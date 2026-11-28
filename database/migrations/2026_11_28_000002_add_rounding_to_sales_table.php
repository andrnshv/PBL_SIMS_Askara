<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'rounding_amount')) {
                // Selisih pembulatan; bisa negatif. Nullable agar transaksi lama tetap valid.
                $table->decimal('rounding_amount', 15, 2)->nullable()->after('tax_amount');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'rounding_amount')) {
            Schema::table('sales', fn (Blueprint $table) => $table->dropColumn('rounding_amount'));
        }
    }
};