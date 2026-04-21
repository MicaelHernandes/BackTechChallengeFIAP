<?php

namespace Domain\Customer\Domain\Entities;

use Domain\Customer\Domain\ValueObjects\LicensePlate;

class Vehicle
{
    public function __construct(
        private readonly ?int $id,
        private LicensePlate $plate,
        private string $brand,
        private string $model,
        private int $year,
        private string $color,
        private readonly int $customerId,
    ) {}

    public static function create(
        string $plate,
        string $brand,
        string $model,
        int $year,
        string $color,
        int $customerId,
    ): self {
        return new self(
            id: null,
            plate: new LicensePlate($plate),
            brand: $brand,
            model: $model,
            year: $year,
            color: $color,
            customerId: $customerId,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlate(): LicensePlate
    {
        return $this->plate;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function updateDetails(string $brand, string $model, int $year, string $color): void
    {
        $this->brand = $brand;
        $this->model = $model;
        $this->year  = $year;
        $this->color = $color;
    }

    public function changePlate(string $plate): void
    {
        $this->plate = new LicensePlate($plate);
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'plate'       => $this->plate->value(),
            'brand'       => $this->brand,
            'model'       => $this->model,
            'year'        => $this->year,
            'color'       => $this->color,
            'customer_id' => $this->customerId,
        ];
    }
}
