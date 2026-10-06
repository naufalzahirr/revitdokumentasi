<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            $table->timestamps();
        });
        Schema::table('document_photos', function (Blueprint $table) {
            $table->foreignId('document_item_id')->nullable()->constrained()->cascadeOnDelete();
        });
        DB::table('documents')->whereExists(function ($query) {
            $query->selectRaw('1')->from('document_photos')->whereColumn('document_photos.document_id', 'documents.id');
        })->orderBy('id')->chunkById(100, function ($documents) {
            foreach ($documents as $document) {
                $itemId = DB::table('document_items')->insertGetId([
                    'document_id' => $document->id, 'name' => 'Dokumentasi sebelumnya', 'created_at' => now(), 'updated_at' => now(),
                ]);
                DB::table('document_photos')->where('document_id', $document->id)->update(['document_item_id' => $itemId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('document_item_id');
        });
        Schema::dropIfExists('document_items');
    }
};
