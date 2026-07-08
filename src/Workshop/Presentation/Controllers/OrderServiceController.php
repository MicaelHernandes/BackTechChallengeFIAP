<?php

namespace Domain\Workshop\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Workshop\Application\DTOs\CreateOsDTO;
use Domain\Workshop\Application\DTOs\GenerateBudgetDTO;
use Domain\Workshop\Application\UseCases\ApproveBudgetUseCase;
use Domain\Workshop\Application\UseCases\CreateOsUseCase;
use Domain\Workshop\Application\UseCases\DeliverAndFinishOsUseCase;
use Domain\Workshop\Application\UseCases\FinishExecutionUseCase;
use Domain\Workshop\Application\UseCases\GenerateBudgetUseCase;
use Domain\Workshop\Application\UseCases\ListOrderServicesUseCase;
use Domain\Workshop\Application\UseCases\RejectBudgetUseCase;
use Domain\Workshop\Application\UseCases\RejectRenegotiationUseCase;
use Domain\Workshop\Application\UseCases\SendToAnalysisUseCase;
use Domain\Workshop\Application\UseCases\StartExecutionUseCase;
use Domain\Workshop\Domain\Enums\OsStatus;
use Domain\Workshop\Domain\Repositories\OrderServiceRepositoryInterface;
use Domain\Workshop\Presentation\Requests\CreateOsRequest;
use Domain\Workshop\Presentation\Requests\GenerateBudgetRequest;
use Domain\Workshop\Presentation\Resources\OrderServiceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'OrderServices', description: 'Ordens de Serviço — ciclo de vida completo')]
class OrderServiceController extends Controller
{
    public function __construct(
        private readonly OrderServiceRepositoryInterface $repository,
        private readonly ListOrderServicesUseCase $listUseCase,
        private readonly CreateOsUseCase $createUseCase,
        private readonly SendToAnalysisUseCase $sendToAnalysisUseCase,
        private readonly GenerateBudgetUseCase $generateBudgetUseCase,
        private readonly ApproveBudgetUseCase $approveBudgetUseCase,
        private readonly RejectBudgetUseCase $rejectBudgetUseCase,
        private readonly RejectRenegotiationUseCase $rejectRenegotiationUseCase,
        private readonly StartExecutionUseCase $startExecutionUseCase,
        private readonly FinishExecutionUseCase $finishExecutionUseCase,
        private readonly DeliverAndFinishOsUseCase $deliverAndFinishUseCase,
    ) {}

    // ── LIST ─────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/order-services',
        summary: 'Listar Ordens de Serviço',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['created', 'in_analysis', 'pending_approval', 'in_renegotiation', 'approved', 'rejected', 'in_execution', 'execution_finished', 'delivered_and_finalized'])),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Lista paginada de OS, ordenada por prioridade de status (Execução > Aguardando Aprovação > Diagnóstico > Recebida) e mais antigas primeiro. Sem filtro de status, OS rejeitadas/finalizadas/entregues ficam ocultas (exclusão lógica).')]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $status  = $request->input('status') ? OsStatus::from($request->input('status')) : null;
        $perPage = (int) $request->input('per_page', 15);

        return OrderServiceResource::collection(
            $this->listUseCase->execute($status, $perPage)
        );
    }

    // ── SHOW ─────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/order-services/{id}',
        summary: 'Buscar OS por ID',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Dados da OS, incluindo public_status (status simplificado de 6 estados) e requested_services/requested_parts (itens solicitados na abertura, sem preço).'),
            new OA\Response(response: 404, description: 'Não encontrada'),
        ]
    )]
    public function show(int $id): OrderServiceResource|JsonResponse
    {
        $os = $this->repository->findById($id);

        return $os
            ? new OrderServiceResource($os)
            : response()->json(['message' => 'Ordem de Serviço não encontrada.'], 404);
    }

    // ── CREATE ───────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/order-services',
        summary: 'Criar nova OS (Atendente). Status inicial: CREATED',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_id', 'vehicle_id', 'complaint'],
                properties: [
                    new OA\Property(property: 'customer_id', type: 'integer', example: 1),
                    new OA\Property(property: 'vehicle_id', type: 'integer', example: 1),
                    new OA\Property(property: 'complaint', type: 'string', example: 'Carro fazendo barulho ao frear'),
                    new OA\Property(property: 'mechanic_user_id', type: 'integer', nullable: true, example: null),
                    new OA\Property(
                        property: 'services',
                        description: 'Opcional. Serviços solicitados pelo cliente na abertura — sem preço, apenas informativo. O orçamento oficial é gerado depois via generate-budget.',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'service_id', type: 'integer', example: 1),
                            new OA\Property(property: 'quantity', type: 'integer', example: 1),
                        ])
                    ),
                    new OA\Property(
                        property: 'parts',
                        description: 'Opcional. Peças solicitadas pelo cliente na abertura — sem preço, apenas informativo. O orçamento oficial é gerado depois via generate-budget.',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'part_id', type: 'integer', example: 1),
                            new OA\Property(property: 'quantity', type: 'integer', example: 2),
                        ])
                    ),
                ]
            )
        ),
        tags: ['OrderServices'],
        responses: [
            new OA\Response(response: 201, description: 'OS criada (status: created). Se services/parts forem enviados, aparecem em requested_services/requested_parts na resposta, sem preço e sem gerar orçamento.'),
            new OA\Response(response: 404, description: 'Cliente, veículo, serviço ou peça solicitada não encontrado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ]
    )]
    public function store(CreateOsRequest $request): OrderServiceResource|JsonResponse
    {
        try {
            $dto    = CreateOsDTO::fromArray($request->validated());
            $result = $this->createUseCase->execute($dto);

            return (new OrderServiceResource($result))->response()->setStatusCode(201);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: SEND TO ANALYSIS ─────────────────────────────────────────

    #[OA\Post(
        path: '/api/order-services/{id}/send-to-analysis',
        summary: 'Enviar OS para análise (Mecânico). CREATED → IN_ANALYSIS',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para IN_ANALYSIS'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function sendToAnalysis(int $id): OrderServiceResource|JsonResponse
    {
        try {
            return new OrderServiceResource($this->sendToAnalysisUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: GENERATE BUDGET ──────────────────────────────────────────

    #[OA\Post(
        path: '/api/order-services/{id}/generate-budget',
        summary: 'Gerar orçamento (Mecânico). IN_ANALYSIS → PENDING_APPROVAL',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['services', 'parts'],
                properties: [
                    new OA\Property(property: 'services', type: 'array', items: new OA\Items(properties: [new OA\Property(property: 'service_id', type: 'integer'), new OA\Property(property: 'quantity', type: 'integer')])),
                    new OA\Property(property: 'parts', type: 'array', items: new OA\Items(properties: [new OA\Property(property: 'part_id', type: 'integer'), new OA\Property(property: 'quantity', type: 'integer')])),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                ]
            )
        ),
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Orçamento gerado, status PENDING_APPROVAL'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida ou dados inválidos'),
        ]
    )]
    public function generateBudget(GenerateBudgetRequest $request, int $id): OrderServiceResource|JsonResponse
    {
        try {
            $dto = GenerateBudgetDTO::fromArray($id, $request->validated());
            $result = $this->generateBudgetUseCase->execute($dto);

            return new OrderServiceResource($result);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: APPROVE BUDGET ───────────────────────────────────────────

    #[OA\Post(
        path: '/api/order-services/{id}/approve-budget',
        summary: 'Cliente aprova orçamento (Atendente). PENDING_APPROVAL → APPROVED',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para APPROVED'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function approveBudget(int $id): OrderServiceResource|JsonResponse
    {
        try {
            return new OrderServiceResource($this->approveBudgetUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: REJECT BUDGET ────────────────────────────────────────────

    #[OA\Post(
        path: '/api/order-services/{id}/reject-budget',
        summary: 'Cliente recusa orçamento (Atendente). PENDING_APPROVAL → IN_RENEGOTIATION',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para IN_RENEGOTIATION'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function rejectBudget(int $id): OrderServiceResource|JsonResponse
    {
        try {
            return new OrderServiceResource($this->rejectBudgetUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: REJECT RENEGOTIATION (CANCEL) ────────────────────────────

    #[OA\Post(
        path: '/api/order-services/{id}/reject-renegotiation',
        summary: 'Cancelar OS (Atendente). IN_RENEGOTIATION → REJECTED',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para REJECTED'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function rejectRenegotiation(int $id): OrderServiceResource|JsonResponse
    {
        try {
            return new OrderServiceResource($this->rejectRenegotiationUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: APPROVE RENEGOTIATION ────────────────────────────────────

    #[OA\Post(
        path: '/api/order-services/{id}/approve-renegotiation',
        summary: 'Cliente aprova renegociação (Atendente). IN_RENEGOTIATION → APPROVED',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para APPROVED'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function approveRenegotiation(int $id): OrderServiceResource|JsonResponse
    {
        try {
            return new OrderServiceResource($this->approveBudgetUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: START EXECUTION ──────────────────────────────────────────

    #[OA\Post(
        path: '/api/order-services/{id}/start-execution',
        summary: 'Iniciar execução (Mecânico). APPROVED → IN_EXECUTION. Guard: sem peças pendentes.',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para IN_EXECUTION'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida ou peças pendentes'),
        ]
    )]
    public function startExecution(int $id): OrderServiceResource|JsonResponse
    {
        try {
            return new OrderServiceResource($this->startExecutionUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: FINISH EXECUTION ─────────────────────────────────────────

    #[OA\Post(
        path: '/api/order-services/{id}/finish-execution',
        summary: 'Finalizar execução (Mecânico). IN_EXECUTION → EXECUTION_FINISHED',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para EXECUTION_FINISHED'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function finishExecution(int $id): OrderServiceResource|JsonResponse
    {
        try {
            return new OrderServiceResource($this->finishExecutionUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // ── ACTION: DELIVER & FINALIZE ───────────────────────────────────────

    #[OA\Post(
        path: '/api/order-services/{id}/deliver',
        summary: 'Entregar veículo e finalizar OS (Atendente). EXECUTION_FINISHED → DELIVERED_AND_FINALIZED',
        security: [['sanctum' => []]],
        tags: ['OrderServices'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atualizado para DELIVERED_AND_FINALIZED'),
            new OA\Response(response: 404, description: 'Não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function deliver(int $id): OrderServiceResource|JsonResponse
    {
        try {
            return new OrderServiceResource($this->deliverAndFinishUseCase->execute($id));
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
