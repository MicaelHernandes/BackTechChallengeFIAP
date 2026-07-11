output "cluster_name" {
  description = "Nome do cluster kind criado."
  value       = kind_cluster.this.name
}

output "kubeconfig_path" {
  description = "Caminho do kubeconfig gerado. Use: export KUBECONFIG=<este caminho>."
  value       = kind_cluster.this.kubeconfig_path
}

output "cluster_endpoint" {
  description = "Endpoint da API do cluster."
  value       = kind_cluster.this.endpoint
}

output "namespace" {
  description = "Namespace provisionado."
  value       = kubernetes_namespace_v1.laravel.metadata[0].name
}

output "database_host" {
  description = "Host interno do banco (usar como DB_HOST na aplicação)."
  value       = "${kubernetes_service_v1.pgsql.metadata[0].name}.${var.namespace}.svc.cluster.local"
}

output "database_name" {
  description = "Nome do banco de dados da aplicação."
  value       = var.db_name
}

output "database_port" {
  description = "Porta do banco de dados."
  value       = 5432
}

output "app_url" {
  description = "URL para acessar a aplicação no host após o deploy do app pelo CI/CD."
  value       = "http://localhost:${var.host_http_port}"
}
