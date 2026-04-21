<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique()->comment('Código/SKU da peça');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('stock_quantity')->default(0)->comment('Estoque físico disponível');
            $table->unsignedInteger('minimum_stock')->default(0)->comment('Estoque mínimo para alerta');
            $table->string('unit', 10)->default('un')->comment('Unidade de medida: un, lt, kg, m...');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
