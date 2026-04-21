<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; }
        h2 { color: #c0392b; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #c0392b; color: #fff; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .badge { background: #c0392b; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <h2>⚠️ Estoque Insuficiente — Ação Necessária</h2>

    <p>O setor de <strong>Almoxarifado</strong> registrou uma solicitação de peças que <strong>não pôde ser atendida</strong> com o estoque atual.</p>

    <p><strong>Solicitação #{{ $partRequest->getId() }}</strong><br>
       Status: <span class="badge">{{ $partRequest->getStatus()->label() }}</span><br>
       @if($partRequest->getNotes())
       Observações: {{ $partRequest->getNotes() }}
       @endif
    </p>

    <h3>Peças sem estoque:</h3>
    <table>
        <tr>
            <th>Peça</th>
            <th>Quantidade Solicitada</th>
        </tr>
        @foreach($partRequest->getItems() as $item)
        <tr>
            <td>{{ $item->getPartName() }}</td>
            <td>{{ $item->getQuantityRequested() }} {{ 'un' }}</td>
        </tr>
        @endforeach
    </table>

    <p style="margin-top: 24px;">Por favor, acesse o sistema e realize o pedido ao fornecedor o mais breve possível.</p>

    <hr>
    <small style="color: #999;">Oficina Mecânica — Sistema Integrado de Atendimento</small>
</body>
</html>
