<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; }
        h2 { color: #2c3e50; }
        .status-badge {
            display: inline-block;
            background: #2980b9;
            color: #fff;
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: bold;
        }
        .message-box {
            background: #f0f7ff;
            border-left: 4px solid #2980b9;
            padding: 14px 18px;
            margin: 20px 0;
            border-radius: 0 8px 8px 0;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #2c3e50; color: #fff; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
    <h2>Atualização da Ordem de Serviço</h2>

    <p>Olá, <strong>{{ $event->customerName }}</strong>!</p>

    <p>
        O status da sua <strong>OS #{{ $event->orderService->getId() }}</strong> foi atualizado.
    </p>

    <p>
        Status atual: <span class="status-badge">{{ $event->orderService->getStatus()->label() }}</span>
    </p>

    <div class="message-box">
        {{ $event->orderService->getStatus()->customerNotificationMessage() }}
    </div>

    @if($event->orderService->getBudget())
    <h3>Resumo do Orçamento</h3>
    <table>
        <tr>
            <th>Item</th>
            <th style="text-align:right;">Valor</th>
        </tr>
        <tr>
            <td>Serviços</td>
            <td style="text-align:right;">R$ {{ number_format($event->orderService->getBudget()->getTotalServices(), 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Peças</td>
            <td style="text-align:right;">R$ {{ number_format($event->orderService->getBudget()->getTotalParts(), 2, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td style="text-align:right;"><strong>R$ {{ number_format($event->orderService->getBudget()->getTotalAmount(), 2, ',', '.') }}</strong></td>
        </tr>
    </table>
    @endif

    <p style="margin-top: 24px; color: #666;">
        Em caso de dúvidas, entre em contato com nossa equipe de atendimento.
    </p>

    <hr>
    <small style="color: #999;">Oficina Mecânica — Sistema Integrado de Atendimento</small>
</body>
</html>
