<?php

namespace Domain\Customer\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Application\DTOs\CreateVehicleDTO;
use Domain\Customer\Application\DTOs\UpdateVehicleDTO;
use Domain\Customer\Application\UseCases\Vehicle\CreateVehicleUseCase;
use Domain\Customer\Application\UseCases\Vehicle\DeleteVehicleUseCase;
use Domain\Customer\Application\UseCases\Vehicle\GetVehicleUseCase;
use Domain\Customer\Application\UseCases\Vehicle\ListVehiclesUseCase;
use Domain\Customer\Application\UseCases\Vehicle\UpdateVehicleUseCase;
use Domain\Customer\Presentation\Requests\Vehicle\CreateVehicleRequest;
use Domain\Customer\Presentation\Requests\Vehicle\UpdateVehicleRequest;
use Domain\Customer\Presentation\Resources\VehicleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Vehicles', description: 'Gerenciamento de veículos por cliente')]
class VehicleController extends Controller
{
    public function __construct(
        private readonly ListVehiclesUseCase $listUseCase,
        private readonly GetVehicleUseCase $getUseCase,
        private readonly CreateVehicleUseCase $createUseCase,
        private readonly UpdateVehicleUseCase $updateUseCase,
        private readonly DeleteVehicleUseCase $deleteUseCase,
    ) {}

    #[OA\Get(
        path: '/api/customers/{customerId}/vehicles',
        summary: 'Listar veículos de um cliente',
        security: [['sanctum' => []]],
        tags: ['Vehicles'],
        parameters: [
            new OA\Parameter(name: 'customerId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de veículos paginada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(int $customerId): AnonymousResourceCollection
    {
        $perPage = (int) request()->input('per_page', 15);
        $result  = $this->listUseCase->execute($customerId, $perPage);

        return VehicleResource::collection($result);
    }

    #[OA\Get(
        path: '/api/customers/{customerId}/vehicles/{id}',
        summary: 'Buscar veículo por ID',
        security: [['sanctum' => []]],
        tags: ['Vehicles'],
        parameters: [
            new OA\Parameter(name: 'customerId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dados do veículo'),
            new OA\Response(response: 404, description: 'Veículo não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    // With ->shallow() routing, show/update/destroy receive only {vehicle}
    public function show(int $id): VehicleResource|JsonResponse
    {
        try {
            $vehicle = $this->getUseCase->execute($id);

            return new VehicleResource($vehicle);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    #[OA\Post(
        path: '/api/customers/{customerId}/vehicles',
        summary: 'Cadastrar veículo para um cliente',
        security: [['sanctum' => []]],
        tags: ['Vehicles'],
        parameters: [
            new OA\Parameter(name: 'customerId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['plate', 'brand', 'model', 'year', 'color'],
                properties: [
                    new OA\Property(property: 'plate', type: 'string', example: 'ABC1D23'),
                    new OA\Property(property: 'brand', type: 'string', example: 'Toyota'),
                    new OA\Property(property: 'model', type: 'string', example: 'Corolla'),
                    new OA\Property(property: 'year', type: 'integer', example: 2020),
                    new OA\Property(property: 'color', type: 'string', example: 'Prata'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Veículo criado'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function store(CreateVehicleRequest $request, int $customerId): VehicleResource|JsonResponse
    {
        try {
            $vehicle = $this->createUseCase->execute(
                CreateVehicleDTO::fromArray($customerId, $request->validated())
            );

            return (new VehicleResource($vehicle))->response()->setStatusCode(201);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Put(
        path: '/api/customers/{customerId}/vehicles/{id}',
        summary: 'Atualizar veículo',
        security: [['sanctum' => []]],
        tags: ['Vehicles'],
        parameters: [
            new OA\Parameter(name: 'customerId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['plate', 'brand', 'model', 'year', 'color'],
                properties: [
                    new OA\Property(property: 'plate', type: 'string', example: 'ABC1D23'),
                    new OA\Property(property: 'brand', type: 'string', example: 'Toyota'),
                    new OA\Property(property: 'model', type: 'string', example: 'Corolla'),
                    new OA\Property(property: 'year', type: 'integer', example: 2020),
                    new OA\Property(property: 'color', type: 'string', example: 'Prata'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Veículo atualizado'),
            new OA\Response(response: 404, description: 'Veículo não encontrado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function update(UpdateVehicleRequest $request, int $id): VehicleResource|JsonResponse
    {
        try {
            $vehicle = $this->updateUseCase->execute(
                UpdateVehicleDTO::fromArray($id, $request->validated())
            );

            return new VehicleResource($vehicle);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Delete(
        path: '/api/customers/{customerId}/vehicles/{id}',
        summary: 'Remover veículo',
        security: [['sanctum' => []]],
        tags: ['Vehicles'],
        parameters: [
            new OA\Parameter(name: 'customerId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Veículo removido'),
            new OA\Response(response: 404, description: 'Veículo não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
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
