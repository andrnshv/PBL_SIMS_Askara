<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengganti legacy_items (sudah di-drop oleh 2026_11_03_044436_drop_table_legacy_items).
     * Data Accurate disimpan apa adanya (staging) dan TIDAK mengubah products,
     * product_variants, maupun stok sampai dipetakan dan diverifikasi.
     */
    public function up(): void
    {
        Schema::create('accurate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('accurate_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number'); // nomor baris di file asal, untuk penelusuran

            // Data mentah dari Accurate
            $table->string('accurate_code', 100);
            $table->string('accurate_name');
            $table->string('accurate_unit', 50)->nullable();
            $table->integer('accurate_stock')->default(0);
            $table->decimal('accurate_price', 15, 2)->nullable();
            $table->string('accurate_category')->nullable();

            // Pemetaan & verifikasi: unmapped -> mapped -> verified (atau rejected)
            $table->string('mapping_status', 20)->default('unmapped');
            $table->foreignId('mapped_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->foreignId('mapped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mapped_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('rejected_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'accurate_code']);
            $table->index('accurate_code');
            $table->index('mapping_status');
            $table->index('mapped_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accurate_items');
    }
};