<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('cancelled_at')->nullable()->after('total_amount');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')
                ->constrained('users')->restrictOnDelete();
            $table->string('cancel_reason', 255)->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancelled_at', 'cancel_reason']);
        });
    }
};