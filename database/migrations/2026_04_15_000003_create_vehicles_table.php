<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('plate', 8)->unique()->comment('Placa sem hífen, ex: ABC1234 ou ABC1D23');
            $table->string('brand', 60);
            $table->string('model', 100);
            $table->unsignedSmallInteger('year');
            $table->string('color', 40);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
