resource "kubernetes_namespace_v1" "laravel" {
  metadata {
    name = var.namespace
    labels = {
      "app.kubernetes.io/managed-by" = "terraform"
      "app.kubernetes.io/part-of"    = "backtech-fiap"
    }
  }
}
