<?php

namespace Domain\Workshop\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Domain\Customer\Infrastructure\Models\CustomerModel;
use Domain\Customer\Infrastructure\Models\VehicleModel;
use Domain\Workshop\Domain\Enums\OsStatus;
use Domain\Workshop\Infrastructure\Models\OrderServiceModel;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'PublicTracking', description: 'Rastreamento público de OS (sem autenticação)')]
class PublicOsTrackingController extends Controller
{
    #[OA\Get(
        path: '/api/public/track/{id}',
        summary: 'Consultar status da OS pelo cliente (sem autenticação)',
        tags: ['PublicTracking'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Status atual da OS, incluindo public_status (rótulo simplificado: recebida, diagnostico, aguardando_aprovacao, execucao, finalizada ou entregue). Não expõe valores financeiros do orçamento.'),
            new OA\Response(response: 404, description: 'OS não encontrada'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $model = OrderServiceModel::find($id);

        if ($model === null) {
            return response()->json(['message' => 'Ordem de Serviço não encontrada.'], 404);
        }

        $customer = CustomerModel::find($model->customer_id);
        $vehicle = VehicleModel::find($model->vehicle_id);

        $status = OsStatus::from($model->status);

        return response()->json([
            'data' => [
                'os_id' => $model->id,
                'status' => $status->value,
                'status_label' => $status->label(),
                'public_status' => [
                    'value' => $status->toPublicStatus()->value,
                    'label' => $status->toPublicStatus()->label(),
                ],
                'message' => $status->customerNotificationMessage(),
                'customer_name' => $customer?->name,
                'vehicle' => $vehicle ? "{$vehicle->brand} {$vehicle->model} ({$vehicle->plate})" : null,
                'is_finalized' => $status->isTerminal(),
            ],
        ]);
    }
}
