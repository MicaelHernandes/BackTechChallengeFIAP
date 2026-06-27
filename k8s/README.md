# Manifestos Kubernetes — Laravel 13 + Sail

## Estrutura

```
k8s/
├── namespace.yaml              # Namespace "laravel"
├── kustomization.yaml          # Apply tudo de uma vez
├── config/
│   ├── configmap.yaml          # Variáveis não-sensíveis
│   └── secret.yaml             # Variáveis sensíveis (APP_KEY, senhas)
├── app/
│   ├── deployment.yaml         # App Laravel (2 réplicas)
│   ├── service.yaml            # ClusterIP + LoadBalancer externo
│   ├── ingress.yaml            # Ingress NGINX (opcional)
│   ├── queue-worker.yaml       # Workers de fila (2 réplicas)
│   └── scheduler.yaml          # CronJob para php artisan schedule:run
├── nginx/
│   └── deployment.yaml         # Nginx reverse proxy em Node IP:80
├── pgsql/
│   └── statefulset.yaml        # PostgreSQL 18 + PVC + init script
├── redis/
│   └── deployment.yaml         # Redis alpine + PVC
├── mailpit/
│   └── deployment.yaml         # Mailpit (SMTP fake para dev/staging)
└── minio/
    └── statefulset.yaml        # MinIO (S3-compatible) + PVC
```

## Pré-requisitos

- Cluster Kubernetes (EKS, GKE, AKS, k3s, etc.)
- `kubectl` configurado
- Sua imagem Docker publicada em um registry acessível pelo cluster

## Antes de aplicar

### 1. Publique sua imagem Docker

```bash
# Build local (Dockerfile na raiz)
docker build -t backtechchallenge/laravel-app:local .

# Se estiver usando kind:
kind load docker-image backtechchallenge/laravel-app:local --name mycluster
```

Substitua `backtechchallenge/laravel-app:local` nos arquivos abaixo pela sua imagem:
- `app/deployment.yaml`
- `app/queue-worker.yaml`
- `app/scheduler.yaml`

### 2. Configure os Secrets

Edite `config/secret.yaml` com seus valores reais:

```bash
# Gere a APP_KEY
php artisan key:generate --show

# Ou codifique manualmente em base64
echo -n "sua-app-key" | base64
```

### 3. Ajuste o ConfigMap

Edite `config/configmap.yaml`:
- `DB_DATABASE`, `DB_USERNAME` — nome do banco e usuário
- `APP_NAME` — nome da aplicação

## Aplicando os manifestos

```bash
# Aplicar tudo de uma vez com Kustomize
kubectl apply -k k8s/

# Ou aplicar individualmente
kubectl apply -f k8s/namespace.yaml
kubectl apply -f k8s/config/
kubectl apply -f k8s/pgsql/
kubectl apply -f k8s/redis/
kubectl apply -f k8s/mailpit/
kubectl apply -f k8s/minio/
kubectl apply -f k8s/app/
```

## Verificando o deploy

```bash
# Status dos pods
kubectl get pods -n laravel

# Logs da aplicação
kubectl logs -n laravel deploy/laravel-app -f

# Logs do worker
kubectl logs -n laravel deploy/laravel-queue -f

# Ver serviços e IP externo
kubectl get svc -n laravel
```

## Acesso ao app via Node IP:80 (Nginx)

Foi adicionado um Nginx (`laravel-nginx-proxy`) com `hostNetwork`/`hostPort: 80` que encaminha para `laravel-app`.

```bash
# Descubra o IP do node
kubectl get nodes -o wide

# Acesse no navegador
http://<NODE_IP>/
```

## Executando comandos Artisan

```bash
# Abre shell no pod da aplicação
kubectl exec -it -n laravel deploy/laravel-app -- bash

# Ou executa diretamente
kubectl exec -n laravel deploy/laravel-app -- php artisan migrate
kubectl exec -n laravel deploy/laravel-app -- php artisan cache:clear
```

## Acesso aos dashboards internos

```bash
# App Laravel via Nginx (Node IP:80)
kubectl get nodes -o wide
# Acesse: http://<NODE_IP>/

# MinIO Console (port-forward)
kubectl port-forward -n laravel svc/minio 8900:8900
# Acesse: http://localhost:8900

# Mailpit Dashboard (port-forward)
kubectl port-forward -n laravel svc/mailpit 8025:8025
# Acesse: http://localhost:8025

# PostgreSQL (port-forward)
kubectl port-forward -n laravel svc/pgsql 5432:5432
```

## Removendo tudo

```bash
kubectl delete namespace laravel
```
