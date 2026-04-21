<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_requests', function (Blueprint $table) {
            $table->id();
            $table->string('status', 30)->default('received');
            $table->foreignId('requested_by_user_id')->constrained('users');
            // os_id será adicionado como FK na Fase 4 (Workshop context)
            $table->unsignedBigInteger('os_id')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_requests');
    }
};
