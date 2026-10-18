<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draft opname boleh menyimpan varian yang belum dihitung,
     * jadi physical_qty dan difference harus boleh NULL.
     * Saat opname diselesaikan, keduanya selalu terisi.
     */
    public function up(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->integer('physical_qty')->nullable()->change();
            $table->integer('difference')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->integer('physical_qty')->nullable(false)->change();
            $table->integer('difference')->nullable(false)->change();
        });
    }
};
