<?php

namespace Domain\Workshop\Domain\Enums;

/**
 * Vocabulário simplificado de status (6 estados) exposto ao cliente/atendimento,
 * mapeado a partir dos 9 estados internos de OsStatus via OsStatus::toPublicStatus().
 */
enum PublicOsStatus: string
{
    case Recebida = 'recebida';
    case Diagnostico = 'diagnostico';
    case AguardandoAprovacao = 'aguardando_aprovacao';
    case Execucao = 'execucao';
    case Finalizada = 'finalizada';
    case Entregue = 'entregue';

    public function label(): string
    {
        return match ($this) {
            self::Recebida => 'Recebida',
            self::Diagnostico => 'Em Diagnóstico',
            self::AguardandoAprovacao => 'Aguardando Aprovação',
            self::Execucao => 'Em Execução',
            self::Finalizada => 'Finalizada',
            self::Entregue => 'Entregue',
        };
    }

    /**
     * Prioridade para ordenação da listagem (menor = mais urgente/aparece primeiro).
     * Em Execução > Aguardando Aprovação > Diagnóstico > Recebida. Finalizada/Entregue
     * ficam com prioridade baixa: são excluídas da listagem padrão, mas precisam de um
     * valor definido para quando aparecem via filtro explícito de status.
     */
    public function priority(): int
    {
        return match ($this) {
            self::Execucao => 1,
            self::AguardandoAprovacao => 2,
            self::Diagnostico => 3,
            self::Recebida => 4,
            self::Finalizada => 5,
            self::Entregue => 6,
        };
    }
}
