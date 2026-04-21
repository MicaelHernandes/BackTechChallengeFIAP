<?php

namespace Domain\Inventory\Domain\Entities;

use Domain\Inventory\Domain\Enums\PartRequestStatus;

class PartRequest
{
    /** @var PartRequestItem[] */
    private array $items;

    public function __construct(
        private readonly ?int $id,
        private PartRequestStatus $status,
        private readonly int $requestedByUserId,
        private readonly ?int $osId,
        private ?string $notes,
        PartRequestItem ...$items,
    ) {
        $this->items = $items;
    }

    public static function create(
        int $requestedByUserId,
        ?int $osId,
        ?string $notes,
        PartRequestItem ...$items,
    ): self {
        return new self(
            null,
            PartRequestStatus::Received,
            $requestedByUserId,
            $osId,
            $notes,
            ...$items,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): PartRequestStatus
    {
        return $this->status;
    }

    public function getRequestedByUserId(): int
    {
        return $this->requestedByUserId;
    }

    public function getOsId(): ?int
    {
        return $this->osId;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /** @return PartRequestItem[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function transitionTo(PartRequestStatus $newStatus): void
    {
        $this->status->guardTransitionTo($newStatus);
        $this->status = $newStatus;
    }

    public function reserve(): void
    {
        $this->transitionTo(PartRequestStatus::Reserved);
    }

    public function markOutOfStock(): void
    {
        $this->transitionTo(PartRequestStatus::OutOfStock);
    }

    public function requestPurchase(): void
    {
        $this->transitionTo(PartRequestStatus::Purchasing);
    }

    public function markAvailableForPickup(): void
    {
        $this->transitionTo(PartRequestStatus::AvailableForPickup);
    }

    public function pickUp(): void
    {
        $this->transitionTo(PartRequestStatus::PickedUp);
    }

    public function finalize(): void
    {
        $this->transitionTo(PartRequestStatus::Finalized);
    }

    public function toArray(): array
    {
        return [
            'id'                     => $this->id,
            'status'                 => $this->status->value,
            'status_label'           => $this->status->label(),
            'requested_by_user_id'   => $this->requestedByUserId,
            'os_id'                  => $this->osId,
            'notes'                  => $this->notes,
            'items'                  => array_map(fn ($i) => $i->toArray(), $this->items),
        ];
    }
}
