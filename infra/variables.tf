variable "cluster_name" {
  description = "Nome do cluster Kubernetes (kind)."
  type        = string
  default     = "backtech-fiap"
}

variable "node_image" {
  description = "Imagem do node kind (define a versão do Kubernetes)."
  type        = string
  default     = "kindest/node:v1.31.2"
}

variable "worker_count" {
  description = "Quantidade de nodes worker além do control-plane."
  type        = number
  default     = 1
}

variable "host_http_port" {
  description = "Porta do host mapeada para a porta 80 do cluster (acesso ao Nginx/app)."
  type        = number
  default     = 8080
}

variable "kubeconfig_path" {
  description = "Caminho onde o kubeconfig do cluster será gravado (usado pelo kubectl e pelo CI/CD)."
  type        = string
  default     = "./kubeconfig"
}

variable "namespace" {
  description = "Namespace onde a aplicação e o banco serão criados."
  type        = string
  default     = "laravel"
}

variable "db_name" {
  description = "Nome do banco de dados da aplicação."
  type        = string
  default     = "laravel"
}

variable "db_user" {
  description = "Usuário do banco de dados."
  type        = string
  default     = "laravel"
}

variable "db_password" {
  description = "Senha do banco de dados."
  type        = string
  default     = "secret"
  sensitive   = true
}

variable "db_test_name" {
  description = "Banco de dados de testes criado automaticamente na inicialização."
  type        = string
  default     = "laravel_testing"
}

variable "postgres_image" {
  description = "Imagem Docker do PostgreSQL."
  type        = string
  default     = "postgres:18-alpine"
}

variable "db_storage" {
  description = "Tamanho do volume persistente do banco de dados."
  type        = string
  default     = "10Gi"
}
