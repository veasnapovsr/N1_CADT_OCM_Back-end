<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestDocumentTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-document-transaction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $sender = $randomUser = DB::table('users')
            ->inRandomOrder()
            ->first();
        
        \App\Models\Document\Document::create([
            'title' => '' ,
            'word_file' => '' ,
            'pdf_file' => '' ,
            'created_by' => '' ,
            'updated_by' => '' ,
            'created_at' => '' ,
            'updated_at' => ''
        ]);
    }
}
