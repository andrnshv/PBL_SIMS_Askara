<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('movement_type'); // sale, receipt, opname_adjustment, merge, initial, ...
            $table->integer('qty_change');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['variant_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('movement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
