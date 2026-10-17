<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('unit');
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->string('status')->default('active'); // active, inactive, merged
            $table->unsignedBigInteger('merged_into_variant_id')->nullable();
            $table->timestamps();

            $table->foreign('merged_into_variant_id')
                ->references('id')->on('product_variants')
                ->nullOnDelete();

            $table->index('name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
