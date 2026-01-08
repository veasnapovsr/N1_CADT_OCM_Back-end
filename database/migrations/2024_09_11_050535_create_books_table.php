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
        Schema::create('books', function (Blueprint $table) {
            $table->increments('id');
            $table->text('title')->comment('The title of the book');
            $table->text('description')->nullable();
            $table->string('color', 191)->default('#FAFAFA')->comment('The color of the book');
            $table->string('cover', 191)->nullable()->comment('The cover of the book');
            $table->integer('complete')->default(0)->comment('the status identify that the book has been finished the data entry or not');
            $table->integer('active')->default(0)->comment('the status identify that the book has been published');
            $table->integer('created_by')->nullable(true);
            $table->integer('updated_by')->nullable(true);
            $table->integer('deleted_by')->nullable(true);
            $table->string('pdf', 191)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
