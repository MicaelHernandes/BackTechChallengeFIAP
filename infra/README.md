# Infraestrutura como Código (Terraform)

Provisionamento do **cluster Kubernetes** e do **banco de dados PostgreSQL** do
projeto via Terraform. O cluster é local (**kind** = _Kubernetes in Docker_),
100% reproduzível e gratuito — ideal para desenvolvimento e para a avaliação do
Tech Challenge. Ao final há instruções de como migrar para a cloud.

> **Divisão de responsabilidades**
> - **Terraform (esta pasta)** → cria o cluster, o namespace e o **banco de dados**.
> - **CI/CD + `k8s/`** → faz o deploy da **aplicação** (app, workers, redis, nginx…).
>
> Por isso o Postgres foi **removido** do `k8s/kustomization.yaml`: quem provisiona
> o banco é o Terraform.

---

## Recursos criados

| Recurso Terraform | Tipo Kubernetes/Infra | Descrição |
|---|---|---|
| `kind_cluster.this` | Cluster kind (Docker) | Cluster Kubernetes com 1 control-plane + `worker_count` workers. Mapeia a porta 80 do cluster para `host_http_port` no host. |
| `kubernetes_namespace_v1.laravel` | Namespace | Namespace `laravel` onde tudo é criado. |
| `kubernetes_secret_v1.pgsql` | Secret | Guarda a senha do PostgreSQL. |
| `kubernetes_config_map_v1.pgsql_init` | ConfigMap | Script SQL de init que cria o banco de **testes** (`laravel_testing`). |
| `kubernetes_persistent_volume_claim_v1.pgsql` | PVC (10Gi) | Volume persistente dos dados do banco. |
| `kubernetes_stateful_set_v1.pgsql` | StatefulSet | Pod do **PostgreSQL 18** com probes de liveness/readiness. |
| `kubernetes_service_v1.pgsql` | Service (headless) | DNS interno `pgsql` usado pela aplicação como `DB_HOST`. |

---

## Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) em execução
- [Terraform >= 1.5](https://developer.hashicorp.com/terraform/install)
- [kubectl](https://kubernetes.io/docs/tasks/tools/) (para inspecionar o cluster)
- [kind CLI](https://kind.sigs.k8s.io/docs/user/quick-start/#installation) (para
  carregar imagens locais com `kind load`, veja abaixo)

> Não é preciso instalar o `kind` manualmente **para criar o cluster** — o
> provider Terraform `tehcyx/kind` baixa o binário necessário sozinho. Mas o
> `kind` CLI continua sendo necessário localmente para o comando `kind load
> docker-image`, que copia a imagem da aplicação para dentro do cluster (em
> CI/CD isso já é feito automaticamente pela pipeline).

---

## Como aplicar

```bash
cd infra

# 1. (opcional) ajuste as variáveis
cp terraform.tfvars.example terraform.tfvars

# 2. inicializa os providers
terraform init

# 3. veja o que será criado
terraform plan

# 4. cria o cluster + banco de dados
terraform apply
```

Ao final, o Terraform grava um `kubeconfig` nesta pasta e imprime os outputs:

```bash
# aponta o kubectl para o cluster recém-criado
export KUBECONFIG="$PWD/kubeconfig"

kubectl get nodes
kubectl get pods -n laravel        # deve mostrar o pod "pgsql-0" rodando
```

### Build + load da imagem da aplicação

O cluster e o banco já estão de pé, mas o kind não enxerga o Docker local — ele
roda em containers isolados. Por isso é preciso construir a imagem e carregá-la
manualmente para dentro do cluster antes do deploy:

```bash
# na raiz do projeto
docker build -t backtechchallenge/laravel-app:local .
kind load docker-image backtechchallenge/laravel-app:local --name backtech-fiap
```

> `backtech-fiap` é o `cluster_name` padrão (veja a tabela de variáveis). Se
> você alterou essa variável no `terraform.tfvars`, use o mesmo nome aqui.
>
> Em produção/CI esse build + load é feito automaticamente pela pipeline
> (`.github/workflows/ci-cd.yml`) — os comandos acima são só para quem quer
> rodar tudo localmente.

### Fazer o deploy da aplicação

Com a imagem já carregada no cluster, aplique os manifestos da aplicação **e**
do `metrics-server` (necessário para o autoscaling, veja a seção seguinte):

```bash
# na raiz do projeto
kubectl apply -k k8s/ && kubectl apply -f k8s/addons/metrics-server.yaml
kubectl rollout status deploy/laravel-app -n laravel
kubectl rollout status deployment/metrics-server -n kube-system
```
### Autoscaling (HPA) e métricas

A aplicação tem `HorizontalPodAutoscaler`s configurados (`k8s/app/hpa.yaml`) que
escalam `laravel-app` (2→6 réplicas) e `laravel-queue` (2→8 réplicas) por uso de
CPU/memória.

Confirme que as métricas estão chegando (leva ~15-20s após o rollout):

```bash
kubectl top pods -n laravel
kubectl get hpa -n laravel
```

#### Testando o autoscaling (simular carga)

Com as métricas funcionando, dá pra ver o HPA escalar de verdade gerando carga
contra o app (`ab` = Apache Bench, geralmente já vem com o `httpd-tools`/`apache2-utils`):

```bash
# 200 conexões concorrentes por 90s
ab -n 1000000 -c 200 -t 90 http://localhost:8080/

# em outro terminal, acompanhe o autoscaling
watch -n 5 'kubectl get hpa -n laravel; echo; kubectl get pods -n laravel -l app=laravel-app'
```

Depois que a carga cessa, o HPA demora ~5 min para escalar de volta (
`stabilizationWindowSeconds: 300` no `scaleDown`, para evitar oscilação).

### Resumo (copiar e colar)

```bash
# 1. infra: cluster + banco
cd infra
cp terraform.tfvars.example terraform.tfvars   # opcional
terraform init
terraform apply
export KUBECONFIG="$PWD/kubeconfig"

# 2. imagem da aplicação
cd ..
docker build -t backtechchallenge/laravel-app:local .
kind load docker-image backtechchallenge/laravel-app:local --name backtech-fiap

# 3. deploy da aplicação + metrics-server (necessário para o HPA funcionar)
kubectl apply -k k8s/ && kubectl apply -f k8s/addons/metrics-server.yaml
kubectl rollout status deploy/laravel-app -n laravel
kubectl rollout status deployment/metrics-server -n kube-system

# 4. acessar
open http://localhost:8080   # ou seu navegador de preferência
```

---

## Como destruir

```bash
cd infra
terraform destroy
```

Isso remove o cluster kind inteiro (e, com ele, o banco e todos os dados).

---

## Variáveis principais

| Variável | Padrão | Descrição |
|---|---|---|
| `cluster_name` | `backtech-fiap` | Nome do cluster kind. |
| `node_image` | `kindest/node:v1.31.2` | Versão do Kubernetes. |
| `worker_count` | `1` | Nodes worker além do control-plane. |
| `host_http_port` | `8080` | Porta do host para acessar o app (`http://localhost:8080`). |
| `db_name` / `db_user` / `db_password` | `laravel` / `laravel` / `secret` | Credenciais do banco. |
| `db_storage` | `10Gi` | Tamanho do volume do banco. |

> ⚠️ **Importante:** `db_name`, `db_user` e `db_password` **precisam bater** com o
> que a aplicação usa em `k8s/config/configmap.yaml` (`DB_DATABASE`, `DB_USERNAME`)
> e `k8s/config/secret.yaml` (`DB_PASSWORD`). Se mudar aqui, mude lá também.

---

## Migrando para a cloud (opcional)

A separação de responsabilidades facilita a troca do alvo. Para provisionar em
cloud, substitua **apenas** `cluster.tf` e `providers.tf`:

- **AWS EKS** → use o módulo [`terraform-aws-modules/eks`](https://github.com/terraform-aws-modules/terraform-aws-eks)
  e aponte o provider `kubernetes` para o endpoint do EKS. O banco pode virar um
  `aws_db_instance` (RDS PostgreSQL) no lugar do StatefulSet.
- **GCP GKE** → use `google_container_cluster` + `google_sql_database_instance` (Cloud SQL).

O `database.tf`, `namespace.tf` e o restante da aplicação (`k8s/`) permanecem
praticamente iguais, pois falam apenas com a API do Kubernetes.
