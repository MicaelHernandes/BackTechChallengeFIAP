<?php

namespace Domain\Inventory\Domain\Repositories;

use Domain\Inventory\Domain\Entities\PartRequest;
use Domain\Inventory\Domain\Enums\PartRequestStatus;
use Illuminate\Pagination\LengthAwarePaginator;

interface PartRequestRepositoryInterface
{
    public function findById(int $id): ?PartRequest;

    /** @return LengthAwarePaginator<PartRequest> */
    public function paginate(?PartRequestStatus $status, int $perPage = 15): LengthAwarePaginator;

    public function save(PartRequest $request): PartRequest;

    /** Count requests for a given OS that are still in any of the provided statuses. */
    public function countByOsIdAndStatuses(int $osId, array $statusValues): int;

    /** Count all requests linked to a given OS, regardless of status. */
    public function countByOsId(int $osId): int;
}
