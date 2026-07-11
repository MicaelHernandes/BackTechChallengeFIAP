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

> Não é preciso instalar o `kind` manualmente — o provider `tehcyx/kind` baixa o
> binário necessário. O `kind` CLI só é útil para carregar imagens locais
> (`kind load`), o que o CI/CD já faz automaticamente.

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

### Fazer o deploy da aplicação (após a infra)

O banco já está de pé. Agora aplique os manifestos da aplicação:

```bash
# na raiz do projeto
kubectl apply -k k8s/
kubectl rollout status deploy/laravel-app -n laravel
```

> Em produção/CI isso é feito automaticamente pela pipeline
> (`.github/workflows/ci-cd.yml`). Localmente, lembre de fazer o build da imagem
> e `kind load docker-image ... --name backtech-fiap` antes do apply.

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
