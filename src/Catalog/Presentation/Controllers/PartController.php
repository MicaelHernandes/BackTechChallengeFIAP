<?php

namespace Domain\Catalog\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Domain\Catalog\Application\DTOs\CreatePartDTO;
use Domain\Catalog\Application\DTOs\UpdatePartDTO;
use Domain\Catalog\Application\UseCases\Part\CreatePartUseCase;
use Domain\Catalog\Application\UseCases\Part\DeletePartUseCase;
use Domain\Catalog\Application\UseCases\Part\GetPartUseCase;
use Domain\Catalog\Application\UseCases\Part\ListPartsUseCase;
use Domain\Catalog\Application\UseCases\Part\UpdatePartUseCase;
use Domain\Catalog\Presentation\Requests\CreatePartRequest;
use Domain\Catalog\Presentation\Requests\UpdatePartRequest;
use Domain\Catalog\Presentation\Resources\PartResource;
use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Parts', description: 'Catálogo de peças e controle de estoque')]
class PartController extends Controller
{
    public function __construct(
        private readonly ListPartsUseCase $listUseCase,
        private readonly GetPartUseCase $getUseCase,
        private readonly CreatePartUseCase $createUseCase,
        private readonly UpdatePartUseCase $updateUseCase,
        private readonly DeletePartUseCase $deleteUseCase,
    ) {}

    #[OA\Get(
        path: '/api/parts',
        summary: 'Listar peças do catálogo',
        security: [['sanctum' => []]],
        tags: ['Parts'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de peças paginada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $perPage = (int) request()->input('per_page', 15);
        return PartResource::collection($this->listUseCase->execute($perPage));
    }

    #[OA\Get(
        path: '/api/parts/{id}',
        summary: 'Buscar peça por ID',
        security: [['sanctum' => []]],
        tags: ['Parts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados da peça'),
            new OA\Response(response: 404, description: 'Peça não encontrada'),
        ]
    )]
    public function show(int $id): PartResource|JsonResponse
    {
        try {
            return new PartResource($this->getUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    #[OA\Post(
        path: '/api/parts',
        summary: 'Cadastrar nova peça',
        security: [['sanctum' => []]],
        tags: ['Parts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code', 'name', 'unit_price'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: 'FILTRO-AR-001'),
                    new OA\Property(property: 'name', type: 'string', example: 'Filtro de Ar'),
                    new OA\Property(property: 'unit_price', type: 'number', example: 45.90),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'stock_quantity', type: 'integer', example: 10),
                    new OA\Property(property: 'minimum_stock', type: 'integer', example: 2),
                    new OA\Property(property: 'unit', type: 'string', example: 'un'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Peça criada'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ]
    )]
    public function store(CreatePartRequest $request): PartResource|JsonResponse
    {
        try {
            $part = $this->createUseCase->execute(CreatePartDTO::fromArray($request->validated()));
            return (new PartResource($part))->response()->setStatusCode(201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Put(
        path: '/api/parts/{id}',
        summary: 'Atualizar peça',
        security: [['sanctum' => []]],
        tags: ['Parts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'unit_price'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Filtro de Ar Premium'),
                    new OA\Property(property: 'unit_price', type: 'number', example: 55.90),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'minimum_stock', type: 'integer', example: 3),
                    new OA\Property(property: 'unit', type: 'string', example: 'un'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Peça atualizada'),
            new OA\Response(response: 404, description: 'Peça não encontrada'),
        ]
    )]
    public function update(UpdatePartRequest $request, int $id): PartResource|JsonResponse
    {
        try {
            $part = $this->updateUseCase->execute(UpdatePartDTO::fromArray($id, $request->validated()));
            return new PartResource($part);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Delete(
        path: '/api/parts/{id}',
        summary: 'Remover peça do catálogo',
        security: [['sanctum' => []]],
        tags: ['Parts'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Peça removida'),
            new OA\Response(response: 404, description: 'Peça não encontrada'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->deleteUseCase->execute($id);
            return response()->json(null, 204);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
