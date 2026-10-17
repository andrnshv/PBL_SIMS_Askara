<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_merge_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('target_name_snapshot');
            // daftar varian sumber yang digabung, contoh:
            // [{"variant_id":5,"name":"...","sku":"...","stock_transferred":10}]
            $table->json('sources');
            $table->text('reason')->nullable();
            $table->foreignId('merged_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('merged_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_merge_logs');
    }
};
