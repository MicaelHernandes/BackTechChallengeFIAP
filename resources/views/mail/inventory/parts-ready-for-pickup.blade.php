<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; }
        h2 { color: #27ae60; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #27ae60; color: #fff; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        .badge { background: #27ae60; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <h2>✅ Peças Disponíveis para Retirada</h2>

    <p>As peças da solicitação abaixo foram recebidas do fornecedor e estão <strong>disponíveis para retirada</strong> no almoxarifado.</p>

    <p><strong>Solicitação #{{ $partRequest->getId() }}</strong><br>
       Status: <span class="badge">{{ $partRequest->getStatus()->label() }}</span><br>
       Fornecedor: <strong>{{ $supplierName }}</strong>
    </p>

    <h3>Peças disponíveis:</h3>
    <table>
        <tr>
            <th>Peça</th>
            <th>Solicitado</th>
            <th>Fornecido</th>
        </tr>
        @foreach($partRequest->getItems() as $item)
        <tr>
            <td>{{ $item->getPartName() }}</td>
            <td>{{ $item->getQuantityRequested() }}</td>
            <td>{{ $item->getQuantityProvided() > 0 ? $item->getQuantityProvided() : $item->getQuantityRequested() }}</td>
        </tr>
        @endforeach
    </table>

    <p style="margin-top: 24px;">Dirija-se ao almoxarifado para retirar as peças e iniciar a execução.</p>

    <hr>
    <small style="color: #999;">Oficina Mecânica — Sistema Integrado de Atendimento</small>
</body>
</html>
