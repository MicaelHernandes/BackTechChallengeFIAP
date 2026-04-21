<?php

namespace Domain\Inventory\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Inventory\Application\DTOs\CreatePartRequestDTO;
use Domain\Inventory\Application\DTOs\ReceivePartsFromSupplierDTO;
use Domain\Inventory\Application\UseCases\CreatePartRequestUseCase;
use Domain\Inventory\Application\UseCases\FinishPartRequestUseCase;
use Domain\Inventory\Application\UseCases\PickUpPartsUseCase;
use Domain\Inventory\Application\UseCases\ReceivePartsFromSupplierUseCase;
use Domain\Inventory\Application\UseCases\RequestPurchaseFromSupplierUseCase;
use Domain\Inventory\Domain\Enums\PartRequestStatus;
use Domain\Inventory\Domain\Repositories\PartRequestRepositoryInterface;
use Domain\Inventory\Presentation\Requests\CreatePartRequestRequest;
use Domain\Inventory\Presentation\Requests\ReceivePartsFromSupplierRequest;
use Domain\Inventory\Presentation\Resources\PartRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'PartRequests', description: 'Solicitações de peças — fluxo de estoque')]
class PartRequestController extends Controller
{
    public function __construct(
        private readonly PartRequestRepositoryInterface $repository,
        private readonly CreatePartRequestUseCase $createUseCase,
        private readonly RequestPurchaseFromSupplierUseCase $requestPurchaseUseCase,
        private readonly ReceivePartsFromSupplierUseCase $receiveFromSupplierUseCase,
        private readonly PickUpPartsUseCase $pickUpUseCase,
        private readonly FinishPartRequestUseCase $finishUseCase,
    ) {}

    // ── LIST ─────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/part-requests',
        summary: 'Listar solicitações de peças',
        security: [['sanctum' => []]],
        tags: ['PartRequests'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['received','reserved','out_of_stock','purchasing','available_for_pickup','picked_up','finalized'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista paginada')]
    )]
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $status  = $request->input('status') ? PartRequestStatus::from($request->input('status')) : null;
        $perPage = (int) $request->input('per_page', 15);

        return PartRequestResource::collection(
            $this->repository->paginate($status, $perPage)
        );
    }

    // ── SHOW ─────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/part-requests/{id}',
        summary: 'Buscar solicitação por ID',
        security: [['sanctum' => []]],
        tags: ['PartRequests'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados da solicitação'),
            new OA\Response(response: 404, description: 'Não encontrada'),
        ]
    )]
    public function show(int $id): PartRequestResource|JsonResponse
    {
        $partRequest = $this->repository->findById($id);
        return $partRequest
            ? new PartRequestResource($partRequest)
            : response()->json(['message' => 'Solicitação não encontrada.'], 404);
    }

    // ── CREATE ───────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/part-requests',
        summary: 'Criar solicitação de peças (Mecânico). CheckInventoryPolicy executada automaticamente.',
        security: [['sanctum' => []]],
        tags: ['PartRequests'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(property: 'os_id', type: 'integer', nullable: true, example: null),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'part_id', type: 'integer', example: 1),
                                new OA\Property(property: 'quantity', type: 'integer', example: 2),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Solicitação criada (status: reserved ou out_of_stock)'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ]
    )]
    public function store(CreatePartRequestRequest $request): PartRequestResource|JsonResponse
    {
        try {
            $dto    = CreatePartRequestDTO::fromArray($request->user()->id, $request->validated());
            $result = $this->createUseCase->execute($dto);

            return (new PartRequestResource($result))->response()->setStatusCode(201);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: REQUEST PURCHASE ─────────────────────────────────────────

    #[OA\Post(
        path: '/api/part-requests/{id}/request-purchase',
        summary: 'Solicitar compra ao fornecedor (Compras). OUT_OF_STOCK → PURCHASING',
        security: [['sanctum' => []]],
        tags: ['PartRequests'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para PURCHASING'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function requestPurchase(int $id): PartRequestResource|JsonResponse
    {
        try {
            return new PartRequestResource($this->requestPurchaseUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: RECEIVE FROM SUPPLIER ────────────────────────────────────

    #[OA\Post(
        path: '/api/part-requests/{id}/receive-from-supplier',
        summary: 'Registrar recebimento do fornecedor (Compras). PURCHASING → AVAILABLE_FOR_PICKUP. AddPartsToInventoryPolicy + NotifyMechanicPolicy disparadas.',
        security: [['sanctum' => []]],
        tags: ['PartRequests'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['supplier_name', 'items'],
                properties: [
                    new OA\Property(property: 'supplier_name', type: 'string', example: 'Distribuidora ABC'),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'part_request_item_id', type: 'integer', example: 1),
                                new OA\Property(property: 'quantity_received', type: 'integer', example: 3),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para AVAILABLE_FOR_PICKUP'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function receiveFromSupplier(ReceivePartsFromSupplierRequest $request, int $id): PartRequestResource|JsonResponse
    {
        try {
            $dto    = ReceivePartsFromSupplierDTO::fromArray($id, $request->validated());
            $result = $this->receiveFromSupplierUseCase->execute($dto);

            return new PartRequestResource($result);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: PICK UP ──────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/part-requests/{id}/pick-up',
        summary: 'Mecânico retira peças. RESERVED|AVAILABLE_FOR_PICKUP → PICKED_UP',
        security: [['sanctum' => []]],
        tags: ['PartRequests'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para PICKED_UP'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function pickUp(int $id): PartRequestResource|JsonResponse
    {
        try {
            return new PartRequestResource($this->pickUpUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: FINISH ───────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/part-requests/{id}/finish',
        summary: 'Almoxarife finaliza solicitação. PICKED_UP → FINALIZED',
        security: [['sanctum' => []]],
        tags: ['PartRequests'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para FINALIZED'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function finish(int $id): PartRequestResource|JsonResponse
    {
        try {
            return new PartRequestResource($this->finishUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
