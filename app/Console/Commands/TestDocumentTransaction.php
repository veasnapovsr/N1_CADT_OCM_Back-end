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
    
        /**
         * កំណត់យកចំនួនជំហ៊ាននៃការរត់ឯកសារ
         */
        $steps = rand( 5 , 20 );
        echo 'There are ' . $steps . ' steps' . PHP_EOL;
        /**
         * កំណត់ការចាប់ផ្ដើម
         */
        $start = 1 ;
        $previous_transaction = null ;
        do{
            /**
             * សិក្សាពីលក្ខណនៃការចាប់ផ្ដើមដំបូង។ ដែល previous_transaction_id ស្នើរ null
             */
            echo "Start : " . $start . PHP_EOL;

            $sender = $randomUser = \DB::table('users')
                ->inRandomOrder()
                ->first();

            $receivers = $randomUser = \DB::table('users')
                ->inRandomOrder()
                ->whereNotIn( 'id' , [$sender->id] )
                ->limit( rand(1, 10 ) )
                ->get();

            $document = \App\Models\Document\Document::create([
                'title' => 'ចំណងជើងឯកសារ ' . $start ,
                'word_file' => 'path_to_word file' ,
                'pdf_file' => 'path_to_word file' ,
                'created_by' => $sender->id ,
                'updated_by' => $sender->id ,
                'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
            ]);

            /**
             * បង្កើតប្រតិបត្តិការបញ្ជូនឯកសារ
             */ 
            $transaction = \App\Models\Document\Transaction::create([
                'document_id' => $document->id ,
                'subject' => "ប្រធានបទនៃការបញ្ជូនឯកសារ " . $start ,
                'sent_at' => \Carbon\Carbon::now()->format("Y-m-d H:i:s") ,
                'previous_transaction_id' => $previous_transaction != null ? $previous_transaction->id : null , // The first step of document transaction/flow
                'sender_id' => $sender->id ,
                'next_transaction_id' => null ,
                'created_by' => $sender->id ,
                'updated_by' => $sender->id ,
                'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
            ]);


            /**
             * បន្ថែមកំណត់បង្ហាញទៅលើឯកសាដែលដាក់បញ្ជូនទៅ
             */
            $document->briefings()->create([
                'document_id' => $document->id ,
                'briefing' => 'ខ្លឹមសានៃកំណត់បង្ហាញ ' . $start ,
                'created_by' => $sender->id ,
                'updated_by' => $sender->id ,
                'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
            ]);

            /**
             * ភ្ជាប់ហត្ថលេខាសង្ខេប
             */
            $document->shortSignatures()->create([
                'document_id' => $document->id ,
                'user_id' => $sender->id
            ]);
            /**
             * ភ្ជាប់ហត្ថលេខាផ្លូវការចុងក្រោយ
             */
            $document->signatures()->create([
                'document_id' => $document->id ,
                'user_id' => $sender->id
            ]);
            
            foreach( $receivers AS $receiver ){
                $transaction->receivers()->create([
                    'document_transaction_id' => $transaction->id ,
                    'receiver_id' => $receiver->id ,
                    'seen_at' => null ,
                    'is_download' => null ,
                    'is_preview' => null
                ]);
            }

            if( $previous_transaction != null ){
                $previous_transaction->next_transaction_id = $transaction->id;
                $previous_transaction->save();
            }

            $previous_transaction = $transaction ;
            echo "FINISHED " . $start  . PHP_EOL;
            $start++;
            
        }while( $start <= $steps );

    }
}
