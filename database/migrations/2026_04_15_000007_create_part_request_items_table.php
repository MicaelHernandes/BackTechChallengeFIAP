<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('part_request_id')->constrained('part_requests')->cascadeOnDelete();
            $table->foreignId('part_id')->constrained('parts');
            $table->string('part_name', 150)->comment('Snapshot do nome da peça no momento da solicitação');
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_provided')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_request_items');
    }
};
