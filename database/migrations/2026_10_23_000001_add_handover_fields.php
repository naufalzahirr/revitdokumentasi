<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('handover_number', 50)->nullable();
            $table->date('handover_date')->nullable();
            $table->string('order_number', 50)->nullable();
            $table->date('order_date')->nullable();
            $table->string('supplier_name', 150)->nullable();
        });
        Schema::table('document_items', function (Blueprint $table) {
            $table->decimal('ordered_quantity', 12, 3)->nullable();
            $table->decimal('received_good_quantity', 12, 3)->nullable();
            $table->decimal('received_damaged_quantity', 12, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            $table->dropColumn(['ordered_quantity', 'received_good_quantity', 'received_damaged_quantity']);
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['handover_number', 'handover_date', 'order_number', 'order_date', 'supplier_name']);
        });
    }
};
