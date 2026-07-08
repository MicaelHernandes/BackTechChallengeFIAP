<?php

namespace Domain\Workshop\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Domain\Core\Domain\Exceptions\DomainException;
use Domain\Core\Domain\Exceptions\NotFoundException;
use Domain\Workshop\Application\UseCases\ApproveBudgetUseCase;
use Domain\Workshop\Application\UseCases\RejectBudgetUseCase;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'PublicBudgetApproval', description: 'Aprovação/rejeição pública de orçamento via link assinado (e-mail)')]
class PublicBudgetApprovalController extends Controller
{
    public function __construct(
        private readonly ApproveBudgetUseCase $approveBudgetUseCase,
        private readonly RejectBudgetUseCase $rejectBudgetUseCase,
    ) {}

    #[OA\Get(
        path: '/api/public/order-services/{id}/approve-budget',
        summary: 'Cliente aprova orçamento via link assinado enviado por e-mail (sem autenticação)',
        tags: ['PublicBudgetApproval'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Orçamento aprovado'),
            new OA\Response(response: 403, description: 'Assinatura inválida ou expirada'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida (ex.: link já utilizado)'),
        ]
    )]
    public function approve(int $id): JsonResponse
    {
        try {
            $os = $this->approveBudgetUseCase->execute($id);

            return response()->json([
                'message' => 'Orçamento aprovado com sucesso.',
                'data' => [
                    'id' => $os->getId(),
                    'status' => $os->getStatus()->value,
                    'status_label' => $os->getStatus()->label(),
                ],
            ]);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    #[OA\Get(
        path: '/api/public/order-services/{id}/reject-budget',
        summary: 'Cliente recusa orçamento via link assinado enviado por e-mail (sem autenticação)',
        tags: ['PublicBudgetApproval'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Orçamento recusado, OS em renegociação'),
            new OA\Response(response: 403, description: 'Assinatura inválida ou expirada'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
            new OA\Response(response: 422, description: 'Transição inválida'),
        ]
    )]
    public function reject(int $id): JsonResponse
    {
        try {
            $os = $this->rejectBudgetUseCase->execute($id);

            return response()->json([
                'message' => 'Orçamento recusado. Uma nova proposta será preparada.',
                'data' => [
                    'id' => $os->getId(),
                    'status' => $os->getStatus()->value,
                    'status_label' => $os->getStatus()->label(),
                ],
            ]);
        } catch (NotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
