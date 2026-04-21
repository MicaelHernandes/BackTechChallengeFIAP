<?php

namespace Domain\Workshop\Domain\Enums;

use Domain\Core\Domain\Exceptions\DomainException;

enum OsStatus: string
{
    case Created              = 'created';
    case InAnalysis           = 'in_analysis';
    case PendingApproval      = 'pending_approval';
    case InRenegotiation      = 'in_renegotiation';
    case Approved             = 'approved';
    case Rejected             = 'rejected';
    case InExecution          = 'in_execution';
    case ExecutionFinished    = 'execution_finished';
    case DeliveredAndFinalized = 'delivered_and_finalized';

    public function label(): string
    {
        return match($this) {
            self::Created              => 'Criada',
            self::InAnalysis           => 'Em Análise',
            self::PendingApproval      => 'Pendente de Aprovação',
            self::InRenegotiation      => 'Em Renegociação',
            self::Approved             => 'Aprovada',
            self::Rejected             => 'Rejeitada/Cancelada',
            self::InExecution          => 'Em Execução',
            self::ExecutionFinished    => 'Execução Finalizada',
            self::DeliveredAndFinalized => 'Finalizada/Entregue',
        };
    }

    /** @return array<OsStatus> */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::Created              => [self::InAnalysis],
            self::InAnalysis           => [self::PendingApproval],
            self::PendingApproval      => [self::Approved, self::InRenegotiation],
            self::InRenegotiation      => [self::Approved, self::Rejected, self::PendingApproval],
            self::Approved             => [self::InExecution],
            self::Rejected             => [],
            self::InExecution          => [self::ExecutionFinished],
            self::ExecutionFinished    => [self::DeliveredAndFinalized],
            self::DeliveredAndFinalized => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function guardTransitionTo(self $target): void
    {
        if (!$this->canTransitionTo($target)) {
            throw DomainException::because(
                "Transição inválida de '{$this->label()}' para '{$target->label()}'."
            );
        }
    }

    public function isTerminal(): bool
    {
        return match($this) {
            self::Rejected, self::DeliveredAndFinalized => true,
            default => false,
        };
    }

    /** Customer-facing notification message for each status. */
    public function customerNotificationMessage(): string
    {
        return match($this) {
            self::Created              => 'Sua Ordem de Serviço foi criada com sucesso. Em breve, nosso time entrará em contato.',
            self::InAnalysis           => 'Sua OS está sendo analisada pelo mecânico responsável.',
            self::PendingApproval      => 'O orçamento da sua OS foi gerado. Aguardamos sua aprovação.',
            self::InRenegotiation      => 'Recebemos sua recusa do orçamento. Uma nova proposta está sendo preparada.',
            self::Approved             => 'Orçamento aprovado! O serviço será iniciado em breve.',
            self::Rejected             => 'Sua OS foi cancelada conforme solicitado. Agradecemos o contato.',
            self::InExecution          => 'O serviço no seu veículo foi iniciado.',
            self::ExecutionFinished    => 'O serviço foi concluído. Seu veículo está pronto para retirada.',
            self::DeliveredAndFinalized => 'Sua OS foi finalizada e o veículo entregue. Obrigado pela preferência!',
        };
    }
}
