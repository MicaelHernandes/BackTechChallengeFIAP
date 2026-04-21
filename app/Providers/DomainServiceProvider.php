<?php

namespace App\Providers;

use Domain\Catalog\Domain\Repositories\PartRepositoryInterface;
use Domain\Catalog\Domain\Repositories\ServiceRepositoryInterface;
use Domain\Catalog\Infrastructure\Repositories\EloquentPartRepository;
use Domain\Catalog\Infrastructure\Repositories\EloquentServiceRepository;
use Domain\Customer\Domain\Repositories\CustomerRepositoryInterface;
use Domain\Customer\Domain\Repositories\VehicleRepositoryInterface;
use Domain\Customer\Infrastructure\Repositories\EloquentCustomerRepository;
use Domain\Customer\Infrastructure\Repositories\EloquentVehicleRepository;
use Domain\Inventory\Application\Policies\AddPartsToInventoryPolicy;
use Domain\Inventory\Application\Policies\NotifyMechanicPolicy;
use Domain\Inventory\Application\Policies\NotifyPurchasingPolicy;
use Domain\Inventory\Domain\Events\PartsAddedToInventoryEvent;
use Domain\Inventory\Domain\Events\PartsReceivedFromSupplierEvent;
use Domain\Inventory\Domain\Events\PartOutOfStockEvent;
use Domain\Inventory\Domain\Repositories\PartRequestRepositoryInterface;
use Domain\Inventory\Infrastructure\Repositories\EloquentPartRequestRepository;
use Domain\Workshop\Application\Policies\NotifyCustomerOfOsStatusPolicy;
use Domain\Workshop\Domain\Events\OsStatusUpdatedEvent;
use Domain\Workshop\Domain\Repositories\OrderServiceRepositoryInterface;
use Domain\Workshop\Infrastructure\Repositories\EloquentOrderServiceRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Customer bounded context
        $this->app->bind(CustomerRepositoryInterface::class, EloquentCustomerRepository::class);
        $this->app->bind(VehicleRepositoryInterface::class, EloquentVehicleRepository::class);

        // Catalog bounded context
        $this->app->bind(PartRepositoryInterface::class, EloquentPartRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, EloquentServiceRepository::class);

        // Inventory bounded context
        $this->app->bind(PartRequestRepositoryInterface::class, EloquentPartRequestRepository::class);

        // Workshop bounded context
        $this->app->bind(OrderServiceRepositoryInterface::class, EloquentOrderServiceRepository::class);
    }

    public function boot(): void
    {
        // ── Inventory Domain Events → Policies ───────────────────────────
        // POL: Notifica Compras quando estoque insuficiente
        Event::listen(PartOutOfStockEvent::class, NotifyPurchasingPolicy::class);

        // POL: Adiciona peças ao estoque após recebimento do fornecedor
        Event::listen(PartsReceivedFromSupplierEvent::class, AddPartsToInventoryPolicy::class);

        // POL: Notifica Mecânico após estoque atualizado
        Event::listen(PartsAddedToInventoryEvent::class, NotifyMechanicPolicy::class);

        // ── Workshop Domain Events → Policies ────────────────────────────
        // POL: Notifica Cliente por e-mail a cada mudança de status da OS
        Event::listen(OsStatusUpdatedEvent::class, NotifyCustomerOfOsStatusPolicy::class);
    }
}
