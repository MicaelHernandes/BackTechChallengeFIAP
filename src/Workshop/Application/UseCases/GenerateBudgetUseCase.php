<?php

namespace Domain\Workshop\Application\UseCases;

use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Catalog\Domain\Repositories\ServiceRepositoryInterface;
use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Domain\Workshop\Application\Concerns\DispatchesOsEvents;
use Domain\Workshop\Application\DTOs\GenerateBudgetDTO;
use Domain\Workshop\Domain\Entities\Budget;
use Domain\Workshop\Domain\Entities\OrderService;
use Domain\Workshop\Domain\Entities\OsPartItem;
use Domain\Workshop\Domain\Entities\OsServiceItem;
use Domain\Workshop\Domain\Enums\OsStatus;
use Domain\Workshop\Domain\Repositories\OrderServiceRepositoryInterface;

/**
 * AG: Orçamento
 * Mecânico inclui serviços e peças → sistema gera orçamento → IN_ANALYSIS → PENDING_APPROVAL
 */
class GenerateBudgetUseCase
{
    use DispatchesOsEvents;

    public function __construct(
        private readonly OrderServiceRepositoryInterface $osRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly PartRepositoryInterface $partRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {}

    public function execute(GenerateBudgetDTO $dto): OrderService
    {
        $os = $this->osRepository->findById($dto->osId)
            ?? throw NotFoundException::forEntity('Ordem de Serviço', $dto->osId);

        if (empty($dto->services) && empty($dto->parts)) {
            throw DomainException::because('O orçamento deve conter pelo menos um serviço ou peça.');
        }

        // Build service items
        $serviceItems = [];
        foreach ($dto->services as $item) {
            $service = $this->serviceRepository->findById((int) $item['service_id'])
                ?? throw NotFoundException::forEntity('Serviço', $item['service_id']);

            $serviceItems[] = OsServiceItem::create(
                serviceId: $service->getId(),
                serviceName: $service->getName(),
                quantity: (int) $item['quantity'],
                unitPrice: $service->getBasePrice(),
            );
        }

        // Build part items
        $partItems = [];
        foreach ($dto->parts as $item) {
            $part = $this->partRepository->findById((int) $item['part_id'])
                ?? throw NotFoundException::forEntity('Peça', $item['part_id']);

            $partItems[] = OsPartItem::create(
                partId: $part->getId(),
                partName: $part->getName(),
                quantity: (int) $item['quantity'],
                unitPrice: $part->getUnitPrice(),
            );
        }

        $budget = Budget::calculate(
            osId: $os->getId(),
            serviceItems: $serviceItems,
            partItems: $partItems,
            notes: $dto->notes,
            existingId: $os->getBudget()?->getId(),
        );

        $previous = $os->getStatus();
        $os->generateBudget($budget, $serviceItems, $partItems);

        $saved = $this->osRepository->save($os);
        $this->dispatchOsStatusUpdated($saved, $previous, $this->customerRepository);

        return $saved;
    }
}
