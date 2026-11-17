<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accurate_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->char('file_hash', 64); // SHA-256 isi file, mencegah file yang sama diimpor dua kali
            $table->unsignedInteger('total_rows')->default(0);    // baris data di file (tanpa header & baris kosong)
            $table->unsignedInteger('imported_rows')->default(0); // baris valid yang tersimpan
            $table->unsignedInteger('failed_rows')->default(0);   // baris ditolak saat impor
            $table->json('error_report')->nullable();             // [{row, code, message}], maks. 500 entri
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('file_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accurate_import_batches');
    }
};