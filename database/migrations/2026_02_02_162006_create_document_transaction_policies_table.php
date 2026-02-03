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
        Schema::create('document_transaction_policies', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name')->comment('Name of organization');
            $table->text('keyname')->nullable()->comment('keyname use to diffentiate from others');
            $table->text('desp')->nullable()->comment('description of organization');
            $table->text('tpid')->nullable()->comment('The id of the parent which identify the whole type of them.');
            $table->integer('pid')->nullable()->comment('Parent id of this organization');
            $table->text('cids')->nullable()->comment('Children IDs will be store here.');
            $table->string('image', 191)->nullable();
            $table->integer('record_index')->nullable()->comment('The index of the record');
            $table->integer('organization_structure_id')->nullable(true)->default(0);
            $table->integer('officer_id')->nullable(true)->default(0);
            $table->integer('active')->nullable()->comment('The activation of the record');
            $table->integer('created_by')->nullable(true);
            $table->integer('updated_by')->nullable(true);
            $table->integer('deleted_by')->nullable(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_transaction_policies');
    }
};
