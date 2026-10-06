<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('voucher_number', 100)->nullable();
            $table->string('received_from', 200)->nullable();
            $table->unsignedBigInteger('amount')->nullable();
            $table->text('purpose')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_place', 100)->nullable();
            $table->string('approver_title', 200)->nullable();
            $table->string('approver_name', 150)->nullable();
            $table->string('approver_nip', 32)->nullable();
            $table->string('treasurer_name', 150)->nullable();
            $table->string('treasurer_nip', 32)->nullable();
            $table->string('recipient_name', 150)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['voucher_number', 'received_from', 'amount', 'purpose', 'payment_date',
                'payment_place', 'approver_title', 'approver_name', 'approver_nip',
                'treasurer_name', 'treasurer_nip', 'recipient_name']);
        });
    }
};
