<?php

namespace Domain\Workshop\Domain\Entities;

use Domain\Workshop\Domain\Enums\OsStatus;

/**
 * Aggregate Root do Bounded Context Workshop.
 * Gerencia o ciclo de vida de uma Ordem de Serviço.
 */
class OrderService
{
    private ?Budget $budget = null;

    /** @var OsServiceItem[] */
    private array $serviceItems = [];

    /** @var OsPartItem[] */
    private array $partItems = [];

    /** @var OsRequestedServiceItem[] */
    private array $requestedServiceItems = [];

    /** @var OsRequestedPartItem[] */
    private array $requestedPartItems = [];

    public function __construct(
        private readonly ?int $id,
        private OsStatus $status,
        private readonly int $customerId,
        private readonly int $vehicleId,
        private string $complaint,
        private ?int $mechanicUserId,
        private ?\DateTimeImmutable $startedAt = null,
        private ?\DateTimeImmutable $finishedAt = null,
    ) {}

    /**
     * @param  OsRequestedServiceItem[]  $requestedServiceItems
     * @param  OsRequestedPartItem[]  $requestedPartItems
     */
    public static function create(
        int $customerId,
        int $vehicleId,
        string $complaint,
        ?int $mechanicUserId = null,
        array $requestedServiceItems = [],
        array $requestedPartItems = [],
    ): self {
        $os = new self(
            id: null,
            status: OsStatus::Created,
            customerId: $customerId,
            vehicleId: $vehicleId,
            complaint: $complaint,
            mechanicUserId: $mechanicUserId,
        );

        $os->requestedServiceItems = $requestedServiceItems;
        $os->requestedPartItems = $requestedPartItems;

        return $os;
    }

    // ── Getters ───────────────────────────────────────────────────────

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): OsStatus
    {
        return $this->status;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }

    public function getComplaint(): string
    {
        return $this->complaint;
    }

    public function getMechanicUserId(): ?int
    {
        return $this->mechanicUserId;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function getBudget(): ?Budget
    {
        return $this->budget;
    }

    /** @return OsServiceItem[] */
    public function getServiceItems(): array
    {
        return $this->serviceItems;
    }

    /** @return OsPartItem[] */
    public function getPartItems(): array
    {
        return $this->partItems;
    }

    /** @return OsRequestedServiceItem[] */
    public function getRequestedServiceItems(): array
    {
        return $this->requestedServiceItems;
    }

    /** @return OsRequestedPartItem[] */
    public function getRequestedPartItems(): array
    {
        return $this->requestedPartItems;
    }

    // ── Setters for hydration (repository use) ────────────────────────

    public function setBudget(?Budget $budget): void
    {
        $this->budget = $budget;
    }

    public function setServiceItems(array $items): void
    {
        $this->serviceItems = $items;
    }

    public function setPartItems(array $items): void
    {
        $this->partItems = $items;
    }

    public function setRequestedServiceItems(array $items): void
    {
        $this->requestedServiceItems = $items;
    }

    public function setRequestedPartItems(array $items): void
    {
        $this->requestedPartItems = $items;
    }

    public function assignMechanic(int $mechanicUserId): void
    {
        $this->mechanicUserId = $mechanicUserId;
    }

    public function markStartedAt(\DateTimeImmutable $at): void
    {
        $this->startedAt = $at;
    }

    public function markFinishedAt(\DateTimeImmutable $at): void
    {
        $this->finishedAt = $at;
    }

    // ── State machine ─────────────────────────────────────────────────

    public function transitionTo(OsStatus $newStatus): void
    {
        $this->status->guardTransitionTo($newStatus);
        $this->status = $newStatus;
    }

    public function sendToAnalysis(): void
    {
        $this->transitionTo(OsStatus::InAnalysis);
    }

    public function generateBudget(Budget $budget, array $serviceItems, array $partItems): void
    {
        $this->transitionTo(OsStatus::PendingApproval);
        $this->budget = $budget;
        $this->serviceItems = $serviceItems;
        $this->partItems = $partItems;
    }

    public function approveBudget(): void
    {
        $this->transitionTo(OsStatus::Approved);
    }

    public function rejectBudget(): void
    {
        $this->transitionTo(OsStatus::InRenegotiation);
    }

    public function rejectRenegotiation(): void
    {
        $this->transitionTo(OsStatus::Rejected);
    }

    public function approveRenegotiation(): void
    {
        $this->transitionTo(OsStatus::Approved);
    }

    public function startExecution(): void
    {
        $this->transitionTo(OsStatus::InExecution);
    }

    public function finishExecution(): void
    {
        $this->transitionTo(OsStatus::ExecutionFinished);
    }

    public function deliverAndFinalize(): void
    {
        $this->transitionTo(OsStatus::DeliveredAndFinalized);
    }

    // ── Serialization ─────────────────────────────────────────────────

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'public_status' => [
                'value' => $this->status->toPublicStatus()->value,
                'label' => $this->status->toPublicStatus()->label(),
            ],
            'customer_id' => $this->customerId,
            'vehicle_id' => $this->vehicleId,
            'complaint' => $this->complaint,
            'mechanic_user_id' => $this->mechanicUserId,
            'budget' => $this->budget?->toArray(),
            'service_items' => array_map(fn ($i) => $i->toArray(), $this->serviceItems),
            'part_items' => array_map(fn ($i) => $i->toArray(), $this->partItems),
            'requested_services' => array_map(fn ($i) => $i->toArray(), $this->requestedServiceItems),
            'requested_parts' => array_map(fn ($i) => $i->toArray(), $this->requestedPartItems),
        ];
    }
}
