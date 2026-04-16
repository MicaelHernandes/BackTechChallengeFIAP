<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Oficina Backend API',
    version: '1.0.0',
    description: 'Documentacao base da API'
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Servidor principal'
)]
#[OA\Get(
    path: '/api/health',
    summary: 'Health check',
    tags: ['System'],
    responses: [
        new OA\Response(response: 200, description: 'OK'),
    ]
)]
final class OpenApiSpec
{
}
