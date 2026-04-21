<?php

namespace Domain\Catalog\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Domain\Catalog\Application\DTOs\CreateServiceDTO;
use Domain\Catalog\Application\DTOs\UpdateServiceDTO;
use Domain\Catalog\Application\UseCases\Service\CreateServiceUseCase;
use Domain\Catalog\Application\UseCases\Service\DeleteServiceUseCase;
use Domain\Catalog\Application\UseCases\Service\GetServiceUseCase;
use Domain\Catalog\Application\UseCases\Service\ListServicesUseCase;
use Domain\Catalog\Application\UseCases\Service\UpdateServiceUseCase;
use Domain\Catalog\Presentation\Requests\CreateServiceRequest;
use Domain\Catalog\Presentation\Requests\UpdateServiceRequest;
use Domain\Catalog\Presentation\Resources\ServiceResource;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Services', description: 'Catálogo de serviços/mão de obra')]
class ServiceController extends Controller
{
    public function __construct(
        private readonly ListServicesUseCase $listUseCase,
        private readonly GetServiceUseCase $getUseCase,
        private readonly CreateServiceUseCase $createUseCase,
        private readonly UpdateServiceUseCase $updateUseCase,
        private readonly DeleteServiceUseCase $deleteUseCase,
    ) {}

    #[OA\Get(
        path: '/api/services',
        summary: 'Listar serviços do catálogo',
        security: [['sanctum' => []]],
        tags: ['Services'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de serviços paginada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return ServiceResource::collection($this->listUseCase->execute((int) request()->input('per_page', 15)));
    }

    #[OA\Get(
        path: '/api/services/{id}',
        summary: 'Buscar serviço por ID',
        security: [['sanctum' => []]],
        tags: ['Services'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados do serviço'),
            new OA\Response(response: 404, description: 'Serviço não encontrado'),
        ]
    )]
    public function show(int $id): ServiceResource|JsonResponse
    {
        try {
            return new ServiceResource($this->getUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    #[OA\Post(
        path: '/api/services',
        summary: 'Cadastrar novo serviço',
        security: [['sanctum' => []]],
        tags: ['Services'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'base_price'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Troca de Óleo'),
                    new OA\Property(property: 'base_price', type: 'number', example: 80.00),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'estimated_minutes', type: 'integer', example: 30, nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Serviço criado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ]
    )]
    public function store(CreateServiceRequest $request): ServiceResource|JsonResponse
    {
        $service = $this->createUseCase->execute(CreateServiceDTO::fromArray($request->validated()));
        return (new ServiceResource($service))->response()->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/services/{id}',
        summary: 'Atualizar serviço',
        security: [['sanctum' => []]],
        tags: ['Services'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'base_price'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Troca de Óleo Sintético'),
                    new OA\Property(property: 'base_price', type: 'number', example: 100.00),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'estimated_minutes', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Serviço atualizado'),
            new OA\Response(response: 404, description: 'Serviço não encontrado'),
        ]
    )]
    public function update(UpdateServiceRequest $request, int $id): ServiceResource|JsonResponse
    {
        try {
            $service = $this->updateUseCase->execute(UpdateServiceDTO::fromArray($id, $request->validated()));
            return new ServiceResource($service);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    #[OA\Delete(
        path: '/api/services/{id}',
        summary: 'Remover serviço do catálogo',
        security: [['sanctum' => []]],
        tags: ['Services'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Serviço removido'),
            new OA\Response(response: 404, description: 'Serviço não encontrado'),
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
