<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('category', 100)->index();
            $table->date('receipt_date')->index();
            $table->string('receipt_number', 100)->index();
            $table->timestamps();
        });
        Schema::create('document_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->text('caption');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_photos');
        Schema::dropIfExists('documents');
    }
};
