<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_items', function (Blueprint $table) {
            $table->id();
            $table->string('batch_name'); // nama file/batch impor dari Accurate
            $table->string('accurate_code');
            $table->string('accurate_name');
            $table->string('accurate_unit')->nullable();
            $table->integer('accurate_stock')->default(0);
            $table->string('mapping_status')->default('unmapped'); // unmapped, mapped, verified, rejected
            $table->foreignId('mapped_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index('batch_name');
            $table->index('accurate_code');
            $table->index('mapping_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_items');
    }
};
