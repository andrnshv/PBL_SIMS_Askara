<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_no')->unique();
            $table->date('opname_date');
            $table->string('status')->default('draft'); // draft, completed, cancelled
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
