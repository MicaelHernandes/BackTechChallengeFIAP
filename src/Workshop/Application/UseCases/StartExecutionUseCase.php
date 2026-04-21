<?php

namespace Domain\Workshop\Application\UseCases;

use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Domain\Inventory\Domain\Enums\PartRequestStatus;
use Domain\Inventory\Domain\Repositories\PartRequestRepositoryInterface;
use Domain\Workshop\Application\Concerns\DispatchesOsEvents;
use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Repositories\OrderServiceRepositoryInterface;

/**
 * AG: Execução da OS
 * APPROVED → IN_EXECUTION
 * Guarda: só pode iniciar se todas as solicitações de peças estiverem
 * no estado PICKED_UP ou FINALIZED (ou se não houver solicitações).
 */
class StartExecutionUseCase
{
    use DispatchesOsEvents;

    public function __construct(
        private readonly OrderServiceRepositoryInterface $osRepository,
        private readonly PartRequestRepositoryInterface $partRequestRepository,
        private readonly PartRepositoryInterface $partRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    public function execute(int $osId): OrderService
    {
        $os = $this->osRepository->findById($osId)
            ?? throw NotFoundException::forEntity('Ordem de Serviço', $osId);

        $this->guardPartRequestsReady($osId);
        $this->guardPartItemsHaveStock($os);

        $previous = $os->getStatus();
        $os->startExecution();

        $saved = $this->osRepository->save($os);
        $this->dispatchOsStatusUpdated($saved, $previous, $this->customerRepository);

        return $saved;
    }

    /**
     * When no part requests are linked to the OS, verify that every part item
     * in the budget has sufficient stock. If part requests exist (even all in
     * picked_up/finalized), the inventory flow already handled availability.
     */
    private function guardPartItemsHaveStock(OrderService $os): void
    {
        if (empty($os->getPartItems())) {
            return;
        }

        if ($this->partRequestRepository->countByOsId($os->getId()) > 0) {
            return;
        }

        $insufficient = [];
        foreach ($os->getPartItems() as $item) {
            $part = $this->partRepository->findById($item->getPartId());
            if ($part === null || !$part->hasStock($item->getQuantity())) {
                $insufficient[] = $item->getPartName();
            }
        }

        if (!empty($insufficient)) {
            $names = implode(', ', $insufficient);
            throw DomainException::because(
                "Não é possível iniciar a execução: estoque insuficiente para: {$names}. Crie uma solicitação de compra."
            );
        }
    }

    private function guardPartRequestsReady(int $osId): void
    {
        $activeStatuses = [
            PartRequestStatus::Received->value,
            PartRequestStatus::Reserved->value,
            PartRequestStatus::OutOfStock->value,
            PartRequestStatus::Purchasing->value,
            PartRequestStatus::AvailableForPickup->value,
        ];

        $pendingRequests = $this->partRequestRepository->countByOsIdAndStatuses($osId, $activeStatuses);

        if ($pendingRequests > 0) {
            throw DomainException::because(
                "Não é possível iniciar a execução: existem {$pendingRequests} solicitação(ões) de peças pendentes de retirada."
            );
        }
    }
}
