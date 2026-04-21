<?php

namespace Domain\Customer\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Customer\Application\DTOs\CreateCustomerDTO;
use Domain\Customer\Application\DTOs\UpdateCustomerDTO;
use Domain\Customer\Application\UseCases\Customer\CreateCustomerUseCase;
use Domain\Customer\Application\UseCases\Customer\DeleteCustomerUseCase;
use Domain\Customer\Application\UseCases\Customer\GetCustomerUseCase;
use Domain\Customer\Application\UseCases\Customer\ListCustomersUseCase;
use Domain\Customer\Application\UseCases\Customer\UpdateCustomerUseCase;
use Domain\Customer\Presentation\Requests\Customer\CreateCustomerRequest;
use Domain\Customer\Presentation\Requests\Customer\UpdateCustomerRequest;
use Domain\Customer\Presentation\Resources\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Customers', description: 'Gerenciamento de clientes')]
class CustomerController extends Controller
{
    public function __construct(
        private readonly ListCustomersUseCase $listUseCase,
        private readonly GetCustomerUseCase $getUseCase,
        private readonly CreateCustomerUseCase $createUseCase,
        private readonly UpdateCustomerUseCase $updateUseCase,
        private readonly DeleteCustomerUseCase $deleteUseCase,
    ) {}

    #[OA\Get(
        path: '/api/customers',
        summary: 'Listar clientes',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista de clientes paginada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $perPage = (int) request()->input('per_page', 15);
        $result  = $this->listUseCase->execute($perPage);

        return CustomerResource::collection($result);
    }

    #[OA\Get(
        path: '/api/customers/{id}',
        summary: 'Buscar cliente por ID',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Dados do cliente'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function show(int $id): CustomerResource|JsonResponse
    {
        try {
            $customer = $this->getUseCase->execute($id);

            return new CustomerResource($customer);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    #[OA\Post(
        path: '/api/customers',
        summary: 'Criar novo cliente',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'document', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'João da Silva'),
                    new OA\Property(property: 'document', type: 'string', example: '529.982.247-25'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '(11) 99999-9999', nullable: true),
                    new OA\Property(property: 'address', type: 'string', example: 'Rua das Flores, 123', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente criado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function store(CreateCustomerRequest $request): CustomerResource|JsonResponse
    {
        try {
            $customer = $this->createUseCase->execute(
                CreateCustomerDTO::fromArray($request->validated())
            );

            return (new CustomerResource($customer))->response()->setStatusCode(201);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Put(
        path: '/api/customers/{id}',
        summary: 'Atualizar cliente',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'João da Silva'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'joao@example.com'),
                    new OA\Property(property: 'phone', type: 'string', example: '(11) 99999-9999', nullable: true),
                    new OA\Property(property: 'address', type: 'string', example: 'Rua das Flores, 123', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cliente atualizado'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function update(UpdateCustomerRequest $request, int $id): CustomerResource|JsonResponse
    {
        try {
            $customer = $this->updateUseCase->execute(
                UpdateCustomerDTO::fromArray($id, $request->validated())
            );

            return new CustomerResource($customer);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Delete(
        path: '/api/customers/{id}',
        summary: 'Remover cliente',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Cliente removido'),
            new OA\Response(response: 404, description: 'Cliente não encontrado'),
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
