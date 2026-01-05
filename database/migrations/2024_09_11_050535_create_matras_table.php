<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matras', function (Blueprint $table) {
            $table->increments('id');
            $table->text('number');
            $table->text('title');
            $table->text('meaning');
            $table->integer('book_id')->default(0);
            $table->integer('kunty_id')->default(0);
            $table->integer('matika_id')->default(0);
            $table->integer('chapter_id')->default(0);
            $table->integer('part_id')->default(0);
            $table->integer('section_id')->default(0);
            $table->integer('created_by')->nullable(true);
            $table->integer('updated_by')->nullable(true);
            $table->integer('deleted_by')->nullable(true);
            $table->integer('active')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matras');
    }
};
