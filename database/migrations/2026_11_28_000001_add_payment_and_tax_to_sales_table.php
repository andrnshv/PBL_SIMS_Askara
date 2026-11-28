<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'subtotal_amount', 'tax_rate', 'tax_amount', 'amount_paid', 'change_amount',
    ];

    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Semua nullable agar transaksi lama tetap valid. Dicek dulu supaya aman diulang.
            if (! Schema::hasColumn('sales', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 15, 2)->nullable()->after('status');
            }
            if (! Schema::hasColumn('sales', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->nullable()->after('subtotal_amount');
            }
            if (! Schema::hasColumn('sales', 'tax_amount')) {
                $table->decimal('tax_amount', 15, 2)->nullable()->after('tax_rate');
            }
            if (! Schema::hasColumn('sales', 'amount_paid')) {
                $table->decimal('amount_paid', 15, 2)->nullable()->after('total_amount');
            }
            if (! Schema::hasColumn('sales', 'change_amount')) {
                $table->decimal('change_amount', 15, 2)->nullable()->after('amount_paid');
            }
        });
    }

    public function down(): void
    {
        $existing = array_filter($this->columns, fn ($c) => Schema::hasColumn('sales', $c));

        if ($existing) {
            Schema::table('sales', fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }
};