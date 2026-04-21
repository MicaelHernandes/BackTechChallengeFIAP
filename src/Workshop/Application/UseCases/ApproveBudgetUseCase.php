<?php

namespace Domain\Workshop\Application\UseCases;

use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Domain\Workshop\Application\Concerns\DispatchesOsEvents;
use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Repositories\OrderServiceRepositoryInterface;

/**
 * AG: Orçamento
 * Cliente aprova orçamento → PENDING_APPROVAL | IN_RENEGOTIATION → APPROVED
 */
class ApproveBudgetUseCase
{
    use DispatchesOsEvents;

    public function __construct(
        private readonly OrderServiceRepositoryInterface $osRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    public function execute(int $osId): OrderService
    {
        $os = $this->osRepository->findById($osId)
            ?? throw NotFoundException::forEntity('Ordem de Serviço', $osId);

        $previous = $os->getStatus();
        $os->approveBudget();

        $saved = $this->osRepository->save($os);
        $this->dispatchOsStatusUpdated($saved, $previous, $this->customerRepository);

        return $saved;
    }
}
