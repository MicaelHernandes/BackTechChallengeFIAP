# ---------------------------------------------------------------------------
# Cluster Kubernetes local (kind = Kubernetes IN Docker)
#
# Cria um cluster com 1 control-plane + N workers. A porta 80 do control-plane
# é mapeada para a porta do host (var.host_http_port), permitindo acessar o
# Nginx (DaemonSet com hostPort 80) em http://localhost:<host_http_port>.
# ---------------------------------------------------------------------------
resource "kind_cluster" "this" {
  name            = var.cluster_name
  node_image      = var.node_image
  kubeconfig_path = pathexpand(var.kubeconfig_path)
  wait_for_ready  = true

  kind_config {
    kind        = "Cluster"
    api_version = "kind.x-k8s.io/v1alpha4"

    # Node control-plane, com mapeamento de porta para acesso externo ao app.
    node {
      role = "control-plane"

      extra_port_mappings {
        container_port = 80
        host_port      = var.host_http_port
        protocol       = "TCP"
      }
    }

    # Nodes worker (onde os pods de aplicação/banco de fato rodam).
    dynamic "node" {
      for_each = range(var.worker_count)
      content {
        role = "worker"
      }
    }
  }
}
