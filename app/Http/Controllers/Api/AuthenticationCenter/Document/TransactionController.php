<?php

namespace App\Http\Controllers\Api\AuthenticationCenter\Document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document\Transaction as RecordModel;
use App\Http\Controllers\CrudController;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class TransactionController extends Controller
{
    private $selectFields = [
        'id',
        'document_id' ,
        'sender_id' ,
        'subject' ,
        'sent_at' ,
        'date_in' ,
        'status' ,
        'previous_transaction_id' ,
        'next_transaction_id' ,
        'tpid' ,
        'created_at' , 
        'updated_at' ,
        'created_by' ,
        'updated_by'
    ];

    /**
     * Listing function
     */
    public function index(Request $request){
        $user = \Auth::user() != null ? \Auth::user() : false ;

        /** Format from query string */
        $search = isset( $request->search ) && $request->search !== "" ? $request->search : false ;
        $perPage = isset( $request->perPage ) && $request->perPage !== "" ? $request->perPage : 10 ;
        $page = isset( $request->page ) && $request->page !== "" ? $request->page : 1 ;

        /**
         * Filter conditions
         */
        $sender_id = isset( $request->sender_id ) && intval( $request->sender_id ) > 0 ? $request->sender_id : false ;
        $date = isset( $request->date ) & strlen( $request->date ) >=10 ? \Carbon\Carbon::parse( $request->date ) : false ;
        $status = isset( $request->status ) & strlen( $request->status ) >3 
            ? (
                in_array( $request->status , RecordModel::STATUSES ) 
                    ? $request->status 
                    : false
            )
            : false ;

        $queryString = [
            "where" => [
                'default' => [
                    $status != false
                        ? 
                            [
                                'field' => 'status' ,
                                'value' => $status
                            ] 
                        : 
                        [
                            'field' => 'status' ,
                            'value' => null
                        ]
                ],
                // 'in' => [
                //     [
                //         'field' => 'type' ,
                //         'value' => isset( $request->type ) && $request->type !== null ? [$request->type] : false
                //     ]
                // ] ,
                // 'not' => [
                //     [
                //         'field' => 'type' ,
                //         'value' => [4]
                //     ]
                // ] ,
                // 'like' => [
                //     $date != false
                //         ? [
                //             'field' => 'date_in' ,
                //             'value' => $date->format('Y-m-d')
                //         ] : [] 
                // ]
            ] ,
            // "pivots" => [
            //     $search != false ?
            //     [
            //         "relationship" => 'sender',
            //         "where" => [
            //             // "in" => [
            //             //     "field" => "id",
            //             //     "value" => [$request->unit]
            //             // ],
            //             // "not"=> [
            //             //     [
            //             //         "field" => 'fieldName' ,
            //             //         "value"=> 'value'
            //             //     ]
            //             // ],
            //             "like"=>  [
            //                 [
            //                     "field"=> 'firstname' ,
            //                     "value"=> $search
            //                 ],
            //                 [
            //                     "field"=> 'lastname' ,
            //                     "value"=> $search
            //                 ]
            //             ]
            //         ]
            //     ]
            //     : []
            // ],
            "pagination" => [
                'perPage' => $perPage,
                'page' => $page
            ],
            "search" =>
                $search === false ? [] : [
                    'value' => $search ,
                    'fields' => [
                        'date_in'
                    ] 
                ]
            ,
            "order" => [
                'field' => 'date_in' ,
                'by' => 'desc'
            ],
        ];

        $request->merge( $queryString );

        $crud = new CrudController(new RecordModel(), $request, $this->selectFields);

        $crud->setRelationshipFunctions([
            /** relationship name => [ array of fields name to be selected ] */
            'document' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' , 
                'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                'editor' => [ 'id' , 'firstname' , 'lastname' ]
            ] ,
            'sender' => [ 
                'id' , 'firstname' , 'lastname' ,
                'officer' => [ 
                        'id' , 'code' ,
                        // people => [ 'id' , 'firstname' , 'lastname' ]
                ] 
            ] ,
            'receivers' => [ 'id' , 'firstname' , 'lastname'  ],
            
            'previous' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' , 
                'document' => [
                   'id' , 'objective' , 'word_file' , 'pdf_file' , 
                //    'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                //    'editor' => [ 'id' , 'firstname' , 'lastname' ]
                ] ,
                'sender' => [ 'id' , 'firstname' , 'lastname' ] ,
                'receivers' => [ 'id' , 'firstname' , 'lastname'  ],
            ],
            'next' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' , 
                'document' => [
                    'id' , 'objective' , 'word_file' , 'pdf_file' , 
                    // 'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                    // 'editor' => [ 'id' , 'firstname' , 'lastname' ]
                ] ,
                'sender' => [ 'id' , 'firstname' , 'lastname' ] ,
                'receivers' => [ 'id' , 'firstname' , 'lastname'  ],
            ],
        ]);

        $builder = $crud->getListBuilder();

        $builder->whereNull('previous_transaction_id');

        $responseData = $crud->pagination(true, $builder);
        $responseData['records'] = $responseData['records']->map(function($record){
            if( $record['sender']['officer'] != null ){
                $officer = \App\Models\Officer\Officer::find( $record['sender']['officer']['id'] );
                $record['sender']['officer']['people'] = $officer->people;
                $record['sender']['officer']['jobs'] = $officer->jobs->map(function($job){
                    $job->countesy;
                    if( $job->organizationStructurePosition != null ){
                        $job->organizationStructurePosition->position;
                        if( $job->organizationStructurePosition->organizationStructure != null ){
                            $job->organizationStructurePosition->organizationStructure->organization;
                        }
                    }
                    return $job;
                });
                return $record;
            }
        });
        $responseData['message'] = __("crud.read.success");
        $responseData['ok'] = true ;
        return response()->json($responseData, 200);
    }
    public function read(Request $request){
        $user = \Auth::user() != null 
            ? \Auth::user()
            : (
                auth('api')->user() 
                    ? auth('api')->user()
                    : (
                        $request->user() != null
                            ? $request->user()
                            : 0
                    )
            );

        $record = intval( $request->id ) > 0 ? RecordModel::find( $request->id ) : null ;
        if( $record == null ){
            return response()->json([
                'ok' => false ,
                'record' => $record ,
                'message' => 'មិនមានព័ត៌មាននេះឡើយ។'
            ],403);
        }

        // ពិនិត្យមើលអ្នកដែលមានសិទ្ធិក្នុងការបើកឯកសារមើល
        if( ( $receiver = $record->receiversPivot()->where('receiver_id',$user->id)->first() ) != null ){
            // កត់ត្រាម៉ោងដែលបានចូលមើលដំបូងបង្អស់
            if( $receiver->seen_at == null || strlen( $receiver->seen_at ) <= 0 ){
                $receiver->update(['seen_at'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
            }
        }else{
            return response()->json([
                'ok' => false ,
                'message' => 'អ្នកមិនមានសិទ្ធិក្នុងប្រតិបត្តិការនេះទេ។'
            ],403);
        }
        
        $crud = new CrudController(new RecordModel(), $request, $this->selectFields);

        $crud->setRelationshipFunctions([
            /** relationship name => [ array of fields name to be selected ] */
            'document' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' , 
                'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                'editor' => [ 'id' , 'firstname' , 'lastname' ]
            ] ,
            'sender' => [ 
                'id' , 'firstname' , 'lastname' ,
                'officer' => [ 
                        'id' , 'code'
                ] 
            ] ,
            'receivers' => [ 'id' , 'firstname' , 'lastname'  ],
            
            'previous' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' , 
                'document' => [
                   'id' , 'objective' , 'word_file' , 'pdf_file' , 
                //    'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                //    'editor' => [ 'id' , 'firstname' , 'lastname' ]
                ] ,
                'sender' => [ 'id' , 'firstname' , 'lastname' ] ,
                'receivers' => [ 'id' , 'firstname' , 'lastname'  ],
            ],
            'next' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' , 
                'document' => [
                    'id' , 'objective' , 'word_file' , 'pdf_file' , 
                    // 'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                    // 'editor' => [ 'id' , 'firstname' , 'lastname' ]
                ] ,
                'sender' => [ 'id' , 'firstname' , 'lastname' ] ,
                'receivers' => [ 'id' , 'firstname' , 'lastname'  ],
            ],
        ]);

        $builder = $crud->getListBuilder();

        $builder->where('id' , $record->id );

        $responseData = $crud->pagination(true, $builder);
        $responseData['records'] = $responseData['records']->map(function($record){
            if( $record['sender']['officer'] != null ){
                $officer = \App\Models\Officer\Officer::find( $record['sender']['officer']['id'] );
                $record['sender']['officer']['people'] = $officer->people;
                $record['sender']['officer']['jobs'] = $officer->jobs->map(function($job){
                    $job->countesy;
                    if( $job->organizationStructurePosition != null ){
                        $job->organizationStructurePosition->position;
                        if( $job->organizationStructurePosition->organizationStructure != null ){
                            $job->organizationStructurePosition->organizationStructure->organization;
                        }
                    }
                    return $job;
                });
                return $record;
            }
        });

        return response()->json([
            'message' => __("crud.read.success") ,
            'record' => $responseData['records']->first() ,
            'ok' => true
        ], 200);
    }

    public function store(Request $request){
        $receivers = explode(',',$request->receivers);
        $organizations = explode(',',$request->organizations);
        if( 
            ( is_array( $receivers) && empty( $receivers ) ) ||
            ( is_array( $organizations) && empty( $organizations ) )
        ){
            // ក្នុងករណី អ្នកទទួលមាន ឬ អង្គភាពទទួលមាន មានន័យថាបញ្ជូនចេញ
            return $this->send( $request );
        }else{
            return $this->storeDraft( $request );
        }
    }

    public function storeDraft(Request $request){
        /**
         * បង្កើតប្រតិបត្តិការដឹកជញ្ជូនឯកសារ
         */
        // ត្រួតពិនិត្យម៉ោងឯកសារចូល
        $dateIn = strlen( trim($request->date_in) ) > 0 ? \Carbon\Carbon::parse( trim($request->date_in) ) : false ;
        if( $dateIn == false ){
            return response()->json([
                'ok' => false ,
                'message' => "សូមបញ្ចូលកាលបរិច្ឆេទបញ្ចូលឯកសារឱ្យបានត្រឹមត្រូវ។"
            ],422);
        }
        // ត្រួតពិនិ្យប្រធានបទនៃការបញ្ជូនឯកសារ
        $subject = strlen( trim($request->subject) ) > 0 ? trim($request->subject) : false ;
        if( $subject == false ){
            return response()->json([
                'ok' => false ,
                'message' => "សូមបញ្ចូលខ្លឹមសារនៃឯកសារឱ្យបានត្រឹមត្រូវ។"
            ],422);
        }
        // ត្រួតពិនិត្យលេខអ្នកបញ្ជូនឯកសារ
        $sender = \Auth::user();

        $transaction = RecordModel::create([
            'document_id' => null ,
            'sender_id' => $sender->id ,
            'subject' => $subject ,
            'date_in' => $dateIn->format('Y-m-d H:i:s') ,
            
            'status' => 'draft' ,
            'created_by' => $sender->id ,
            'updated_by' => $sender->id ,
            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
        ]);

        /**
         * បង្កើតឯកសាររួចភ្ជាប់ជាមួយឯកសារដែលបានបញ្ជូនមក
         */
        // ត្រួតពិនិត្យលេខឯកសារ    
        $number = strlen( trim($request->number) ) > 0 ? trim($request->number) : false ;
        if( $number == false ){
            return response()->json([
                'ok' => false ,
                'message' => "សូមបញ្ចូលលេខឯកសារឱ្យបានត្រឹមត្រូវ។"
            ],422);
        }
        $public_key = md5( $number . ( $dateIn != false ? $dateIn->format('YmdHis') : '' ) );
        // ត្រួតពិនិត្យខ្លឹមសារឯកសារ
        $objective = strlen( trim($request->objective) ) > 0 ? trim($request->objective) : false ;
        if( $objective == false ){
            return response()->json([
                'ok' => false ,
                'message' => "សូមបញ្ចូលខ្លឹមសារនៃឯកសារឱ្យបានត្រឹមត្រូវ។"
            ],422);
        }
        //​ បង្កើតឯកសារ
        $document = \App\Models\Document\Document::create([
            'public_key' => $public_key ,
            'number' => $number ,
            'objective' => $objective ,
            'created_by' => $sender->id ,
            'updated_by' => $sender->id ,
            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
        ]);
        // ភ្ជាប់ឯកសារទៅកាន់ការបញ្ជូន
        $transaction->update(['document_id'=>$document->id]);
        // ភ្ជាប់អ្នកទទួលបើសិនមាន
        $receivers = strlen( trim($request->receivers) ) > 0 ? explode(',',trim($request->receivers)) : [] ;
        if( !empty( $receivers) ){
            $receivers = \App\Models\User::whereIn('id', $receivers )->get();
            foreach( $receivers AS $receiver ){
                $transaction->receivers()->create([
                    'document_transaction_id' => $transaction->id ,
                    'receiver_id' => $receiver->id ,
                    'seen_at' => null ,
                    'is_download' => null ,
                    'is_preview' => null ,
                    'created_by' => $sender->id ,
                    'updated_by' => $sender->id ,
                    'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
                    'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
                ]);
            }
        }
    }
    public function changeReceiver(Request $request){
        // ត្រួតពិនិត្យប្រតិបត្តិការបញ្ជូន
        $transaction = intval( $request->transaction_id ) > 0 ? RecordModel::find( $request->transaction_id ) : null ;
        if( $transaction == null ){
            return response()->json([
                'ok' => false ,
                'message' => 'ប្រតិបត្តិការបញ្ជូនមិនមានឡើយ។' 
            ],422);
        }
        $receiver = intval( $request->receiver_id ) > 0 ? \App\Models\User::find( $request->receiver_id ) : null ;
        if( $receiver == null ){
             return response()->json([
                'ok' => false ,
                'message' => "អត្តសញ្ញាណអ្នកទទួលឯកសារមិនមាន។"
            ],422);
        }
        if( ( $transactionReceiver = $transaction->receiversPivot()->where('receiver_id',$receiver->id)->first() ) != null ){
            // លុបចោល
            $transactionReceiver->deleted_at = \Carbon\Carbon::now()->format("Y-m-d H:i:s");
            $transactionReceiver->deleted_by = \Auth::user()->id ;
            $transactionReceiver->save();
        }
        if( ( $transactionReceiver = $transaction->receiversPivot()->where('receiver_id',$receiver->id)->withTrashed()->first() ) != null ){
            $transactionReceiver->restore();
        }
        else{
            $receiver = $transaction->receivers()->create([
                'document_transaction_id' => $transaction->id ,
                'receiver_id' => $receiver->id ,
                'seen_at' => null ,
                'is_download' => null ,
                'is_preview' => null ,
                'created_by' => $sender->id ,
                'updated_by' => $sender->id ,
                'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
                'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
            ]);
        }
        return response()->json([
            'ok' => true ,
            'record' => $receiver ,
            'message' => 'ជោគជ័យ'
        ],200);
    }
    public function send(Request $request){
        /**
         * មុនពេលបញ្ជូនឯកសារចេញត្រូវពិនិត្យការងារមួយចំនួនដូចជា៖
         * ១. ឯកសារត្រូវភ្ជាប់ជាមួយ អាចមានជា Word ឬ PDF ឬទាំង ២
         * ២. ត្រូវមានអ្នកទទួលយ៉ាងតិច ម្នាក់
         * ៣. និងត្រូវចុះពេលវេលាផងដែរ ដោយស្វ័យប្រវត្ត
         */

        // ត្រួតពិនិត្យប្រតិបត្តិការបញ្ជូន
        $transaction = intval( $request->transaction_id ) > 0 ? RecordModel::find( $request->transaction_id ) : null ;
        if( $transaction == null ){
            return response()->json([
                'ok' => false ,
                'message' => 'ប្រតិបត្តិការបញ្ជូនមិនមានឡើយ។' 
            ],422);
        }
        // ត្រួតពិនិត្យឯកសារភ្ជាប់ជាមួយការបញ្ជូន
        if( 
            ( $transaction->document == null  ) ||
            (
                $transaction->document != null &&
                ( $transaction->document->word_file == null || strlen( $transaction->document->word_file ) <= 0 ) && 
                ( $transaction->document->pdf_file == null || strlen( $transaction->document->pdf_file ) <= 0 )
            )
        ){
            return response()->json([
                'ok' => false ,
                'message' => 'ប្រតិបត្តិការនេះមិនមានឯកសារយោងភ្ជាប់ជាមួយឡើយ។' 
            ],422);
        }
        // ត្រួតពិនិត្យអ្នកទទួលនៃការបញ្ជូន
        if( $transaction->receivers == null || ( $transaction->receivers instanceof Collection  && $transaction->receivers->count <= 0 ) ){
            return response()->json([
                'ok' => false ,
                'message' => 'ប្រតិបត្តិការបញ្ជូននេះមិនទាន់មានអ្នកទទួលឡើយ។' 
            ],422);
        }
        $transaction->send();
        // ជូនដំណឹងទៅអ្នកទទួល។ ការងារនេះនិងបន្តនៅពេលក្រោយ។
        return response()->json([
            'ok' => true ,
            'record' => $transaction ,
            'message' => 'ជោគជ័យ'
        ],200);
    }
    public function update(Request $request){
        /**
         * ការកែសម្រួលព័ត៌មានរបស់ប្រតិបត្តិការបញ្ជូនឯកសារអាចធ្វើទៅបានតែមួយចំនួនប៉ុណ្ណោះគឺ
         * ១. កាលបរិច្ឆេទឯកសារចូល
         * ២. ប្រធានបទ
         * ហើយក្នុងលក្ខណដែលឯកសារមិនទាន់ត្រូវបានបញ្ជូនចេញ
         */
        // ត្រួតពិនិត្យប្រតិបត្តិការបញ្ជូន
        $transaction = intval( $request->transaction_id ) > 0 ? RecordModel::find( $request->transaction_id ) : null ;
        if( $transaction == null ){
            return response()->json([
                'ok' => false ,
                'record' => $request->input() , 
                'message' => 'ប្រតិបត្តិការបញ្ជូនមិនមានឡើយ។' 
            ],422);
        }
        // ត្រួតពិនិត្យការបញ្ជូនចេញរបស់ឯកសារ
        if( $transaction->sent_at != null && strlen( trim( $transaction->sent_at ) ) > 0 ){
            return response()->json([
                'ok' => false ,
                'message' => 'ឯកសារបានបញ្ជូនចេញ។ មិនអាចកែប្រែព័ត៌មាននៃការបញ្ជូននេះបានទេ។' 
            ],403);
        }

        $transactionParams = [];

        // ប្រសិនបើប្រតិបត្តិការនេះមិនមែនជាប្រតិបត្តិការដំបូងបង្អស់ គឺត្រូវបិទការកែប្រែ ថ្ងៃចុះឯកសារ ដោយយកតាមប្រព័ន្ធ
        if( $transaction->previous_transaction_id == null ){
            // ត្រួតពិនិត្យម៉ោងឯកសារចូល
            $dateIn = strlen( trim($request->date_in) ) > 0 ? \Carbon\Carbon::parse( trim($request->date_in) ) : false ;
            if( $dateIn != false ){
                $transactionParams['date_in'] = $dateIn->format('Y-m-d H:i:s');
            }
        }
        // ត្រួតពិនិ្យប្រធានបទនៃការបញ្ជូនឯកសារ
        $subject = strlen( trim($request->subject) ) > 0 ? trim($request->subject) : false ;
        if( $subject != false ){
            $transactionParams['subject'] = $subject;
        }
        // កែប្រែតម្លែរបស់ date_in និង subject ប្រសិនបើមានការប្រែប្រួល
        if( !empty( $transactionParams ) ){
            $transaction->update($transactionParams);
        }
        $objective = strlen( trim($request->objective) ) > 0 ? trim($request->objective) : false ;
        if( $objective != false ){
            $transaction->document([
                'objective' => $objective
            ]);
        }
        return response()->json([
            'ok' => true ,
            'record' => $transaction ,
            'message' => 'ជោគជ័យ'
        ],200);
    }
    public function uploadWord(Request $request){
        $user = \Auth::user() != null 
            ? \Auth::user()
            : (
                auth('api')->user() 
                    ? auth('api')->user()
                    : (
                        $request->user() != null
                            ? $request->user()
                            : 0
                    )
            );
        if( $user ){
            $document = intval( $request->document_id ) > 0 ? \App\Models\Document\Document::find( $request->document_id) : null ;
            if( $document == null ){
                return response()->json([
                    'ok' => false ,
                    'message' => 'ឯកសារនេះមិនមានឡើយ។'
                ],422);
            }
            if( isset( $_FILES['word_file']['tmp_name'] ) && $_FILES['word_file']['tmp_name'] != "" ) {
                $path_to_word_file = $document->word_file ;
                $uniqeName = Storage::disk('public')->putFile( 'doctransaction/'.$document->id , new File( $_FILES['word_file']['tmp_name'] ) );
                $document->word_file = $uniqeName ;
                $document->save();

                // លុបឯកសារយោងដែលមានមុនពេលដាក់ឯកសារថ្មី
                if( Storage::disk('public')->exists( $path_to_word_file ) ){
                    Storage::disk('public')->delete( $path_to_word_file );
                }

                if( Storage::disk('public')->exists( $document->word_file ) ){
                    // $document->pdf_file = Storage::disk('public')->url( $document->pdf_file  );
                    // $document->word_file = Storage::disk('public')->url( $document->word_file  );
                    $document->update( [ 'file_word_name' => $_FILES['word_file']['name'] ]);
                    return response([
                        'ok' => true ,
                        // 'record' => $document ,
                        'message' => 'ជោគជ័យក្នុងការភ្ជាប់ឯកសារ។'
                    ],200);
                }else{
                    return response([
                        // 'record' => $document ,
                        'ok' => false ,
                        'message' => 'បរាជ័យក្នុងការភ្ចាប់ឯកសារ។'
                    ],500);
                }
            }else{
                return response([
                    // 'result' => $_FILES ,
                    'ok' => false ,
                    'message' => 'មានបញ្ហាជាមួយឯកសារដែលអ្នកបញ្ជូនមក។'
                ],500);
            }
            
        }else{
            return response([
                // 'record' => $user ,
                'ok' => false ,
                'message' => 'សូមចូលប្រព័ន្ធជាមុនសិន។'
            ],403);
        }
    }
    public function uploadPdf(Request $request){
        $user = \Auth::user() != null 
            ? \Auth::user()
            : (
                auth('api')->user() 
                    ? auth('api')->user()
                    : (
                        $request->user() != null
                            ? $request->user()
                            : 0
                    )
            );
        if( $user ){
            $document = intval( $request->document_id ) > 0 ? \App\Models\Document\Document::find( $request->document_id) : null ;
            if( $document == null ){
                return response()->json([
                    'ok' => false ,
                    'message' => 'ឯកសារនេះមិនមានឡើយ។'
                ],422);
            }
            if( isset( $_FILES['pdf_file']['tmp_name'] ) && $_FILES['pdf_file']['tmp_name'] != "" ) {
                $path_to_pdf_file = $document->pdf_file ;
                $uniqeName = Storage::disk('public')->putFile( 'doctransaction/'.$document->id , new File( $_FILES['pdf_file']['tmp_name'] ) );
                $document->pdf_file = $uniqeName ;
                $document->save();

                // លុបឯកសារយោងដែលមានមុនពេលដាក់ឯកសារថ្មី
                if( Storage::disk('public')->exists( $path_to_pdf_file ) ){
                    Storage::disk('public')->delete( $path_to_pdf_file );
                }

                if( Storage::disk('public')->exists( $document->pdf_file ) ){
                    // $document->pdf_file = Storage::disk('public')->url( $document->pdf_file  );
                    // $document->word_file = Storage::disk('public')->url( $document->word_file  );
                    $document->update( [ 'file_pdf_name' => $_FILES['pdf_file']['name'] ]);
                    return response([
                        'ok' => true ,
                        // 'record' => $document ,
                        'message' => 'ជោគជ័យក្នុងការភ្ជាប់ឯកសារ។'
                    ],200);
                }else{
                    return response([
                        'ok' => false ,
                        // 'record' => $document ,
                        'message' => 'បរាជ័យក្នុងការភ្ចាប់ឯកសារ។'
                    ],500);
                }
            }else{
                return response([
                    'ok' => false ,
                    // 'result' => $_FILES ,
                    'message' => 'មានបញ្ហាជាមួយឯកសារដែលអ្នកបញ្ជូនមក។'
                ],500);
            }
            
        }else{
            return response([
                'ok' => false ,
                // 'record' => $user ,
                'message' => 'សូមចូលប្រព័ន្ធជាមុនសិន។'
            ],403);
        }
    }
    public function downloadWord(Request $request){
        $user = \Auth::user() != null 
            ? \Auth::user()
            : (
                auth('api')->user() 
                    ? auth('api')->user()
                    : (
                        $request->user() != null
                            ? $request->user()
                            : 0
                    )
            );
        // \App\Models\Log\Log::access([
        //     'system' => 'client' ,
        //     'user_id' => $user->id ,
        //     'class' => self::class ,
        //     'func' => __FUNCTION__ ,
        //     'desp' => 'read word file of a document transaction'
        // ]); 
        $document = \App\Models\Document\Document::findOrFail($request->document_id);
        if($document) {
            // ពិនិត្យមើលអ្នកដែលមានសិទ្ធិក្នុងការបើកឯកសារមើល
            if( ( $receiver = $document->transaction->receiversPivot()->where('receiver_id',$user->id)->whereNotNull('accepted_at')->first() ) != null ){
                // កត់ត្រាម៉ោងដែលបានចូលមើលដំបូងបង្អស់
                if( $receiver->download_at == null || strlen( $receiver->download_at ) <= 0 ){
                    $receiver->update(['download_at'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
                }
            }else{
                return response()->json([
                    'ok' => false ,
                    'message' => 'អ្នកមិនមានសិទ្ធិទាញយកឯកសារទេ។'
                ],403);
            }
            
            $path = storage_path('app') . '/public/' . $document->word_file ; // doctransaction/49/ajdf;lakjd;flakjdf.pdf
            // $ext = pathinfo($path);
            // $filename = $document->number . "." . $ext['extension'];
            
            /**   Log the access of the user */
            // if( $user != null ){
            //     \App\Models\Log\Log::regulator([
            //         'system' => 'client' ,
            //         'user_id' => $user->id ,
            //         'regulator_id' => $document->id
            //     ]);
            // }

            if(is_file($path)) {

                $pdfBase64 = base64_encode( 
                    file_get_contents( 
                        storage_path('app') . '/public/' . $document->word_file
                    ) 
                );

                return response([
                    "pdf" => 'data:application/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,' . $pdfBase64 ,
                    "filename" => $document->file_word_name ,
                    "ok" => true 
                ],200);
            }else
            {
                return response([
                    'message' => 'មានបញ្ហាក្នុងការអានឯកសារយោង !' ,
                    // 'path' => $path
                ],404 );
            }
        }
    }
    public function downloadPdf(Request $request){
        $user = \Auth::user() != null 
            ? \Auth::user()
            : (
                auth('api')->user() 
                    ? auth('api')->user()
                    : (
                        $request->user() != null
                            ? $request->user()
                            : 0
                    )
            );
        \App\Models\Log\Log::access([
            'system' => 'client' ,
            'user_id' => $user->id ,
            'class' => self::class ,
            'func' => __FUNCTION__ ,
            'desp' => 'read word file of a document transaction'
        ]); 
        $document = \App\Models\Document\Document::findOrFail($request->document_id);
        if($document) {
            // ពិនិត្យមើលអ្នកដែលមានសិទ្ធិក្នុងការបើកឯកសារមើល
            if( ( $receiver = $document->transaction->receiversPivot()->where('receiver_id',$user->id)->whereNotNull('accepted_at')->first() ) != null ){
                // កត់ត្រាម៉ោងដែលបានចូលមើលដំបូងបង្អស់
                if( $receiver->download_at == null || strlen( $receiver->download_at ) <= 0 ){
                    $receiver->update(['download_at'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
                }
            }else{
                return response()->json([
                    'ok' => false ,
                    $document->transaction->receivers->pluck('id') ,
                    'message' => 'អ្នកមិនមានសិទ្ធិទាញយកឯកសារទេ។'
                ],403);
            }
            $path = storage_path('app') . '/public/' . $document->word_file ;
            // $ext = pathinfo($path);
            // $filename = $document->number . "." . $ext['extension'];
            
            /**   Log the access of the user */
            // if( $user != null ){
            //     \App\Models\Log\Log::regulator([
            //         'system' => 'client' ,
            //         'user_id' => $user->id ,
            //         'regulator_id' => $document->id
            //     ]);
            // }

            if(is_file($path)) {

                $pdfBase64 = base64_encode( 
                    file_get_contents( 
                        storage_path('app') . '/public/' . $document->word_file
                    ) 
                );

                return response([
                    "pdf" => 'data:application/pdf;base64,' . $pdfBase64 ,
                    "filename" => $document->file_pdf_name ,
                    "ok" => true 
                ],200);
            }else
            {
                return response([
                    'message' => 'មានបញ្ហាក្នុងការអានឯកសារយោង !' ,
                    // 'path' => $path
                ],404 );
            }
        }
    }

    /**
     * មុនងារមួយនេះនៅមិនទាន់រួចរាល់ត្រង់ចំណុចដាក់ watermark នៅឡើយ
     */
    public function previewPdf(Request $request){
        $user = \Auth::user() != null 
            ? \Auth::user()
            : (
                auth('api')->user() 
                    ? auth('api')->user()
                    : (
                        $request->user() != null
                            ? $request->user()
                            : 0
                    )
            );
        \App\Models\Log\Log::access([
            'system' => 'client' ,
            'user_id' => $user->id ,
            'class' => self::class ,
            'func' => __FUNCTION__ ,
            'desp' => 'read word file of a document transaction'
        ]); 
        $document = \App\Models\Document\Document::findOrFail($request->document_id);
        if($document) {
            // ពិនិត្យមើលអ្នកដែលមានសិទ្ធិក្នុងការបើកឯកសារមើល
            if( ( $receiver = $document->transaction->receiversPivot()->where('receiver_id',$user->id)->first() ) != null ){
                // កត់ត្រាម៉ោងដែលបានចូលមើលដំបូងបង្អស់
                if( $receiver->preview_at == null || strlen( $receiver->preview_at ) <= 0 ){
                    $receiver->update(['preview_at'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
                }
            }else{
                return response()->json([
                    'ok' => false ,
                    'message' => 'អ្នកមិនមានសិទ្ធិទាញយកឯកសារទេ។'
                ],403);
            }
            $path = storage_path('app') . '/public/' . $document->word_file ;
            // $ext = pathinfo($path);
            // $filename = $document->number . "." . $ext['extension'];
            
            /**   Log the access of the user */
            // if( $user != null ){
            //     \App\Models\Log\Log::regulator([
            //         'system' => 'client' ,
            //         'user_id' => $user->id ,
            //         'regulator_id' => $document->id
            //     ]);
            // }

            if(is_file($path)) {

                // Check whether the pdf has once applied the watermark
                // if( !file_exists (storage_path('data') . '/watermarkfiles/' . $document->pdf ) ){
                //     // Specify path to the existing pdf
                //     $pdf = new Pdf( $pathPdf );

                //     // Specify path to image. The image must have a 96 DPI resolution.
                //     $watermark = new ImageWatermark( 
                //         storage_path('data') . 
                //         '/watermark5.png' 
                //     );

                //     // Create a new watermarker
                //     $watermarker = new PDFWatermarker($pdf, $watermark); 

                //     // Set the position of the watermark including optional X/Y offsets
                //     // $position = new Position(Position::BOTTOM_CENTER, -50, -10);

                //     // All possible positions can be found in Position::options
                //     // $watermarker->setPosition($position);

                //     // Place watermark behind original PDF content. Default behavior places it over the content.
                //     // $watermarker->setAsBackground();


                //     // Only Watermark specific range of pages
                //     // This would only watermark page 3 and 4
                //     // $watermarker->setPageRange(3, 4);
                    
                //     // Save the new PDF to its specified location
                //     $watermarker->save( storage_path('data') . '/watermarkfiles/' . $document->pdf );
                // }   


                $pdfBase64 = base64_encode( 
                    file_get_contents( 
                        storage_path('app') . '/public/' . $document->word_file
                    ) 
                );

                return response([
                    "pdf" => 'data:application/pdf;base64,' . $pdfBase64 ,
                    "filename" => $document->file_pdf_name ,
                    "ok" => true 
                ],200);
            }else
            {
                return response([
                    'message' => 'មានបញ្ហាក្នុងការអានឯកសារយោង !' ,
                    // 'path' => $path
                ],404 );
            }
        }
    }
    public function addBriefing(Request $request){
        $user = \Auth::user() != null 
            ? \Auth::user()
            : (
                auth('api')->user() 
                    ? auth('api')->user()
                    : (
                        $request->user() != null
                            ? $request->user()
                            : 0
                    )
            );
        // ត្រួតពិនិ្យឯកសារ
        $document = intval( $request->document_id ) > 0 ? \App\Models\Document\Document::find( $request->document_id) : null ;
            if( $document == null ){
                return response()->json([
                    'ok' => false ,
                    'message' => 'ឯកសារនេះមិនមានឡើយ។'
                ],422);
            }
        // ត្រួតពិនិ្យកំណត់បង្ហាញនៃឯកសារ
        $briefing = strlen( trim($request->briefing) ) > 0 ? trim($request->briefing) : false ;
        if( $briefing == false ){
            return response()->json([
                'ok' => false ,
                'message' => "សូមបញ្ចូលកំណត់បង្ហាញនៃឯកសារឱ្យបានត្រឹមត្រូវ។"
            ],422);
        }
        $document->briefings()->create(
            [
                'document_id'=> $document->id ,
                'briefer_id' => $user->id ,
                'briefing' => $briefing ,
                'created_by' => $user->id ,
                'updated_by' => $user->id ,
                'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
                'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
            ]
        );
        return response()->json([
            'ok' => true ,
            'message' => 'ជោគជ័យ'
        ],200);
    }
    public function accepted(Request $request){
        // អ្នកទទួលការងារក្នុងពេលនេះនិងដើរតួជាអ្នកបញ្ជូននៅប្រតិបត្តិការបន្ទាប់
        $receiver = \Auth::user() != null 
            ? \Auth::user()
            : (
                auth('api')->user() 
                    ? auth('api')->user()
                    : (
                        $request->user() != null
                            ? $request->user()
                            : 0
                    )
            );
        /**
         * នៅពេលទទួលការងារ អ្នកទទួល និងត្រូវបានកត់ត្រាថាជាអ្នកទទួលខុសត្រូវការងារ
         */
        // ត្រួតពិនិត្យប្រតិបត្តិការបញ្ជូន
        $previousTransaction = intval( $request->id ) > 0 ? RecordModel::find( $request->id ) : null ;

        if( $previousTransaction == null ){
            return response()->json([
                'ok' => false ,
                'previous' => $previousTransaction ,
                'request' => $request->input() ,
                'message' => 'ប្រតិបត្តិការបញ្ជូនមិនមានឡើយ។' 
            ],422);
        }


        if( ( $receiver = $previousTransaction->receiversPivot()->where('receiver_id',$receiver->id)->first() ) != null ){
            $receiver->update(['accepted_at'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
        }else{
            return response()->json([
                'ok' => false ,
                'ids' => $previousTransaction->receivers->toArray() ,
                'message' => 'អ្នកមិនមានសិទ្ធិទាញយកឯកសារទេ។'
            ],403);
        }

        /**
         * បង្កើតប្រតិបត្តិការដឹកជញ្ជូនឯកសារ
         */
        // ត្រួតពិនិត្យម៉ោងឯកសារចូល
        $dateIn = \Carbon\Carbon::now();
        // ត្រួតពិនិ្យប្រធានបទនៃការបញ្ជូនឯកសារ
        $subject = $previousTransaction->subject;

        /**
         * ប្រតិបត្តិការបញ្ជូនអាចត្រូវបាន
         * ១. បង្អាកដោយបច្ចៃនាមួយ
         * ២. ឬ​បញ្ហាណាមួយដែលមិនគ្រងទុក
         * ត្រូវថែម Column 'status' និង ត្រូវមានការផ្ដល់យោបល់ ឬសាក់សួរលើបញ្ហានេះផងដែរ
         */
        $transaction = RecordModel::create([
            'document_id' => null ,
            'sender_id' => $receiver->id ,
            'subject' => $subject ,
            'date_in' => $dateIn->format('Y-m-d H:i:s') ,
            'previous_transaction_id' => $previousTransaction->id ,
            'tpid' => strlen( $previousTransaction->tpid ) > 0 ? $previousTransaction->tpid . ':' . $previousTransaction->id . ':' : $previousTransaction->id . ':' ,

            'created_by' => $receiver->id ,
            'updated_by' => $receiver->id ,
            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
        ]);

        // ភ្ជាប់ប្រតិបត្តិការបញ្ជូនពីមុន ជាមួយ ប្រតិបត្តិការថ្មីនេះ
        $previousTransaction->update([ 'next_transaction_id' => $transaction->id ]) ;
        /**
         * បង្កើតឯកសាររួចភ្ជាប់ជាមួយឯកសារដែលបានបញ្ជូនមក
         */
        // ត្រួតពិនិត្យលេខឯកសារ    
        $number = $previousTransaction->document->number ;
        $public_key = md5( $number . ( $dateIn != false ? $dateIn->format('YmdHis') : '' ) );
        // ត្រួតពិនិត្យខ្លឹមសារឯកសារ
        $objective = $previousTransaction->document->objective;
        //​ បង្កើតឯកសារ
        $document = \App\Models\Document\Document::create([
            'public_key' => $public_key ,
            'number' => $number ,
            'objective' => $objective ,
            'created_by' => $receiver->id ,
            'updated_by' => $receiver->id ,
            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
        ]);

        // ភ្ជាប់ឯកសារទៅកាន់ការបញ្ជូន
        $transaction->update(['document_id'=>$document->id]);
        return response()->json([
            'ok' => true ,
            'receiver' => $receiver ,
            'transaction' => $transaction->toArray() ,
            'previous' => $previousTransaction->toArray() ,
            'message' => 'ជោគជ័យ'
        ],200);
    }
    public function lastSignature(){
        /**
         * ការកំណត់ហត្ថលេខាចុងក្រោយត្រូវពឹងផ្អែកលើគោនយោបាយ នៃតួនាទីរបស់ថ្នាក់ដឹកនាំ
         */
    }
    public function destroy(Request $request){}
}
