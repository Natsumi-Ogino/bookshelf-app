<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('isbn', 13)->nullable()->change();
            $table->date('published_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('books')
            ->whereNull('isbn')
            ->orWhereNull('published_date')
            ->exists()) {
            throw new RuntimeException(
                'Cannot make books.isbn and books.published_date required while NULL values exist.'
            );
        }

        Schema::table('books', function (Blueprint $table) {
            $table->string('isbn', 13)->nullable(false)->change();
            $table->date('published_date')->nullable(false)->change();
        });
    }
};
