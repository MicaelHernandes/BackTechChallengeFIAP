<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part_requests', function (Blueprint $table) {
            $table->foreign('os_id')->references('id')->on('order_services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('part_requests', function (Blueprint $table) {
            $table->dropForeign(['os_id']);
        });
    }
};
