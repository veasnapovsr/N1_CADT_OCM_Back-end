<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_briefings', function (Blueprint $table) {
            if (!Schema::hasColumn('document_briefings', 'document_transaction_id')) {
                $table->integer('document_transaction_id')
                    ->nullable()
                    ->default(0)
                    ->after('document_id')
                    ->comment('Workflow transaction when the comment was added');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_briefings', function (Blueprint $table) {
            if (Schema::hasColumn('document_briefings', 'document_transaction_id')) {
                $table->dropColumn('document_transaction_id');
            }
        });
    }
};
