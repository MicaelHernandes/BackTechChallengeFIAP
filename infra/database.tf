resource "kubernetes_secret_v1" "pgsql" {
  metadata {
    name      = "pgsql-secret"
    namespace = kubernetes_namespace_v1.laravel.metadata[0].name
  }

  data = {
    POSTGRES_PASSWORD = var.db_password
  }

  type = "Opaque"
}

resource "kubernetes_config_map_v1" "pgsql_init" {
  metadata {
    name      = "pgsql-init-scripts"
    namespace = kubernetes_namespace_v1.laravel.metadata[0].name
  }

  data = {
    "10-create-testing-database.sql" = <<-SQL
      SELECT 'CREATE DATABASE ${var.db_test_name}'
      WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '${var.db_test_name}')\gexec
      GRANT ALL PRIVILEGES ON DATABASE ${var.db_test_name} TO ${var.db_user};
    SQL
  }
}

resource "kubernetes_persistent_volume_claim_v1" "pgsql" {
  metadata {
    name      = "pgsql-pvc"
    namespace = kubernetes_namespace_v1.laravel.metadata[0].name
  }

  spec {
    access_modes = ["ReadWriteOnce"]
    resources {
      requests = {
        storage = var.db_storage
      }
    }
  }

  # No kind o volume é provisionado dinamicamente (storageclass "standard");
  # não esperamos um pod consumidor para não travar o apply.
  wait_until_bound = false
}

resource "kubernetes_stateful_set_v1" "pgsql" {
  metadata {
    name      = "pgsql"
    namespace = kubernetes_namespace_v1.laravel.metadata[0].name
    labels = {
      app = "pgsql"
    }
  }

  spec {
    service_name = "pgsql"
    replicas     = 1

    selector {
      match_labels = {
        app = "pgsql"
      }
    }

    template {
      metadata {
        labels = {
          app = "pgsql"
        }
      }

      spec {
        container {
          name  = "postgres"
          image = var.postgres_image

          port {
            container_port = 5432
            name           = "postgres"
          }

          env {
            name  = "POSTGRES_DB"
            value = var.db_name
          }
          env {
            name  = "POSTGRES_USER"
            value = var.db_user
          }
          env {
            name = "POSTGRES_PASSWORD"
            value_from {
              secret_key_ref {
                name = kubernetes_secret_v1.pgsql.metadata[0].name
                key  = "POSTGRES_PASSWORD"
              }
            }
          }
          env {
            name = "PGPASSWORD"
            value_from {
              secret_key_ref {
                name = kubernetes_secret_v1.pgsql.metadata[0].name
                key  = "POSTGRES_PASSWORD"
              }
            }
          }
          # Usa um subdiretório como PGDATA para não conflitar com o
          # lost+found do volume montado.
          env {
            name  = "PGDATA"
            value = "/var/lib/postgresql/data/pgdata"
          }

          volume_mount {
            name       = "pgsql-data"
            mount_path = "/var/lib/postgresql/data"
          }
          volume_mount {
            name       = "init-scripts"
            mount_path = "/docker-entrypoint-initdb.d"
          }

          resources {
            requests = {
              cpu    = "250m"
              memory = "256Mi"
            }
            limits = {
              cpu    = "1000m"
              memory = "1Gi"
            }
          }

          liveness_probe {
            exec {
              command = ["pg_isready", "-q", "-U", var.db_user]
            }
            initial_delay_seconds = 30
            period_seconds        = 10
          }

          readiness_probe {
            exec {
              command = ["pg_isready", "-q", "-U", var.db_user]
            }
            initial_delay_seconds = 5
            period_seconds        = 5
          }
        }

        volume {
          name = "pgsql-data"
          persistent_volume_claim {
            claim_name = kubernetes_persistent_volume_claim_v1.pgsql.metadata[0].name
          }
        }
        volume {
          name = "init-scripts"
          config_map {
            name = kubernetes_config_map_v1.pgsql_init.metadata[0].name
          }
        }
      }
    }
  }
}

# Service headless: dá o nome DNS "pgsql" que o Laravel usa em DB_HOST.
resource "kubernetes_service_v1" "pgsql" {
  metadata {
    name      = "pgsql"
    namespace = kubernetes_namespace_v1.laravel.metadata[0].name
    labels = {
      app = "pgsql"
    }
  }

  spec {
    selector = {
      app = "pgsql"
    }
    port {
      name        = "postgres"
      port        = 5432
      target_port = 5432
    }
    cluster_ip = "None"
  }
}
