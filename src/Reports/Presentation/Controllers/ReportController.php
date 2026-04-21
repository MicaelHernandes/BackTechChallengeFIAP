<?php

namespace Domain\Reports\Presentation\Controllers;

use App\Http\Controllers\Controller;
use Domain\Catalog\Infrastructure\Models\PartModel;
use Domain\Workshop\Infrastructure\Models\BudgetModel;
use Domain\Workshop\Infrastructure\Models\OrderServiceModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Reports', description: 'Relatórios gerenciais da oficina')]
class ReportController extends Controller
{
    // ── OS Summary ────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/reports/os-summary',
        summary: 'Resumo geral das Ordens de Serviço: contagem por status e receita total',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        responses: [new OA\Response(response: 200, description: 'Resumo de OS')]
    )]
    public function osSummary(): JsonResponse
    {
        $countByStatus = OrderServiceModel::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $totalRevenue = BudgetModel::query()
            ->join('order_services', 'budgets.os_id', '=', 'order_services.id')
            ->where('order_services.status', 'delivered_and_finalized')
            ->sum('budgets.total_amount');

        $pendingRevenue = BudgetModel::query()
            ->join('order_services', 'budgets.os_id', '=', 'order_services.id')
            ->whereIn('order_services.status', ['pending_approval', 'in_renegotiation', 'approved', 'in_execution', 'execution_finished'])
            ->sum('budgets.total_amount');

        return response()->json([
            'data' => [
                'counts_by_status' => $countByStatus,
                'total_os'         => OrderServiceModel::count(),
                'revenue'          => [
                    'finalized' => round((float) $totalRevenue, 2),
                    'pending'   => round((float) $pendingRevenue, 2),
                ],
            ],
        ]);
    }

    // ── Revenue by period ─────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/reports/revenue',
        summary: 'Receita de OS finalizadas em um período (from/to em YYYY-MM-DD)',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'to',   in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2026-12-31')),
        ],
        responses: [new OA\Response(response: 200, description: 'Receita no período')]
    )]
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = BudgetModel::query()
            ->join('order_services', 'budgets.os_id', '=', 'order_services.id')
            ->where('order_services.status', 'delivered_and_finalized')
            ->select(
                'order_services.id as os_id',
                'order_services.updated_at as delivered_at',
                'budgets.total_services',
                'budgets.total_parts',
                'budgets.total_amount',
            );

        if ($from = $request->input('from')) {
            $query->whereDate('order_services.updated_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('order_services.updated_at', '<=', $to);
        }

        $rows = $query->orderBy('order_services.updated_at')->get();

        $totals = [
            'total_services' => round($rows->sum('total_services'), 2),
            'total_parts'    => round($rows->sum('total_parts'), 2),
            'total_amount'   => round($rows->sum('total_amount'), 2),
            'os_count'       => $rows->count(),
        ];

        return response()->json([
            'data' => [
                'period'  => ['from' => $request->input('from'), 'to' => $request->input('to')],
                'totals'  => $totals,
                'entries' => $rows->toArray(),
            ],
        ]);
    }

    // ── Average execution time ────────────────────────────────────────────

    #[OA\Get(
        path: '/api/reports/avg-execution-time',
        summary: 'Tempo médio de execução das OS (do início ao fim da execução)',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        responses: [new OA\Response(response: 200, description: 'Tempo médio de execução')]
    )]
    public function avgExecutionTime(): JsonResponse
    {
        $rows = OrderServiceModel::query()
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->select('id', 'mechanic_user_id', 'started_at', 'finished_at')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'data' => [
                    'os_count'            => 0,
                    'avg_minutes'         => null,
                    'avg_human'           => null,
                    'by_mechanic'         => [],
                ],
            ]);
        }

        $minutes = $rows->map(fn ($r) =>
            $r->started_at->diffInMinutes($r->finished_at)
        );

        $byMechanic = $rows->groupBy('mechanic_user_id')->map(function ($group) {
            $avg = $group->avg(fn ($r) => $r->started_at->diffInMinutes($r->finished_at));
            return [
                'mechanic_user_id' => $group->first()->mechanic_user_id,
                'os_count'         => $group->count(),
                'avg_minutes'      => round($avg),
                'avg_human'        => $this->minutesToHuman(round($avg)),
            ];
        })->values();

        $avgTotal = round($minutes->avg());

        return response()->json([
            'data' => [
                'os_count'    => $rows->count(),
                'avg_minutes' => $avgTotal,
                'avg_human'   => $this->minutesToHuman($avgTotal),
                'by_mechanic' => $byMechanic,
            ],
        ]);
    }

    private function minutesToHuman(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes}min";
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $m > 0 ? "{$h}h {$m}min" : "{$h}h";
    }

    // ── Low stock ─────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/reports/low-stock',
        summary: 'Peças com estoque abaixo ou igual ao estoque mínimo',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        responses: [new OA\Response(response: 200, description: 'Lista de peças com estoque baixo')]
    )]
    public function lowStock(): JsonResponse
    {
        $parts = PartModel::query()
            ->whereColumn('stock_quantity', '<=', 'minimum_stock')
            ->orderBy('stock_quantity')
            ->get(['id', 'code', 'name', 'unit', 'unit_price', 'stock_quantity', 'minimum_stock']);

        return response()->json([
            'data' => $parts->map(fn ($p) => [
                'id'             => $p->id,
                'code'           => $p->code,
                'name'           => $p->name,
                'unit'           => $p->unit,
                'stock_quantity' => $p->stock_quantity,
                'minimum_stock'  => $p->minimum_stock,
                'deficit'        => max(0, $p->minimum_stock - $p->stock_quantity),
            ]),
        ]);
    }
}
