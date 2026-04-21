<?php

namespace Database\Seeders;

use Domain\Catalog\Infrastructure\Models\PartModel;
use Domain\Catalog\Infrastructure\Models\ServiceModel;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // ── Serviços base ──────────────────────────────────────────────
        $services = [
            ['name' => 'Troca de Óleo',               'base_price' => 80.00,  'estimated_minutes' => 30,  'description' => 'Troca de óleo do motor com filtro.'],
            ['name' => 'Alinhamento',                  'base_price' => 120.00, 'estimated_minutes' => 60,  'description' => 'Alinhamento das rodas dianteiras e traseiras.'],
            ['name' => 'Balanceamento',                'base_price' => 80.00,  'estimated_minutes' => 45,  'description' => 'Balanceamento de 4 rodas.'],
            ['name' => 'Revisão Completa',             'base_price' => 350.00, 'estimated_minutes' => 180, 'description' => 'Revisão completa: óleo, filtros, freios e suspensão.'],
            ['name' => 'Troca de Pastilhas de Freio',  'base_price' => 150.00, 'estimated_minutes' => 60,  'description' => 'Substituição das pastilhas de freio dianteiras.'],
            ['name' => 'Troca de Correia Dentada',     'base_price' => 280.00, 'estimated_minutes' => 120, 'description' => 'Substituição da correia dentada e tensor.'],
            ['name' => 'Diagnóstico Eletrônico',       'base_price' => 60.00,  'estimated_minutes' => 30,  'description' => 'Leitura de falhas com scanner OBD.'],
            ['name' => 'Troca de Amortecedores',       'base_price' => 400.00, 'estimated_minutes' => 150, 'description' => 'Substituição do par de amortecedores traseiros.'],
        ];

        foreach ($services as $service) {
            ServiceModel::updateOrCreate(['name' => $service['name']], array_merge($service, ['is_active' => true]));
        }

        // ── Peças — com estoque variado ────────────────────────────────
        // Algumas com estoque, outras com estoque zero (para forçar fluxo de compras)
        $parts = [
            // Com estoque suficiente
            ['code' => 'OLEO-5W30-1L',  'name' => 'Óleo Motor 5W30 1L',          'unit_price' => 28.90, 'stock_quantity' => 20, 'minimum_stock' => 5,  'unit' => 'lt'],
            ['code' => 'FILTRO-OLEO-1', 'name' => 'Filtro de Óleo Universal',     'unit_price' => 18.50, 'stock_quantity' => 15, 'minimum_stock' => 4,  'unit' => 'un'],
            ['code' => 'FILTRO-AR-001', 'name' => 'Filtro de Ar',                 'unit_price' => 45.00, 'stock_quantity' => 10, 'minimum_stock' => 3,  'unit' => 'un'],
            ['code' => 'PAST-FREIO-D',  'name' => 'Pastilha de Freio Dianteira',  'unit_price' => 89.90, 'stock_quantity' => 8,  'minimum_stock' => 2,  'unit' => 'jg'],
            ['code' => 'VELA-IGN-001',  'name' => 'Vela de Ignição',              'unit_price' => 22.00, 'stock_quantity' => 16, 'minimum_stock' => 4,  'unit' => 'un'],

            // Estoque baixo (abaixo do mínimo)
            ['code' => 'CORREIA-D-001', 'name' => 'Correia Dentada',              'unit_price' => 120.00,'stock_quantity' => 1,  'minimum_stock' => 3,  'unit' => 'un'],
            ['code' => 'AMORT-TRA-001', 'name' => 'Amortecedor Traseiro',         'unit_price' => 180.00,'stock_quantity' => 1,  'minimum_stock' => 2,  'unit' => 'un'],

            // Sem estoque (força fluxo de compras)
            ['code' => 'PAST-FREIO-T',  'name' => 'Pastilha de Freio Traseira',  'unit_price' => 95.00, 'stock_quantity' => 0,  'minimum_stock' => 2,  'unit' => 'jg'],
            ['code' => 'FILTRO-CB-001', 'name' => 'Filtro de Combustível',       'unit_price' => 55.00, 'stock_quantity' => 0,  'minimum_stock' => 2,  'unit' => 'un'],
            ['code' => 'TENSOR-001',    'name' => 'Tensor de Correia',            'unit_price' => 140.00,'stock_quantity' => 0,  'minimum_stock' => 1,  'unit' => 'un'],
        ];

        foreach ($parts as $part) {
            PartModel::updateOrCreate(['code' => $part['code']], $part);
        }
    }
}
