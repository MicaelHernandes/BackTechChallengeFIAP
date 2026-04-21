<?php

namespace Database\Seeders;

use Domain\Customer\Infrastructure\Models\CustomerModel;
use Domain\Customer\Infrastructure\Models\VehicleModel;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\WithFaker;

class CustomerSeeder extends Seeder
{
    use WithFaker;

    public function run(): void
    {
        $customers = [
            [
                'name'     => 'João da Silva',
                'document' => fake()->cpf(false),   // CPF válido
                'email'    => 'joao.silva@email.com',
                'phone'    => '(11) 98765-4321',
                'address'  => 'Rua das Flores, 123 - São Paulo/SP',
                'vehicles' => [
                    ['plate' => 'ABC1D23', 'brand' => 'Toyota',      'model' => 'Corolla',     'year' => 2020, 'color' => 'Prata'],
                    ['plate' => 'XYZ9876', 'brand' => 'Honda',       'model' => 'Civic',       'year' => 2018, 'color' => 'Preto'],
                ],
            ],
            [
                'name'     => 'Maria Oliveira',
                'document' => fake()->cpf(false),
                'email'    => 'maria.oliveira@email.com',
                'phone'    => '(21) 91234-5678',
                'address'  => 'Av. Copacabana, 456 - Rio de Janeiro/RJ',
                'vehicles' => [
                    ['plate' => 'DEF2E34', 'brand' => 'Volkswagen', 'model' => 'Gol',         'year' => 2019, 'color' => 'Branco'],
                ],
            ],
            [
                'name'     => 'Carlos Mendes',
                'document' => fake()->cpf(false),
                'email'    => 'carlos.mendes@email.com',
                'phone'    => '(31) 99876-5432',
                'address'  => 'Rua dos Andradas, 789 - Belo Horizonte/MG',
                'vehicles' => [
                    ['plate' => 'GHI3F45', 'brand' => 'Chevrolet',  'model' => 'Onix',        'year' => 2021, 'color' => 'Vermelho'],
                    ['plate' => 'JKL4G56', 'brand' => 'Ford',       'model' => 'Ka',          'year' => 2017, 'color' => 'Azul'],
                ],
            ],
            [
                'name'     => 'Ana Souza',
                'document' => fake()->cpf(false),
                'email'    => 'ana.souza@email.com',
                'phone'    => '(41) 98888-7777',
                'address'  => 'Rua XV de Novembro, 101 - Curitiba/PR',
                'vehicles' => [
                    ['plate' => 'MNO5H67', 'brand' => 'Fiat',       'model' => 'Argo',        'year' => 2022, 'color' => 'Cinza'],
                ],
            ],
            [
                'name'     => 'Roberto Lima',
                'document' => fake()->cpf(false),
                'email'    => 'roberto.lima@email.com',
                'phone'    => '(51) 97654-3210',
                'address'  => 'Av. Ipiranga, 202 - Porto Alegre/RS',
                'vehicles' => [
                    ['plate' => 'PQR6I78', 'brand' => 'Renault',    'model' => 'Sandero',     'year' => 2018, 'color' => 'Laranja'],
                    ['plate' => 'STU7J89', 'brand' => 'Hyundai',    'model' => 'HB20',        'year' => 2020, 'color' => 'Branco'],
                ],
            ],
            [
                'name'     => 'Fernanda Costa',
                'document' => fake()->cpf(false),
                'email'    => 'fernanda.costa@email.com',
                'phone'    => '(61) 96543-2109',
                'address'  => 'SQN 312, Bloco B - Brasília/DF',
                'vehicles' => [
                    ['plate' => 'VWX8K90', 'brand' => 'Jeep',       'model' => 'Compass',     'year' => 2021, 'color' => 'Preto'],
                ],
            ],
            [
                'name'     => 'Marcos Pereira',
                'document' => fake()->cpf(false),
                'email'    => 'marcos.pereira@email.com',
                'phone'    => '(71) 95432-1098',
                'address'  => 'Av. Sete de Setembro, 303 - Salvador/BA',
                'vehicles' => [
                    ['plate' => 'YZA9L01', 'brand' => 'Nissan',     'model' => 'Kicks',       'year' => 2019, 'color' => 'Prata'],
                    ['plate' => 'BCD1M12', 'brand' => 'Peugeot',    'model' => '208',         'year' => 2023, 'color' => 'Azul'],
                ],
            ],
            [
                'name'     => 'Luciana Alves',
                'document' => fake()->cpf(false),
                'email'    => 'luciana.alves@email.com',
                'phone'    => '(81) 94321-0987',
                'address'  => 'Rua da Aurora, 404 - Recife/PE',
                'vehicles' => [
                    ['plate' => 'EFG2N23', 'brand' => 'Kia',        'model' => 'Stinger',     'year' => 2022, 'color' => 'Verde'],
                ],
            ],
            [
                'name'     => 'Paulo Ferreira',
                'document' => fake()->cpf(false),
                'email'    => 'paulo.ferreira@email.com',
                'phone'    => '(85) 93210-9876',
                'address'  => 'Av. Beira Mar, 505 - Fortaleza/CE',
                'vehicles' => [
                    ['plate' => 'HIJ3O34', 'brand' => 'Mitsubishi',  'model' => 'Outlander',  'year' => 2020, 'color' => 'Branco'],
                    ['plate' => 'KLM4P45', 'brand' => 'Subaru',     'model' => 'Impreza',     'year' => 2017, 'color' => 'Azul'],
                ],
            ],
            [
                'name'     => 'Camila Santos',
                'document' => fake()->cpf(false),
                'email'    => 'camila.santos@email.com',
                'phone'    => '(91) 92109-8765',
                'address'  => 'Travessa da Paz, 606 - Belém/PA',
                'vehicles' => [
                    ['plate' => 'NOP5Q56', 'brand' => 'BMW',        'model' => 'X1',          'year' => 2021, 'color' => 'Cinza'],
                ],
            ],
        ];

        foreach ($customers as $customerData) {
            $vehicles = $customerData['vehicles'];
            unset($customerData['vehicles']);

            $customer = CustomerModel::updateOrCreate(
                ['document' => $customerData['document']],
                $customerData
            );

            foreach ($vehicles as $vehicleData) {
                VehicleModel::updateOrCreate(
                    ['plate' => $vehicleData['plate']],
                    array_merge($vehicleData, ['customer_id' => $customer->id])
                );
            }
        }
    }
}
