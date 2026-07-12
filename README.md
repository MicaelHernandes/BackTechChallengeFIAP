# Oficina Mecânica — API Backend

teste cicd

API RESTful para gerenciamento completo de uma oficina mecânica, desenvolvida com **Laravel 13**, arquitetura **Domain-Driven Design (DDD)** e banco de dados **PostgreSQL**.

---

## Sumário

- [Objetivos do Projeto](#objetivos-do-projeto)
- [Tecnologias Utilizadas](#tecnologias-utilizadas)
- [Arquitetura](#arquitetura)
- [Módulos e Funcionalidades](#módulos-e-funcionalidades)
- [Pré-requisitos](#pré-requisitos)
- [Instalação e Configuração](#instalação-e-configuração)
- [Executando os Testes](#executando-os-testes)
- [Documentação da API (Swagger)](#documentação-da-api-swagger)
- [Credenciais de Acesso](#credenciais-de-acesso)
- [Fluxo Principal — Ordem de Serviço](#fluxo-principal--ordem-de-serviço)
- [Endpoints Disponíveis](#endpoints-disponíveis)
- [Estrutura de Pastas](#estrutura-de-pastas)
- [Arquitetura de Infraestrutura e Deploy](#arquitetura-de-infraestrutura-e-deploy)

---

## Objetivos do Projeto

O sistema atende ao ciclo de vida completo de uma oficina mecânica, desde o cadastro de clientes até a entrega do veículo reparado, cobrindo:

- **Gestão de clientes e veículos** com validação de CPF/CNPJ e placa
- **Catálogo de peças e serviços** com controle de estoque mínimo
- **Controle de estoque** via solicitações de compra com máquina de estados
- **Ordens de Serviço** com ciclo completo de 9 estados, orçamento e aprovação do cliente
- **Notificações por e-mail** automáticas a cada mudança de status
- **Rastreamento público** da OS sem necessidade de login
- **Relatórios gerenciais**: resumo de OS, receita por período, estoque baixo e tempo médio de execução

---

## Tecnologias Utilizadas

| Camada | Tecnologia |
|---|---|
| Framework | Laravel 13 |
| Linguagem | PHP 8.3+ |
| Banco de dados | PostgreSQL (produção) / SQLite (testes) |
| Autenticação | Laravel Sanctum (tokens Bearer) |
| Testes | Pest PHP 4 |
| Documentação | Swagger via `l5-swagger` (atributos PHP 8) |
| E-mail | SMTP / Mailpit (desenvolvimento) |
| Arquitetura | Domain-Driven Design (DDD) |

---

## Arquitetura

O projeto adota DDD com separação em **Bounded Contexts** dentro de `src/`. Cada contexto segue a estrutura de camadas:

```
src/
└── <Contexto>/
    ├── Domain/
    │   ├── Entities/        # Entidades e Agregados
    │   ├── Enums/           # Máquinas de estado (enum PHP 8)
    │   ├── Events/          # Eventos de domínio
    │   ├── Repositories/    # Interfaces de repositório
    │   └── ValueObjects/    # Objetos de valor
    ├── Application/
    │   ├── DTOs/            # Objetos de transferência
    │   ├── Mail/            # Mailables
    │   ├── Policies/        # Handlers de eventos (listeners)
    │   └── UseCases/        # Casos de uso (um por operação)
    └── Infrastructure/
        ├── Models/          # Modelos Eloquent
        ├── Repositories/    # Implementações Eloquent
        └── Presentation/
            ├── Controllers/ # Controllers com Swagger
            ├── Requests/    # Form Requests (validação)
            └── Resources/   # API Resources (resposta)
```

**Decisões de design:**
- Repositórios são interfaces no domínio, implementados na infraestrutura e ligados via `DomainServiceProvider`
- Eventos de domínio (`Event::dispatch`) desacoplam regras de negócio de efeitos colaterais (e-mails, atualização de estoque)
- Máquinas de estado como enums PHP 8.1+ com `allowedTransitions()` e `guardTransitionTo()` que lançam `DomainException` em transições inválidas
- Cada Use Case faz exatamente uma operação, facilitando testes unitários

---

## Módulos e Funcionalidades

### Auth
- Login/logout com token Bearer (Sanctum)
- 5 papéis: `admin`, `attendant`, `mechanic`, `storekeeper`, `purchasing`

### Customer — Clientes e Veículos
- CRUD completo de clientes com validação de CPF/CNPJ
- CRUD de veículos vinculados (placa, marca, modelo, ano, cor)
- Value Objects `CpfCnpj` e `LicensePlate` com validação de formato

### Catalog — Catálogo
- CRUD de **peças** com estoque atual, estoque mínimo, unidade e preço
- CRUD de **serviços** com duração estimada e ativação/desativação

### Inventory — Controle de Estoque
Solicitações de peças com máquina de 7 estados:

```
received → reserved          (estoque disponível: decrementa imediatamente)
received → out_of_stock      (sem estoque: inicia fluxo de compra)
          → purchasing
          → available_for_pickup   (estoque incrementado ao receber do fornecedor)
          → picked_up
          → finalized
```

- Ao criar: verifica estoque automaticamente e encaminha para o estado correto
- Ao receber do fornecedor: adiciona quantidade ao estoque via evento `PartsReceivedFromSupplierEvent`
- E-mail automático para Compras quando há falta de estoque
- E-mail automático para Mecânico quando peças ficam disponíveis

### Workshop — Ordens de Serviço
Máquina de 9 estados com regras de negócio em cada transição:

```
created → in_analysis → pending_approval ⇄ in_renegotiation
                                        ↓
                                     approved → in_execution → execution_finished → delivered_and_finalized
                                        ↓
                                     rejected
```

- **Abertura da OS** pode já incluir `services`/`parts` desejados pelo cliente/atendente — ficam registrados como **itens solicitados** (`requested_services`/`requested_parts`), sem preço e sem gerar orçamento. O orçamento oficial continua sendo gerado depois via `generate-budget`, na etapa de diagnóstico.
- **Status público simplificado**: todo retorno de OS traz um campo `public_status` que mapeia os 9 estados internos para 6 rótulos (`recebida`, `diagnostico`, `aguardando_aprovacao`, `execucao`, `finalizada`, `entregue`), usados na consulta de status e na priorização da listagem.
- **Listagem priorizada**: `GET /api/order-services` ordena por prioridade de status (Execução > Aguardando Aprovação > Diagnóstico > Recebida) e, dentro da mesma prioridade, mais antigas primeiro. Sem filtro de `status`, OS rejeitadas/finalizadas/entregues ficam ocultas por padrão (exclusão lógica — os registros continuam existindo e aparecem normalmente com `?status=` explícito).
- **Aprovação/recusa de orçamento por link assinado**: o e-mail de orçamento pendente (`pending_approval`/`in_renegotiation`) inclui dois links assinados (válidos por 7 dias) que o cliente pode clicar sem login para aprovar ou recusar — endpoints públicos que reaproveitam as mesmas regras de transição de estado.
- Geração de orçamento com itens de serviço e peças (snapshots de preço)
- Aprovação/rejeição pelo cliente com ciclo de renegociação
- Guard de execução: bloqueia `start_execution` se há peças sem estoque ou solicitações pendentes
- E-mail ao cliente a cada mudança de status com mensagem personalizada
- Timestamps `started_at` / `finished_at` para mensuração de tempo de execução

### Reports — Relatórios
- **Resumo de OS**: contagem por status + receita finalizada/pendente
- **Receita por período**: filtro por data com totais e entradas detalhadas
- **Estoque baixo**: peças abaixo do estoque mínimo com déficit calculado
- **Tempo médio de execução**: média geral e por mecânico em formato legível (ex: `3h 7min`)

### Rastreamento Público
- `GET /api/public/track/{id}` — sem autenticação
- Exibe status (interno e `public_status` simplificado), mensagem ao cliente, veículo e nome do cliente
- Não expõe valores financeiros do orçamento
- `GET /api/public/order-services/{id}/approve-budget` e `.../reject-budget` — aprovação/recusa de orçamento via link assinado enviado por e-mail, sem autenticação (protegido pela assinatura, não por login)

---

## Pré-requisitos

- **Docker** e **Docker Compose** (única dependência necessária)

O projeto usa **Laravel Sail**, que sobe automaticamente os containers de PHP 8.3, PostgreSQL e Mailpit sem nenhuma instalação adicional.

---

## Instalação e Configuração

### 1. Clonar o repositório

```bash
git clone https://github.com/MicaelHernandes/BackTechChallengeFIAP
cd BackTechChallengeFIAP
```

### 2. Instalar dependências via Docker (sem PHP local)

```bash
docker run --rm -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs
```

### 3. Configurar o ambiente

```bash
cp .env.example .env
```

> O `.env.example` já vem pré-configurado para o Sail (PostgreSQL + Mailpit). Nenhuma edição necessária.

### 4. Subir os containers

```bash
./vendor/bin/sail up -d
```

Aguarde os containers iniciarem (na primeira vez faz o build da imagem, ~1 minuto).

### 5. Gerar a chave e executar migrations + seeders

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

### 6. Pronto!

| Serviço | URL |
|---|---|
| API | `http://localhost` |
| Documentação Swagger | `http://localhost/api/documentation` |
| Mailpit (caixa de e-mails) | `http://localhost:8025` |

Para parar os containers:
```bash
./vendor/bin/sail down
```

---

## Executando os Testes

Os testes usam **SQLite em memória** — não dependem do PostgreSQL e podem rodar a qualquer momento:

```bash
./vendor/bin/sail artisan test
```

Ou diretamente com Pest:
```bash
./vendor/bin/sail exec laravel.test ./vendor/bin/pest
```

Para ver detalhes por arquivo:
```bash
./vendor/bin/sail artisan test --verbose
```

### Suíte de testes

| Arquivo | Tipo | Casos |
|---|---|---|
| `Feature/Auth/AuthTest` | Feature | 6 |
| `Feature/Customer/CustomerApiTest` | Feature | 11 |
| `Feature/Customer/VehicleApiTest` | Feature | 6 |
| `Feature/Catalog/PartApiTest` | Feature | 9 |
| `Feature/Inventory/PartRequestApiTest` | Feature | 15 |
| `Feature/Workshop/OrderServiceApiTest` | Feature | 17 |
| `Feature/Workshop/PublicOsTrackingTest` | Feature | 10 |
| `Feature/Workshop/PublicBudgetApprovalTest` | Feature | 7 |
| `Feature/Reports/ReportApiTest` | Feature | 8 |
| `Unit/Customer/CpfCnpjTest` | Unit | 8 |
| `Unit/Customer/LicensePlateTest` | Unit | 5 |
| `Unit/Inventory/PartRequestStatusTest` | Unit | 11 |
| `Unit/Workshop/OsStatusTest` | Unit | 20 |
| `Unit/Workshop/PublicOsStatusTest` | Unit | 3 |

---

## Documentação da API (Swagger)

Após subir o Sail, acesse:

```
http://localhost/api/documentation
```

Para regenerar a documentação após alterações:
```bash
./vendor/bin/sail artisan l5-swagger:generate
```

---

## Credenciais de Acesso

Após executar `./vendor/bin/sail artisan migrate --seed`, os seguintes usuários estarão disponíveis:

| Papel | E-mail | Senha |
|---|---|---|
| Administrador | `admin@oficina.com` | `password` |
| Atendente | `atendente@oficina.com` | `password` |
| Mecânico | `mecanico@oficina.com` | `password` |
| Almoxarife | `almoxarife@oficina.com` | `password` |
| Compras | `compras@oficina.com` | `password` |

**Autenticação:** todas as rotas protegidas requerem o header:
```
Authorization: Bearer {token}
```

O token é obtido via `POST /api/auth/login`.

---

## Fluxo Principal — Ordem de Serviço

Exemplo de ciclo completo via API:

```
1. POST /api/auth/login                                    → obtém token

2. POST /api/customers                                     → cria cliente
3. POST /api/customers/{id}/vehicles                       → cadastra veículo

4. POST /api/parts                                         → cadastra peça (catálogo)
5. POST /api/services                                      → cadastra serviço (catálogo)

6. POST /api/order-services                                → abre OS (status: created)
   body opcional: { "services": [...], "parts": [...] }   ← itens solicitados, sem preço
7. POST /api/order-services/{id}/send-to-analysis          → status: in_analysis
8. POST /api/order-services/{id}/generate-budget           → gera orçamento (status: pending_approval)
   body: { "services": [...], "parts": [...] }
   → dispara e-mail ao cliente com links de aprovar/recusar (válidos por 7 dias)

9. POST /api/order-services/{id}/approve-budget            → status: approved
   (alternativa: cliente clica no link do e-mail → GET /api/public/order-services/{id}/approve-budget)

   [Se faltar peças em estoque:]
10. POST /api/part-requests                                → cria solicitação vinculada à OS
    body: { "os_id": X, "items": [{ "part_id": Y, "quantity": Z }] }
11. POST /api/part-requests/{id}/request-purchase          → status: purchasing
12. POST /api/part-requests/{id}/receive-from-supplier     → status: available_for_pickup
    body: { "supplier_name": "...", "items": [...] }
13. POST /api/part-requests/{id}/pick-up                   → status: picked_up

14. POST /api/order-services/{id}/start-execution          → status: in_execution
15. POST /api/order-services/{id}/finish-execution         → status: execution_finished
16. POST /api/order-services/{id}/deliver                  → status: delivered_and_finalized

17. GET  /api/reports/avg-execution-time                   → tempo médio de execução
```

---

## Endpoints Disponíveis

### Auth
| Método | Rota | Descrição |
|---|---|---|
| POST | `/api/auth/login` | Login — retorna token Bearer |
| POST | `/api/auth/logout` | Logout (invalida token) |
| GET  | `/api/auth/me` | Dados do usuário autenticado |

### Clientes
| Método | Rota | Descrição |
|---|---|---|
| GET    | `/api/customers` | Lista paginada |
| POST   | `/api/customers` | Cria cliente |
| GET    | `/api/customers/{id}` | Detalhe |
| PUT    | `/api/customers/{id}` | Atualiza |
| DELETE | `/api/customers/{id}` | Remove (soft delete) |
| GET    | `/api/customers/{id}/vehicles` | Veículos do cliente |
| POST   | `/api/customers/{id}/vehicles` | Adiciona veículo |
| PUT    | `/api/vehicles/{id}` | Atualiza veículo |
| DELETE | `/api/vehicles/{id}` | Remove veículo |

### Catálogo
| Método | Rota | Descrição |
|---|---|---|
| GET/POST | `/api/parts` | Lista / cria peças |
| GET/PUT/DELETE | `/api/parts/{id}` | Detalhe / atualiza / remove |
| GET/POST | `/api/services` | Lista / cria serviços |
| GET/PUT/DELETE | `/api/services/{id}` | Detalhe / atualiza / remove |

### Estoque
| Método | Rota | Descrição |
|---|---|---|
| GET  | `/api/part-requests` | Lista (filtro por status via `?status=`) |
| POST | `/api/part-requests` | Cria solicitação |
| GET  | `/api/part-requests/{id}` | Detalhe |
| POST | `/api/part-requests/{id}/request-purchase` | Encaminha para compra |
| POST | `/api/part-requests/{id}/receive-from-supplier` | Registra recebimento do fornecedor |
| POST | `/api/part-requests/{id}/pick-up` | Mecânico retira peças |
| POST | `/api/part-requests/{id}/finish` | Finaliza solicitação |

### Ordens de Serviço
| Método | Rota | Descrição |
|---|---|---|
| GET  | `/api/order-services` | Lista (filtro por status via `?status=`). Ordenada por prioridade de status (Execução > Aguardando Aprovação > Diagnóstico > Recebida) e mais antigas primeiro. Sem filtro, oculta OS rejeitadas/finalizadas/entregues (exclusão lógica) |
| POST | `/api/order-services` | Abre nova OS. Aceita opcionalmente `services`/`parts` como itens solicitados (sem preço) |
| GET  | `/api/order-services/{id}` | Detalhe completo com orçamento, `public_status` e itens solicitados |
| POST | `/api/order-services/{id}/send-to-analysis` | Inicia análise |
| POST | `/api/order-services/{id}/generate-budget` | Gera orçamento |
| POST | `/api/order-services/{id}/approve-budget` | Aprova orçamento |
| POST | `/api/order-services/{id}/reject-budget` | Rejeita orçamento (inicia renegociação) |
| POST | `/api/order-services/{id}/approve-renegotiation` | Aprova nova proposta |
| POST | `/api/order-services/{id}/reject-renegotiation` | Cancela OS |
| POST | `/api/order-services/{id}/start-execution` | Inicia execução |
| POST | `/api/order-services/{id}/finish-execution` | Conclui execução |
| POST | `/api/order-services/{id}/deliver` | Entrega e finaliza |

### Relatórios
| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/reports/os-summary` | Contagem por status e receita |
| GET | `/api/reports/revenue` | Receita por período (`?from=YYYY-MM-DD&to=YYYY-MM-DD`) |
| GET | `/api/reports/low-stock` | Peças abaixo do estoque mínimo |
| GET | `/api/reports/avg-execution-time` | Tempo médio de execução por mecânico |

### Público (sem autenticação)
| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/public/track/{id}` | Rastreamento da OS pelo cliente (inclui `public_status`) |
| GET | `/api/public/order-services/{id}/approve-budget` | Aprova orçamento via link assinado do e-mail (protegido por assinatura, válido 7 dias) |
| GET | `/api/public/order-services/{id}/reject-budget` | Recusa orçamento via link assinado do e-mail (protegido por assinatura, válido 7 dias) |

---

## Estrutura de Pastas

```
BackTechChallengeFIAP/
├── app/
│   ├── Enums/UserRole.php               # Papéis de usuário
│   ├── Http/
│   │   ├── Controllers/Auth/            # Controller de autenticação
│   │   └── Middleware/ForceJsonResponse # Força Content-Type JSON
│   ├── Models/User.php
│   └── Providers/DomainServiceProvider  # Bindings DI + Event listeners
├── database/
│   ├── migrations/                      # 19 migrations versionadas
│   └── seeders/                         # Dados iniciais (users, catalog, customers)
├── resources/views/mail/                # Templates de e-mail Blade
│   ├── inventory/
│   └── workshop/
├── routes/api.php                       # Todas as rotas da API
├── src/                                 # Domínio DDD
│   ├── Core/                            # Exceções e Value Objects base
│   ├── Customer/                        # Bounded Context: Clientes
│   ├── Catalog/                         # Bounded Context: Catálogo
│   ├── Inventory/                       # Bounded Context: Estoque
│   ├── Workshop/                        # Bounded Context: Oficina
│   └── Reports/                         # Relatórios gerenciais
├── tests/
│   ├── Feature/                         # Testes de integração HTTP
│   └── Unit/                            # Testes de unidade (domínio puro)
├── infra/                               # IaC: Terraform (cluster kind + PostgreSQL)
├── k8s/                                 # Manifestos Kubernetes da aplicação
├── .github/workflows/ci-cd.yml          # Pipeline de CI/CD (GitHub Actions)
└── storage/api-docs/api-docs.json       # Spec OpenAPI gerada
```

---

## Arquitetura de Infraestrutura e Deploy

O desenho completo da arquitetura de infraestrutura (componentes da aplicação, infraestrutura provisionada via Terraform/Kubernetes e o fluxo de deploy do CI/CD) está documentado separadamente em **[`ARCHITECTURE.md`](./ARCHITECTURE.md)**, incluindo diagramas Mermaid do fluxo de componentes e da pipeline de deploy.

## Infraestrutura como Código e CI/CD

O projeto provisiona sua infraestrutura e faz deploy de forma automatizada.

### Infraestrutura (Terraform — `infra/`)

O Terraform provisiona um **cluster Kubernetes local** (kind) e o **banco de
dados PostgreSQL**. Detalhes, recursos criados e passo a passo em
[`infra/README.md`](infra/README.md).

```bash
cd infra
terraform init
terraform apply        # cria o cluster kind + namespace + PostgreSQL
```

### Aplicação (Kubernetes — `k8s/`)

Os manifestos da aplicação (app, workers, scheduler, redis, nginx, HPA) são
aplicados com Kustomize. Detalhes em [`k8s/README.md`](k8s/README.md).

```bash
kubectl apply -k k8s/
```

### Pipeline (GitHub Actions — `.github/workflows/ci-cd.yml`)

A cada `push` na branch `master`, a pipeline executa, em ordem:

1. **Build da aplicação** — instala dependências PHP e compila os assets (Vite).
2. **Testes automatizados** — roda a suíte Pest contra um PostgreSQL real.
3. **Build da imagem Docker** — publica no GitHub Container Registry (GHCR).
4. **Provisiona cluster + banco** — `terraform apply` (cluster kind + PostgreSQL).
5. **Deploy no Kubernetes** — `kubectl apply -k k8s/` com a imagem recém-publicada.
6. **Migrations + rollout** — executa o Job de migrations e aguarda os pods subirem.

> Em Pull Requests apenas os testes rodam; build e deploy acontecem só no `master`.
