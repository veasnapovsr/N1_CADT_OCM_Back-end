<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('officer_jobs', function (Blueprint $table) {
            $table->id();
            $table->integer('organization_structure_position_id')->nullable(false);
            $table->integer('unofficial_position_id')->nullable(true)->default(0);
            $table->integer('officer_id')->nullable(false);
            $table->integer('countesy_id')->nullable(false);
            $table->string('email',191)->nullable(true);
            $table->string('start',50)->nullable(false);
            $table->string('end',50)->nullable(true);
            $table->integer('created_by')->nullable(true);
            $table->integer('updated_by')->nullable(true);
            $table->integer('deleted_by')->nullable(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('officer_jobs');
    }
};
