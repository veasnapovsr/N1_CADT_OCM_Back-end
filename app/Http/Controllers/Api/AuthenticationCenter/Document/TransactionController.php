<?php

namespace App\Http\Controllers\Api\AuthenticationCenter\Document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Document\Transaction as RecordModel;
use App\Models\Document\OrganizationFocalPeople;
use App\Http\Controllers\CrudController;
use Illuminate\Http\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
// use Spatie\PdfToImage\Pdf;


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
        $user = \Auth::user() != null
            ? \Auth::user()
            : (
                auth('api')->user()
                    ? auth('api')->user()
                    : (
                        $request->user() != null
                            ? $request->user()
                            : null
                    )
            );

        /** Format from query string */
        $search = isset( $request->search ) && strlen( $request->search ) > 0 ? $request->search : false ;
        $perPage = isset( $request->perPage ) && intval( $request->perPage ) > 0  ? $request->perPage : 20 ;
        $page = isset( $request->page ) && intval( $request->page ) > 0 ? $request->page : 1 ;

        /**
         * លក្ខណចម្រោះទិន្នន័យ
         */
        /**
         * លក្ខណចម្រោះនៃឯកសារ
         */
        $number = isset( $request->number ) && strlen( $request->number ) ? $request->number : false ;
        $objective = isset( $request->objective ) && strlen( $request->objective ) ? $request->objective : false ;
        /**
         * លក្ខណចម្រោះប្រតិបត្តិការបញ្ជូនឯកសារ
         */
        $sender_id = isset( $request->sender_id ) && intval( $request->sender_id ) > 0 ? $request->sender_id : false ;

        $date = isset( $request->date ) & strlen( $request->date ) >=10 ? \Carbon\Carbon::parse( $request->date ) : false ;
        $status = isset( $request->status ) & strlen( $request->status ) > 3
            ? (
                in_array( $request->status , RecordModel::STATUSES )
                    ? $request->status
                    : false
            )
            : false ;

        /**
         * លក្ខណចម្រោះតាមអង្គភាពចុងក្រោយ
         */
        $queryString = [
            // "where" => [
            //     'default' => [
            //         $status != false
            //             ?
            //                 [
            //                     'field' => 'status' ,
            //                     'value' => $status
            //                 ]
            //             :
            //             [
            //                 'field' => 'status' ,
            //                 'value' => null
            //             ]
            //     ],
            //     'in' => [
            //         [
            //             'field' => 'type' ,
            //             'value' => isset( $request->type ) && $request->type !== null ? [$request->type] : false
            //         ]
            //     ] ,
            //     'not' => [
            //         [
            //             'field' => 'type' ,
            //             'value' => [4]
            //         ]
            //     ] ,
            //     'like' => [
            //         $date != false
            //             ? [
            //                 'field' => 'date_in' ,
            //                 'value' => $date->format('Y-m-d')
            //             ] : []
            //     ]
            // ] ,
            // "pivots" => [
            //     // Transaction Document
            //     $number != false ?
            //     [
            //         "relationship" => 'document',
            //         "where" =>[
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
            //                     "field"=> 'number' ,
            //                     "value"=> $number
            //                 ],
            //                 [
            //                     "field"=> 'objective' ,
            //                     "value"=> $objective
            //                 ],

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
                'id' , 'objective' , 'word_file' , 'pdf_file' , 'number',
                'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                'editor' => [ 'id' , 'firstname' , 'lastname' ]
            ] , //append number properties
            'sender' => [
                'id' , 'firstname' , 'lastname' , 'avatar_url','countesy_id',
                'officer' => [
                        'id' , 'code' ,
                        // people => [ 'id' , 'firstname' , 'lastname' ]
                ]
            ] , //append avatar_url properties
            'receivers' => [ 'id' , 'code' ],
            'previous' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' ,
                'document' => [
                   'id' , 'objective' , 'word_file' , 'pdf_file' ,
                //    'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                //    'editor' => [ 'id' , 'firstname' , 'lastname' ]
                ] ,
                'sender' => ['id' , 'firstname' , 'lastname', 'countesy_id'] ,
                'receivers' => ['id' , 'firstname' , 'lastname'],
            ],
            'next' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' ,
                'document' => [
                    'id' , 'objective' , 'word_file' , 'pdf_file' ,
                    // 'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                    // 'editor' => [ 'id' , 'firstname' , 'lastname' ]
                ] ,
                'sender' => [ 'id' , 'firstname' , 'lastname' , 'countesy_id' ] ,
                'receivers' => [ 'id' , 'firstname' , 'lastname'  ],
            ],
        ]);


        $builder = $crud->getListBuilder();

        /**
         * 1. ចម្រោះប្រតិបត្តិការឯកសារដោយយោងតាមអ្នកទទួល
         * 2. ចម្រោះប្រតិបត្តិការឯកសារតាមអ្នកបញ្ជូន
         */
        $this->applyTransactionVisibilityScope($builder, $user);

        //     ->orWhereHas('receivers',function($queryBuilder) use( $user ){
        //         $queryBuilder->whereIn('receiver_id', [ $user->officer->id ] );
        //     });
        // });

        $responseData = $crud->pagination(true, $builder);

        $responseData['records'] = $responseData['records']->map(function($record){
            $record['document'] = isset($record['document']) && is_array($record['document'])
                ? $record['document']
                : null;
            $record['receivers'] = $this->buildWorkflowReceiverPayloads($record['id']);
            // Add two if statement for fullname avatar
            if($record['sender']['firstname'] != null && strlen($record['sender']['firstname']) > 0 && $record['sender']['lastname'] != null && strlen($record['sender']['lastname']) > 0 ){
                $record['sender']['fullname'] = $record['sender']['lastname'] . ' ' . $record['sender']['firstname'];
            }
            if($record['sender']['avatar_url'] != null && strlen($record['sender']['avatar_url']) > 0 && \Storage::disk('public')->exists( $record['sender']['avatar_url'] ) ){
                $record['sender']['avatar_url'] = \Storage::disk('public')->url( $record['sender']['avatar_url'] );
            }

           //==========ទាញយកPDf Thumbnail===============
       //    $record['sender']['pdf_thumbnail'] = \Storage::disk('public')->url('doctransaction/' . $record['document']['id'] . '/thumbnail/firtpage.jpg');
           if ($record['document'] != null && isset($record['document']['id'])) {
               $thumbnailPath = 'doctransaction/' . $record['document']['id'] . '/thumbnail/firstpage.jpg';
               if (Storage::disk('public')->exists($thumbnailPath)) {
                   $record['document']['thumbnail'] = Storage::disk('public')->url($thumbnailPath);
               } else {
                   $record['document']['thumbnail'] = null;
               }
           } else {
               $record['document'] = [
                   'id' => null,
                   'thumbnail' => null,
                   'pdf_file' => null,
                   'word_file' => null,
                   'pdf_file_size' => 0,
                   'word_file_size' => 0,
               ];
           }

            // if( $record['sender']['officer'] != null ){
            //     $officer = \App\Models\Officer\Officer::find( $record['sender']['officer']['id'] );
            //     $record['sender']['officer']['people'] = $officer->people;

            //     $record['sender']['officer']['jobs'] = $officer->jobs->map(function($job){
            //         $job->countesy;
            //         if( $job->organizationStructurePosition != null ){
            //             $job->organizationStructurePosition->position;
            //             if( $job->organizationStructurePosition->organizationStructure != null ){
            //                 $job->organizationStructurePosition->organizationStructure->organization;
            //             }
            //         }
            //         return $job;
            //     }
            //     $record['sender']['countesy'] = $officer->jobs->first()->countesy->name;
            //    );
            // }

            if ($record['sender']['officer'] != null) {

                $officer = \App\Models\Officer\Officer::with([
                    'people.countesy',
                    'user',
                    'jobs.organizationStructurePosition.position',
                    'jobs.organizationStructurePosition.organizationStructure.organization'
                ])->find($record['sender']['officer']['id']);

                if ($officer) {
                    $record['sender'] = $this->serializeWorkflowOfficer($officer);
                }
            }
            // if( $record['document'] != null ){
            //     if( $record['document']['pdf_file'] != null && strlen( $record['document']['pdf_file'] ) > 0 && \Storage::disk('public')->exists( $record['document']['pdf_file'] ) ){
            //         $record['document']['pdf_file'] = \Storage::disk('public')->url( $record['document']['pdf_file'] );
            //         // $record['document']['pdf_file_size'] = round( \Storage::disk('public')->size($record['document']['pdf_file']) / 1024, 2) . " KB" ;
            //     }
            //     if( $record['document']['word_file'] != null && strlen( $record['document']['word_file'] ) > 0 && \Storage::disk('public')->exists( $record['document']['word_file'] ) ){
            //         $record['document']['word_file'] = \Storage::disk('public')->url( $record['document']['word_file'] );
            //         // $record['document']['word_file_size'] = round( \Storage::disk('public')->path($record['document']['word_file']) / 1024, 2) . " KB" ;
            //     }
            // }

            // Add an if statement to respone with filesize
            if( $record['document'] != null ){
                $record['document']['pdf_file_size'] = 0 ;
                $record['document']['word_file_size'] = 0 ;
                if( $record['document']['pdf_file'] != null && strlen( $record['document']['pdf_file'] ) > 0 && \Storage::disk('public')->exists( $record['document']['pdf_file'] ) ){
                    $OriginalPath = $record['document']['pdf_file'];
                    $record['document']['pdf_file'] = \Storage::disk('public')->url( $record['document']['pdf_file'] );
                    $record['document']['pdf_file_size'] = round( \Storage::disk('public')->size( $OriginalPath ) / (1024 * 1024), 2) . " MB" ;     //uncomment to get filesize
                }
                if( $record['document']['word_file'] != null && strlen( $record['document']['word_file'] ) > 0 && \Storage::disk('public')->exists( $record['document']['word_file'] ) ){
                    $OriginalPath = $record['document']['word_file'];
                    $record['document']['word_file'] = \Storage::disk('public')->url( $record['document']['word_file'] );
                    $record['document']['word_file_size'] = round( \Storage::disk('public')->size( $OriginalPath ) / (1024 * 1024), 2) . " MB" ;   //uncomment to get filesize
                }
            }
            return $record;

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
        // if( ( $receiver = $record->receiversPivot()->where('receiver_id',$user->id)->first() ) != null ){
        //     // កត់ត្រាម៉ោងដែលបានចូលមើលដំបូងបង្អស់
        //     if( $receiver->seen_at == null || strlen( $receiver->seen_at ) <= 0 ){
        //         $receiver->update(['seen_at'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
        //     }
        // }else{
        //     return response()->json([
        //         'ok' => false ,
        //         'message' => 'អ្នកមិនមានសិទ្ធិក្នុងប្រតិបត្តិការនេះទេ។'
        //     ],403);
        // }
        $crud = new CrudController(new RecordModel(), $request, $this->selectFields);
        $crud->setRelationshipFunctions([
            /** relationship name => [ array of fields name to be selected ] */
            'document' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' , 'number',
                'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                'editor' => [ 'id' , 'firstname' , 'lastname' ]
            ] ,
            'sender' => [
                'id' , 'firstname' , 'lastname' , 'avatar_url',
                'officer' => [
                        'id' , 'code',
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
                'sender' => [ 'id' , 'firstname' , 'lastname', 'countesy_id'] ,
                'receivers' => [ 'id' , 'firstname' , 'lastname'  ],
            ],
            'next' => [
                'id' , 'objective' , 'word_file' , 'pdf_file' ,
                'document' => [
                    'id' , 'objective' , 'word_file' , 'pdf_file' ,
                    // 'author' => [ 'id' , 'firstname' , 'lastname' ] ,
                    // 'editor' => [ 'id' , 'firstname' , 'lastname' ]
                ] ,
                'sender' => [ 'id' , 'firstname' , 'lastname', 'countesy_id', ] ,
                'receivers' => [ 'id' , 'firstname' , 'lastname'  ],
            ],
        ]);

        $builder = $crud->getListBuilder();

        $builder->where('id' , $record->id );

        $responseData = $crud->pagination(true, $builder);
        $responseData['records'] = $responseData['records']->map(function($record){
            $record['document'] = isset($record['document']) && is_array($record['document'])
                ? $record['document']
                : null;
            $record['receivers'] = $this->buildWorkflowReceiverPayloads($record['id']);
            // Add two if state for fullnameand avatarurl
            if($record['sender']['firstname'] != null && strlen($record['sender']['firstname']) > 0 && $record['sender']['lastname'] != null && strlen($record['sender']['lastname']) > 0 ){
                $record['sender']['fullname'] = $record['sender']['lastname'] . ' ' . $record['sender']['firstname'];
            }
            if($record['sender']['avatar_url'] != null && strlen($record['sender']['avatar_url']) > 0 && \Storage::disk('public')->exists( $record['sender']['avatar_url'] ) ){
                $record['sender']['avatar_url'] = \Storage::disk('public')->url( $record['sender']['avatar_url'] );
            }
            //==========ទាញយកPDf Thumbnail===============
            if ($record['document'] != null && isset($record['document']['id'])) {
                $thumbnailPath = 'doctransaction/' . $record['document']['id'] . '/thumbnail/firstpage.jpg';
                if (Storage::disk('public')->exists($thumbnailPath)) {
                    $record['document']['thumbnail'] = Storage::disk('public')->url($thumbnailPath);
                } else {
                    $record['document']['thumbnail'] = null; // optional: placeholder
                }
            } else {
                $record['document'] = [
                    'id' => null,
                    'thumbnail' => null,
                    'pdf_file' => null,
                    'word_file' => null,
                    'pdf_file_size' => 0,
                    'word_file_size' => 0,
                ];
            }
            //=============================================
            // if( $record['sender']['officer'] != null ){
            //     $officer = \App\Models\Officer\Officer::find( $record['sender']['officer']['id'] );
            //     $record['sender']['officer']['people'] = $officer->people;
            //     $record['sender']['officer']['jobs'] = $officer->jobs->map(function($job){
            //         $job->countesy;
            //         if( $job->organizationStructurePosition != null ){
            //             $job->organizationStructurePosition->position;
            //             if( $job->organizationStructurePosition->organizationStructure != null ){
            //                 $job->organizationStructurePosition->organizationStructure->organization;
            //             }
            //         }
            //         return $job;
            //     });
            // }

            if ($record['sender']['officer'] != null) {
                $officer = \App\Models\Officer\Officer::with([
                    'people.countesy',
                    'user',
                    'jobs.organizationStructurePosition.position',
                    'jobs.organizationStructurePosition.organizationStructure.organization'
                ])->find($record['sender']['officer']['id']);

                if ($officer) {
                    $record['sender'] = $this->serializeWorkflowOfficer($officer);
                }
            }

            if( $record['document'] != null ){
                $record['document']['pdf_file_size'] =  0;
                $record['document']['word_file_size'] = 0 ;
                if( $record['document']['pdf_file'] != null && strlen( $record['document']['pdf_file'] ) > 0 && \Storage::disk('public')->exists( $record['document']['pdf_file'] ) ){
                    $OriginalPath = $record['document']['pdf_file'];
                    $record['document']['pdf_file'] = \Storage::disk('public')->url( $record['document']['pdf_file'] );
                    $record['document']['pdf_file_size'] = round( \Storage::disk('public')->size( $OriginalPath ) / (1024 * 1024), 2) . " MB" ;     //uncomment to get filesize
                }
                if( $record['document']['word_file'] != null && strlen( $record['document']['word_file'] ) > 0 && \Storage::disk('public')->exists( $record['document']['word_file'] ) ){
                    $OriginalPath = $record['document']['word_file'];
                    $record['document']['word_file'] = \Storage::disk('public')->url( $record['document']['word_file'] );
                    $record['document']['word_file_size'] = round( \Storage::disk('public')->size( $OriginalPath ) / (1024 * 1024), 2) . " MB" ;   //uncomment to get filesize
                }

                $record['document']['briefings'] = DB::table('document_briefings as briefings')
                    ->leftJoin('users as briefers', 'briefers.id', '=', 'briefings.briefer_id')
                    ->where('briefings.document_id', $record['document']['id'])
                    ->whereNull('briefings.deleted_at')
                    ->orderBy('briefings.created_at')
                    ->get([
                        'briefings.id',
                        'briefings.document_id',
                        'briefings.briefer_id',
                        'briefings.briefing',
                        'briefings.created_at',
                        'briefings.updated_at',
                        'briefers.firstname as briefer_firstname',
                        'briefers.lastname as briefer_lastname',
                        'briefers.email as briefer_email'
                    ])
                    ->map(function ($briefing) {
                        return [
                            'id' => $briefing->id,
                            'document_id' => $briefing->document_id,
                            'briefer_id' => $briefing->briefer_id,
                            'briefing' => $briefing->briefing,
                            'created_at' => $briefing->created_at,
                            'updated_at' => $briefing->updated_at,
                            'briefer' => [
                                'id' => $briefing->briefer_id,
                                'firstname' => $briefing->briefer_firstname,
                                'lastname' => $briefing->briefer_lastname,
                                'email' => $briefing->briefer_email,
                            ],
                        ];
                    })
                    ->values()
                    ->all();
            }
            $record['transactions'] = RecordModel::find($record['id'])->getTimeline();
            return $record;
        });

        return response()->json([
            'message' => __("crud.read.success") ,
            'record' => $responseData['records']->first() ,
            'ok' => true
        ], 200);
    }

    public function store(Request $request){
        //explode is function to convert the string into the array but it separate the base on the first arguement
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

    //1. it take the user token and and with passport it find the user id and return it
    //2. check the value of datein, subject, objective, number, for subject if the request body dont have it will take objective as it value
    //3. then create transaction,
    //4. after that take (number concatinate the date format ymdhis) and md5 it
    //5. access document model and ::create{value...,..}
    //6. with this function 'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') the backend side will generate it date itself
    //7. after the document all set use transaction update the document id
    public function storeDraft(Request $request){
        //get the token from user then turn it into id
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
        $objective = strlen( trim($request->objective) ) > 0 ? trim($request->objective) : false ;
        $subject = $objective ? $objective : false ;
        if( $subject == false ){
            return response()->json([
                'ok' => false ,
                'message' => "សូមបញ្ចូលខ្លឹមសារនៃឯកសារឱ្យបានត្រឹមត្រូវ។"
            ],422);
        }
        // ត្រួតពិនិត្យលេខអ្នកបញ្ជូនឯកសារ
        $sender = $user;

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
        $public_key = md5( $number . ( $dateIn != false ? $dateIn->format('YmdHis') : '' ) ); //123 + 20260402103000 then md5 it
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
            'document_type' => intval( $request->document_type ) > 0 ? intval( $request->document_type ) : 0 ,
            'objective' => $objective ,
            'created_by' => $sender->id ,
            'updated_by' => $sender->id ,
            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
        ]);
        // ភ្ជាប់ឯកសារទៅកាន់ការបញ្ជូន
        $transaction->update(['document_id'=>$document->id]);
        $flowAction = $this->resolveRequestedFlowAction(
            $request,
            $this->requestHasDispatchTargets($request) ? 'send' : RecordModel::STATUS_DRAFT
        );

        if ($flowAction === 'send') {
            $dispatchResult = $this->dispatchTransaction($transaction, $sender, $request);
            if ($dispatchResult !== true) {
                return $dispatchResult;
            }
        } elseif ($flowAction === 'diy') {
            $transaction->update([
                'status' => RecordModel::STATUS_PROGRESS,
                'organization_structure_id' => $this->resolveCurrentOrganizationStructureId($sender),
            ]);
        }

        return response()->json([
            'ok' => true ,
            'record' => $transaction->fresh() ,
            'flow_action' => $flowAction,
            'message' => 'ជោគជ័យ'
        ],200);
    }

    public function changeReceiver(Request $request){
        $actorId = Auth::id();

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
                'created_by' => $actorId != null ? $actorId : $transaction->sender_id ,
                'updated_by' => $actorId != null ? $actorId : $transaction->sender_id ,
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

        // ត្រួតពិនិត្យប្រតិបត្តិការបញ្ជូន
        $transaction = $this->resolveRequestedTransaction($request, $user);
        if( $transaction == null ){
            return response()->json([
                'ok' => false ,
                'message' => 'ប្រតិបត្តិការបញ្ជូនមិនមានឡើយ។'
            ],422);
        }

        if (!$user) {
            return response()->json([
                'ok' => false ,
                'message' => 'សូមចូលប្រើប្រាស់ជាមុនសិន។'
            ],401);
        }

        $sendableTransaction = $this->resolveSendableTransactionForUser($transaction, $user);
        if ($sendableTransaction instanceof \Illuminate\Http\JsonResponse) {
            return $sendableTransaction;
        }

        $transaction = $sendableTransaction;

        $flowAction = $this->resolveRequestedFlowAction($request, 'send');
        if ($flowAction === 'diy') {
            $transaction->update([
                'status' => RecordModel::STATUS_PROGRESS,
                'organization_structure_id' => $this->resolveCurrentOrganizationStructureId($user),
            ]);

            return response()->json([
                'ok' => true,
                'record' => $transaction->fresh(),
                'flow_action' => $flowAction,
                'message' => 'ជោគជ័យ'
            ],200);
        }

        $dispatchResult = $this->dispatchTransaction($transaction, $user, $request);
        if ($dispatchResult !== true) {
            return $dispatchResult;
        }

        return response()->json([
            'ok' => true ,
            'record' => $transaction->fresh() ,
            'flow_action' => 'send',
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
        $transaction = intval( $request->id ) > 0 ? RecordModel::find( $request->id ) : null ;
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

                //===============================================
                //For window require to install imagick PHP 8.2 TS, imagemagick and ghostscript latest version,
                //for linux just composer require spatie/pdf-to-image
                // បង្កើត Thumbnail សម្រាប់ឯកសារ​ PDF
                // try{
                //     if (class_exists(\Imagick::class)){
                //         $thumbnailFolder = storage_path('app/public/doctransaction/'.$document->id.'/thumbnail');
                //         // Create folder if it doesn't exist
                //         if (!file_exists($thumbnailFolder)) {
                //             mkdir($thumbnailFolder, 0777, true); // recursive
                //         }
                //         // 2️⃣ Define thumbnail file path (name)
                //         $thumbnailFileName = 'firstpage.jpg';
                //         $thumbnailPath = $thumbnailFolder.'/'.$thumbnailFileName;

                //         // Remove existing thumbnail if it exists
                //         // if (file_exists($thumbnailPath)) {
                //         //     unlink($thumbnailPath);
                //         // }

                //         $pdf = new Pdf($_FILES['pdf_file']['tmp_name']);
                //         $pdf->save($thumbnailPath);

                //         // $pdf->setPage(1)
                //         //     ->setResolution(150)
                //         //     ->saveImage($thumbnailPath);
                //         //==================================================
                //     }
                // }catch (\Throwable $e) {
                //     // Log only, do NOT crash upload
                //     \Log::warning('PDF thumbnail skipped', [
                //         'reason' => $e->getMessage()
                //     ]);
                // }

                //  $thumbnailFolder = storage_path('app/public/doctransaction/'.$document->id.'/thumbnail');
                //         // Create folder if it doesn't exist
                //         if (!file_exists($thumbnailFolder)) {
                //             mkdir($thumbnailFolder, 0777, true); // recursive
                //         }
                //         // 2️⃣ Define thumbnail file path (name)
                //         $thumbnailFileName = 'firstpage.jpg';
                //         $thumbnailPath = $thumbnailFolder.'/'.$thumbnailFileName;

                //         // Remove existing thumbnail if it exists
                //         // if (file_exists($thumbnailPath)) {
                //         //     unlink($thumbnailPath);
                //         // }

                //         $pdf = new Pdf($_FILES['pdf_file']['tmp_name']);
                //         $pdf->save($thumbnailPath);



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

    public function uploadFiles(Request $request){
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

    if ($user) {

        $phpFileUploadErrors = [
            0 => 'មិនមានបញ្ហាជាមួយឯកសារឡើយ។',
            1 => "ទំហំឯកសារធំហួសកំណត់ " . ini_get("upload_max_filesize"),
            2 => 'ទំហំឯកសារធំហួសកំណត់នៃទំរង់បញ្ចូលទិន្នន័យ ' . ini_get('post_max_size'),
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        ];

        foreach ($_FILES['files']['error'] as $error) {
            if ($error > 0) {
                return response()->json([
                    'ok' => false,
                    'message' => $phpFileUploadErrors[$error]
                ], 403);
            }
        }

        if (($document = \App\Models\Document\Document::find($request->document_id)) !== null) {

            $success = [
                'pdf' => false,
                'word' => false,
                'extension' => []
            ];

            foreach ($_FILES['files']['tmp_name'] as $index => $file) {

                $file_path = $_FILES['files']['tmp_name'][$index];

                // ✅ File size in MB (NOT KB)
                $mbFilesize = round(filesize($file_path) / (1024 * 1024), 4);

                // Original filename
                $filename = $_FILES['files']['name'][$index];

                // File extension
                $extension = pathinfo($filename, PATHINFO_EXTENSION);

                if ($extension == 'pdf') {

                    $path_to_pdf_file = $document->pdf_file != null && strlen($document->pdf_file) > 0
                        ? $document->pdf_file
                        : false;

                    $uniqeName = Storage::disk('public')
                        ->putFile('doctransaction/' . $document->id, new File($file_path));

                    $document->pdf_file = $uniqeName;
                    $document->save();

                    // Delete old file
                    if ($path_to_pdf_file && Storage::disk('public')->exists($path_to_pdf_file)) {
                        Storage::disk('public')->delete($path_to_pdf_file);
                    }

                    if (Storage::disk('public')->exists($document->pdf_file)) {
                        $document->update(['file_pdf_name' => $filename]);
                        $success['pdf'] = true;
                    }

                } elseif ($extension == 'doc' || $extension == 'docx') {

                    $path_to_word_file = $document->word_file != null && strlen($document->word_file) > 0
                        ? $document->word_file
                        : false;

                    $uniqeName = Storage::disk('public')
                        ->putFile('doctransaction/' . $document->id, new File($file_path));

                    $document->word_file = $uniqeName;
                    $document->save();

                    // Delete old file
                    if ($path_to_word_file && Storage::disk('public')->exists($path_to_word_file)) {
                        Storage::disk('public')->delete($path_to_word_file);
                    }

                    if (Storage::disk('public')->exists($document->word_file)) {
                        $document->update(['file_word_name' => $filename]);
                        $success['word'] = true;
                    }
                }
            }

            return response([
                'ok' => true,
                'result' => $success,
                'message' => 'ជោគជ័យក្នុងការភ្ជាប់ឯកសារ។'
            ], 200);

        } else {
            return response([
                'message' => 'សូមបញ្ជាក់អំពីលេខសម្គាល់របស់ឯកសារយោង។'
            ], 403);
        }

    } else {
        return response([
            'message' => 'សូមចូលប្រព័ន្ធជាមុនសិន។'
        ], 403);
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
            $transaction = $this->resolveDocumentAccessTransaction($document->id, $user, $request);
            $receiver = $this->resolveDocumentAccessReceiver($transaction, $user, true);
            if ($transaction == null || ($receiver == null && (int) $transaction->sender_id !== (int) $user->id)) {
                return response()->json([
                    'ok' => false ,
                    'message' => 'អ្នកមិនមានសិទ្ធិទាញយកឯកសារទេ។'
                ],403);
            }

            if( $receiver != null && ( $receiver->download_at == null || strlen( $receiver->download_at ) <= 0 ) ){
                $receiver->update(['download_at'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
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
            $transaction = $this->resolveDocumentAccessTransaction($document->id, $user, $request);
            $receiver = $this->resolveDocumentAccessReceiver($transaction, $user, true);
            if ($transaction == null || ($receiver == null && (int) $transaction->sender_id !== (int) $user->id)) {
                return response()->json([
                    'ok' => false ,
                    'message' => 'អ្នកមិនមានសិទ្ធិទាញយកឯកសារទេ។'
                ],403);
            }

            if( $receiver != null && ( $receiver->download_at == null || strlen( $receiver->download_at ) <= 0 ) ){
                $receiver->update(['download_at'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
            }

            $path = storage_path('app') . '/public/' . $document->pdf_file ;
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
                        storage_path('app') . '/public/' . $document->pdf_file
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
            $transaction = $this->resolveDocumentAccessTransaction($document->id, $user, $request);
            $receiver = $this->resolveDocumentAccessReceiver($transaction, $user, false);
            if ($transaction == null || ($receiver == null && (int) $transaction->sender_id !== (int) $user->id)) {
                return response()->json([
                    'ok' => false ,
                    'message' => 'អ្នកមិនមានសិទ្ធិទាញយកឯកសារទេ។'
                ],403);
            }

            if( $receiver != null && ( $receiver->preview_at == null || strlen( $receiver->preview_at ) <= 0 ) ){
                $receiver->update(['preview_at'=>\Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
            }

            $path = storage_path('app') . '/public/' . $document->pdf_file ;
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
                        storage_path('app') . '/public/' . $document->pdf_file
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
        $authenticatedUser = \Auth::user() != null
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
        $receiverIds = $this->getAuthenticatedReceiverIds($authenticatedUser);
        /**
         * នៅពេលទទួលការងារ អ្នកទទួល និងត្រូវបានកត់ត្រាថាជាអ្នកទទួលខុសត្រូវការងារ
         */
        // ត្រួតពិនិត្យប្រតិបត្តិការបញ្ជូន
        $previousTransaction = $this->resolveRequestedTransaction($request, $authenticatedUser);

        if( $previousTransaction == null ){
            return response()->json([
                'ok' => false ,
                'previous' => $previousTransaction ,
                'request' => $request->input() ,
                'message' => 'ប្រតិបត្តិការបញ្ជូនមិនមានឡើយ។'
            ],422);
        }


        if( ( $receiverPivot = $previousTransaction->receiversPivot()->whereIn('receiver_id', $receiverIds)->first() ) == null ){
            return response()->json([
                'ok' => false ,
                'ids' => $previousTransaction->receivers->toArray() ,
                'message' => 'អ្នកមិនមានសិទ្ធិទាញយកឯកសារទេ។'
            ],403);
        }
        $transaction = $this->createOrReuseUserTransactionFromPrevious(
            $previousTransaction,
            $authenticatedUser,
            $receiverPivot
        );

        $flowAction = $this->resolveRequestedFlowAction($request, 'diy');
        if ($flowAction === 'send') {
            $dispatchResult = $this->dispatchTransaction($transaction, $authenticatedUser, $request);
            if ($dispatchResult !== true) {
                return $dispatchResult;
            }
        }

        return response()->json([
            'ok' => true ,
            'receiver' => $receiverPivot ,
            'transaction' => $transaction->fresh()->toArray() ,
            'previous' => $previousTransaction->fresh()->toArray() ,
            'flow_action' => $flowAction,
            'message' => 'ជោគជ័យ'
        ],200);
    }
    public function lastSignature(){
        /**
         * ការកំណត់ហត្ថលេខាចុងក្រោយត្រូវពឹងផ្អែកលើគោនយោបាយ នៃតួនាទីរបស់ថ្នាក់ដឹកនាំ
         */
    }
    public function destroy(Request $request){
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

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'សូមចូលប្រើប្រាស់ជាមុនសិន។'
            ],401);
        }

        $record = intval( $request->id ) > 0 ? RecordModel::find( $request->id ) : null ;
        if( $record == null ){
            return response()->json([
                'ok' => false ,
                'record' => $record ,
                'message' => 'មិនមានព័ត៌មាននេះឡើយ។'
            ],403);
        }

        if ((int) $record->sender_id !== (int) $user->id) {
            return response()->json([
                'ok' => false,
                'message' => 'អ្នកមិនមានសិទ្ធិលប់ប្រតិបត្តិការនេះទេ។'
            ],403);
        }

        $record->deleted_by = $user->id;
        $record->updated_by = $user->id;
        $record->save();

        $result = $record->delete();
        return response()->json([
            'ok' => $result ,
            'message' => $result ? 'រួចរាល់' : 'មានបញ្ហាក្នុងការលប់។'
        ],200);
    }
    public function filterByStatus(Request $request){
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

        $status = false ;
        if( isset( $request->status ) && strlen( $request->status ) > 0 && in_array( $request->status , RecordModel::STATUSES ) ) {
            $status = $request->status ;
        }
        // else{
        //     return response()->json([
        //         'ok' => false ,
        //         'record' => $request->status ,
        //         'message' => 'ប្រភេទនៃឯកសារមិនត្រឹមត្រូវ។'
        //     ],422);
        // }

        if (!$user) {
            return response()->json([
                'ok' => false,
                'records' => [],
                'message' => 'សូមចូលប្រើប្រាស់ជាមុនសិន។'
            ],401);
        }

        $builder = RecordModel::query();
        $this->applyTransactionVisibilityScope($builder, $user);

        return response()->json([
            'ok' => true ,
            'records' => $status == false
                ? (clone $builder)
                    ->select('status', DB::raw('COUNT(*) as total'))
		            ->groupBy('status')
                    ->get()->pluck('total','status')->toArray()
                : [
                    $status => (clone $builder)->where('status', $status )->count()
                ]  ,
            'message' => 'រួចរាល់'
        ],200);
    }


/**
 * List focal people rules of an organization
 */
public function listOrganizationFocalPeople(Request $request)
{
    $builder = OrganizationFocalPeople::with([
        'organizationStructure',
        'organizationStructurePosition'
    ]);

    if ($request->filled('organization_structure_id')) {
        $builder->where(
            'organization_structure_id',
            $request->organization_structure_id
        );
    }

    return response()->json(
        $builder->get()
    );
}

/**
 * Assign focal receiver position to organization
 */
public function setFocalReceiver(Request $request)
{
    $request->validate([
        'organization_structure_id' => 'required|integer',
        'organization_structure_position_id' => 'required|integer',
        'is_default' => 'required|boolean'
    ]);

    $record = OrganizationFocalPeople::create([
        'organization_structure_id' => $request->organization_structure_id,
        'organization_structure_position_id' => $request->organization_structure_position_id,
        'is_default' => $request->is_default,
        'created_by' => Auth::id(),
        'updated_by' => Auth::id(),
    ]);

    return response()->json([
        'message' => 'Organization focal receiver configured successfully',
        'data' => $record
    ]);
}

/**
 * Update focal receiver rule
 */
public function updateFocalReceiver(Request $request, $id)
{
    $request->validate([
        'organization_structure_position_id' => 'required|integer',
        'is_default' => 'required|boolean'
    ]);

    $record = OrganizationFocalPeople::findOrFail($id);

    $record->update([
        'organization_structure_position_id' => $request->organization_structure_position_id,
        'is_default' => $request->is_default,
        'updated_by' => Auth::id(),
    ]);

    return response()->json([
        'message' => 'Organization focal receiver updated successfully',
        'data' => $record
    ]);
}

/**
 * Remove focal receiver rule (soft delete)
 */
public function removeFocalReceiver($id)
{
    $record = OrganizationFocalPeople::findOrFail($id);

    $record->update([
        'deleted_by' => Auth::id(),
        'updated_by' => Auth::id(),
    ]);

    $record->delete();

    return response()->json([
        'message' => 'Organization focal receiver removed'
    ]);
}

/**
 * Restore focal receiver rule
 */
public function restoreFocalReceiver($id)
{
    $record = OrganizationFocalPeople::withTrashed()->findOrFail($id);

    $record->restore();

    $record->update([
        'deleted_by' => null,
        'updated_by' => Auth::id(),
    ]);

    return response()->json([
        'message' => 'Organization focal receiver restored successfully'
    ]);
}

    private function addReceiverBaseOnOrganizationStructure($transaction , $organizationStructureId){
        $authenticatedUser = \Auth::user() != null
            ? \Auth::user()
            : (
                auth('api')->user()
                    ? auth('api')->user()
                    : (
                        request()->user() != null
                            ? request()->user()
                            : null
                    )
            );
        $actorId = $authenticatedUser != null && isset($authenticatedUser->id)
            ? intval($authenticatedUser->id)
            : intval($transaction->sender_id);
        /**
         * កំណត់អ្នកទទួលឯកសារ
         * ចាប់យកខុទ្ទកាល័យឧបនាយករដ្ឋមន្ត្រីប្រចាំការជាកន្លែងគោល
         * តែឯកសារនឹងត្រូវបានទៅអ្នកទទួលឯកសារ
         * ហើយសម្រាប់ការមើលឃើញគឺ អាចដល់ នាយករង នាយកខុទ្ទកាល័យ និងឧបនាយករដ្ឋមន្ត្រី
         * សម្រាប់ស្ថានភាពគឺតាមដំណាក់កាល
         */

        // Organizatoin -> 3 ខុទ្ទកាល័យឯកឧត្តមឧបនាយករដ្ឋមន្ត្រីប្រចាំការ
        // Officer -> 3604 មន្ត្រីនៅខុទ្ទកាល័យ
        // Officer -> 3048 , 3049 សមាជិកខុទ្ទកាល័យ
        // Officer -> 2484 ជំនួយការឧបនាយករដ្ឋមន្ត្រី
        /**
         * Focal People
         * Organizatoin Structure -> 3
         * Officer -> 3604 , 3048 , 3049 , 2484 , 172 (eng.touch)
         * បានបង្កើតជនបង្គោលសម្រាប់ថ្នាក់
         * ១. ខុទ្ទកាល័យឯកឧត្តមឧបនាយករដ្ឋមន្ត្រីប្រចាំការ
         * ២. អគ្គនាយកដ្ឋានបរិវត្តកម្មឌីជីថល
         * ៣. នាយកដ្ឋានបច្ចេកវិទ្យានិងប្រតិបត្តិការឌីជីថល
         */
        // $organizatoinStructureId = 3 ;

        /**
         * ក្នុងករណីដែលមានការកំណត់ជាក់លាក់នូវជនបង្គោលសម្រាប់ទទួលយកឯកសារចូល
         */
        if( ( $documentOrganizatoinFocalPeople = \App\Models\Document\OrganizationFocalPeople::where('organization_structure_id', $organizationStructureId )->get() ) != null ){
            foreach( $documentOrganizatoinFocalPeople AS $index => $documentOrganizatoinFocalPerson ){
                if( ( $receiver = \App\Models\Document\Receiver::where(
                    // Find by this
                    [
                        'document_transaction_id' => $transaction->id ,
                        'receiver_id' => $documentOrganizatoinFocalPerson->officer_id
                    ]
                )->first() ) != null ) {
                    // កែពេលវេលា និងអ្នកចូលកែ
                    $receiver->update([
                        'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i') ,
                        'updated_by' => $actorId
                    ]);
                }else{
                    // បង្កើតព័ត៌មានថ្មី
                    \App\Models\Document\Receiver::create(
                        [
                            'document_transaction_id' => $transaction->id ,
                            'receiver_id' => $documentOrganizatoinFocalPerson->officer_id ,
                            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i') ,
                            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i') ,
                            'updated_by' => $actorId ,
                            'created_by' => $actorId
                        ]
                    );
                }
                /**
                 * យកជនបង្គោលជាថ្នាក់ដឹកនាំនៃអង្គភាព
                 */
                // if( ( $job = $documentOrganizatoinFocalPerson->officer->jobs()->first() ) != null ){
                //     foreach( $job->getParentIdsInStructure() AS $parentId ){
                //         if( ( $receiver = \App\Models\Document\Receiver::where(
                //             // Find by this
                //             [
                //                 'document_transaction_id' => $transaction->id ,
                //                 'receiver_id' => $parentId
                //             ]
                //         )->first() ) != null ) {
                //             // កែពេលវេលា និងអ្នកចូលកែ
                //             $receiver->update([
                //                 'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i') ,
                //                 'updated_by' => $user->id
                //             ]);
                //         }else{
                //             // បង្កើតព័ត៌មានថ្មី
                //             \App\Models\Document\Receiver::create(
                //                 [
                //                     'document_transaction_id' => $transaction->id ,
                //                     'receiver_id' => $parentId ,
                //                     'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i') ,
                //                     'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i') ,
                //                     'updated_by' => $user->id ,
                //                     'created_by' => $user->id
                //                 ]
                //             );
                //         }
                //     }
                // }
            }
        }
    }

    private function getAuthenticatedReceiverIds($user)
    {
        if ($user == null) {
            return [];
        }

        $receiverIds = [$user->id];

        if ($user->officer != null) {
            $receiverIds[] = $user->officer->id;
        }

        return array_values(array_unique(array_filter($receiverIds, function ($id) {
            return intval($id) > 0;
        })));
    }

    private function applyTransactionVisibilityScope($builder, $user)
    {
        if ($user == null) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->whereNull((new RecordModel())->getTable() . '.deleted_at');

        $receiverIds = $this->getAuthenticatedReceiverIds($user);

        $builder->where(function ($query) use ($user, $receiverIds) {
            $query->where('sender_id', $user->id);

            if (!empty($receiverIds)) {
                $query->orWhereHas('receiversPivot', function ($queryBuilder) use ($receiverIds) {
                    $queryBuilder
                        ->whereNull('deleted_at')
                        ->whereIn('receiver_id', $receiverIds);
                });
            }
        });
    }

    private function assignNextWorkflowReceivers($transaction, $sender)
    {
        $workflowStep = $this->resolveWorkflowStep($sender);
        if ($workflowStep == null) {
            return 0;
        }

        $assignedReceivers = 0;
        if (isset($workflowStep['organization_structure_id'])) {
            $transaction->update([
                'organization_structure_id' => $workflowStep['organization_structure_id'],
                'updated_by' => $sender->id,
            ]);
        }

        if (
            isset($workflowStep['organization_structure_id']) &&
            !isset($workflowStep['position_name']) &&
            !isset($workflowStep['usernames'])
        ) {
            $this->addReceiverBaseOnOrganizationStructure($transaction, $workflowStep['organization_structure_id']);
            return $transaction->receiversPivot()->count();
        }

        $receiverIds = $this->findOfficerIdsByWorkflowStep($workflowStep);
        foreach ($receiverIds as $receiverId) {
            $exists = \App\Models\Document\Receiver::where([
                'document_transaction_id' => $transaction->id,
                'receiver_id' => $receiverId,
            ])->first();

            if ($exists == null) {
                \App\Models\Document\Receiver::create([
                    'document_transaction_id' => $transaction->id,
                    'receiver_id' => $receiverId,
                    'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                    'updated_by' => $sender->id,
                    'created_by' => $sender->id,
                ]);
                $assignedReceivers++;
            }
        }

        return $assignedReceivers;
    }

    private function dispatchTransaction($transaction, $sender, Request $request)
    {
        if ($transaction->sent_at != null && strlen(trim((string) $transaction->sent_at)) > 0) {
            if (!$this->canRedispatchTransaction($transaction, $request, $sender)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'ឯកសារនេះត្រូវបានបញ្ជូនរួចហើយ។'
                ],422);
            }

            $this->resetTransactionForRedispatch($transaction, $sender->id);
        }

        if (
            $transaction->previous_transaction_id == null &&
            (
                ($transaction->document == null) ||
                (
                    $transaction->document != null &&
                    ($transaction->document->word_file == null || strlen($transaction->document->word_file) <= 0) &&
                    ($transaction->document->pdf_file == null || strlen($transaction->document->pdf_file) <= 0)
                )
            )
        ) {
            return response()->json([
                'ok' => false,
                'message' => 'ប្រតិបត្តិការនៅដើមគ្រាមិនអាចគ្មានឯកសារយោងភ្ជាប់ជាមួយឡើយ។'
            ],422);
        }

        $receiverCount = $this->syncRequestedReceivers($transaction, $sender, $request);
        if ($receiverCount <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'សូមបញ្ជាក់អ្នកទទួល ឬអង្គភាពដែលត្រូវបញ្ជូនទៅ។'
            ],422);
        }

        $transaction->send();

        return true;
    }

    private function resolveSendableTransactionForUser($transaction, $user)
    {
        if ((int) $transaction->sender_id === (int) $user->id) {
            return $transaction;
        }

        $receiverPivot = $transaction->receiversPivot()
            ->whereIn('receiver_id', $this->getAuthenticatedReceiverIds($user))
            ->first();

        if ($receiverPivot == null) {
            return response()->json([
                'ok' => false,
                'message' => 'អ្នកមិនមានសិទ្ធិបញ្ជូនប្រតិបត្តិការនេះទេ។'
            ],403);
        }

        $nextTransaction = null;
        if (intval($transaction->next_transaction_id) > 0) {
            $nextTransaction = RecordModel::find($transaction->next_transaction_id);
        }

        if ($nextTransaction != null && (int) $nextTransaction->sender_id === (int) $user->id) {
            return $nextTransaction;
        }

        return $this->createOrReuseUserTransactionFromPrevious($transaction, $user, $receiverPivot);
    }

    private function createOrReuseUserTransactionFromPrevious($previousTransaction, $authenticatedUser, $receiverPivot)
    {
        $existingNextTransaction = RecordModel::query()
            ->where('previous_transaction_id', $previousTransaction->id)
            ->where('sender_id', $authenticatedUser->id)
            ->orderByDesc('id')
            ->first();

        if ($existingNextTransaction != null) {
            if ($receiverPivot->accepted_at == null || strlen((string) $receiverPivot->accepted_at) <= 0) {
                $receiverPivot->update(['accepted_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
            }

            if ((int) $previousTransaction->next_transaction_id !== (int) $existingNextTransaction->id) {
                $previousTransaction->update([
                    'next_transaction_id' => $existingNextTransaction->id,
                    'status' => RecordModel::STATUS_FINISHED,
                ]);
            }

            return $existingNextTransaction;
        }

        if ($receiverPivot->accepted_at == null || strlen((string) $receiverPivot->accepted_at) <= 0) {
            $receiverPivot->update(['accepted_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')]);
        }

        $dateIn = \Carbon\Carbon::now();
        $subject = $previousTransaction->subject;

        $transaction = RecordModel::create([
            'document_id' => null,
            'sender_id' => $authenticatedUser->id,
            'subject' => $subject,
            'date_in' => $dateIn->format('Y-m-d H:i:s'),
            'previous_transaction_id' => $previousTransaction->id,
            'organization_structure_id' => $this->resolveCurrentOrganizationStructureId($authenticatedUser),
            'tpid' => strlen($previousTransaction->tpid) > 0 ? $previousTransaction->tpid . ':' . $previousTransaction->id . ':' : $previousTransaction->id . ':',
            'status' => RecordModel::STATUS_PROGRESS,
            'created_by' => $authenticatedUser->id,
            'updated_by' => $authenticatedUser->id,
            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
        ]);

        $previousTransaction->update([
            'next_transaction_id' => $transaction->id,
            'status' => RecordModel::STATUS_FINISHED,
        ]);

        $transaction->update(['document_id' => $previousTransaction->document_id]);

        return $transaction;
    }

    private function syncRequestedReceivers($transaction, $sender, Request $request)
    {
        $assignedReceivers = 0;

        $organizationStructureId = $this->resolveRequestedOrganizationStructureId($request);
        if ($organizationStructureId > 0) {
            $transaction->update([
                'organization_structure_id' => $organizationStructureId,
                'updated_by' => $sender->id,
            ]);

            $existingCount = $transaction->receiversPivot()->count();
            $this->addReceiverBaseOnOrganizationStructure($transaction, $organizationStructureId);
            $assignedReceivers += max(0, $transaction->receiversPivot()->count() - $existingCount);
        }

        $receiverIds = $this->resolveRequestedReceiverIds($request);
        if (!empty($receiverIds)) {
            $assignedReceivers += $this->attachReceivers($transaction, $receiverIds, $sender->id);
        }

        if ($assignedReceivers <= 0 && $transaction->receiversPivot()->count() <= 0) {
            $assignedReceivers += $this->assignNextWorkflowReceivers($transaction, $sender);
        }

        return max($assignedReceivers, $transaction->receiversPivot()->count());
    }

    private function attachReceivers($transaction, array $receiverIds, $actorId)
    {
        $attachedCount = 0;

        foreach ($receiverIds as $receiverId) {
            $receiverId = intval($receiverId);
            if ($receiverId <= 0) {
                continue;
            }

            $exists = \App\Models\Document\Receiver::where([
                'document_transaction_id' => $transaction->id,
                'receiver_id' => $receiverId,
            ])->first();

            if ($exists != null) {
                continue;
            }

            \App\Models\Document\Receiver::create([
                'document_transaction_id' => $transaction->id,
                'receiver_id' => $receiverId,
                'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
                'updated_by' => $actorId,
                'created_by' => $actorId,
            ]);

            $attachedCount++;
        }

        return $attachedCount;
    }

    private function resolveRequestedFlowAction(Request $request, $default = 'send')
    {
        $flowAction = null;

        foreach (['flow_action', 'action', 'mode'] as $field) {
            if ($request->filled($field)) {
                $flowAction = strtolower(trim((string) $request->input($field)));
                break;
            }
        }

        if ($flowAction === null && $request->has('is_diy')) {
            $flowAction = filter_var($request->input('is_diy'), FILTER_VALIDATE_BOOLEAN)
                ? 'diy'
                : 'send';
        }

        if (in_array($flowAction, ['diy', 'self', 'do_it_myself'], true)) {
            return 'diy';
        }

        if ($flowAction === 'send') {
            return 'send';
        }

        return $default;
    }

    private function requestHasDispatchTargets(Request $request)
    {
        return $this->resolveRequestedOrganizationStructureId($request) > 0
            || !empty($this->resolveRequestedReceiverIds($request));
    }

    private function resolveRequestedOrganizationStructureId(Request $request)
    {
        foreach ([
            'organization_structure_id',
            'organizatoin_structure_id',
            'organization_id',
            'organization',
            'target',
            'selected_organization',
        ] as $field) {
            $organizationStructureId = $this->extractIdFromMixed($request->input($field));
            if ($organizationStructureId > 0) {
                return $organizationStructureId;
            }
        }

        foreach (['transaction', 'record', 'current_transaction', 'currentTransaction', 'item'] as $containerField) {
            $container = $request->input($containerField);
            if (is_array($container) || is_object($container)) {
                foreach (['organization_structure_id', 'organizatoin_structure_id', 'organization_id', 'organization'] as $field) {
                    $organizationStructureId = $this->extractIdFromMixed(data_get($container, $field));
                    if ($organizationStructureId > 0) {
                        return $organizationStructureId;
                    }
                }
            }
        }

        $organizations = $request->input('organizations');
        if (is_array($organizations)) {
            foreach ($organizations as $organization) {
                $organizationStructureId = $this->extractIdFromMixed($organization);
                if ($organizationStructureId > 0) {
                    return $organizationStructureId;
                }
            }
        }

        if (is_string($organizations)) {
            foreach (array_filter(array_map('trim', explode(',', $organizations))) as $organization) {
                $organizationStructureId = intval($organization);
                if ($organizationStructureId > 0) {
                    return $organizationStructureId;
                }
            }
        }

        return 0;
    }

    private function resolveRequestedReceiverIds(Request $request)
    {
        $receiverInput = $request->input('receiver_ids');
        if ($receiverInput === null) {
            $receiverInput = $request->input('receivers');
        }
        if ($receiverInput === null) {
            $receiverInput = $request->input('receiver_id');
        }
        if ($receiverInput === null) {
            $receiverInput = $request->input('receiver');
        }
        if ($receiverInput === null) {
            $receiverInput = $request->input('selected_receiver');
        }
        if ($receiverInput === null) {
            $receiverInput = [];
        }

        if (is_string($receiverInput)) {
            $receiverInput = array_filter(array_map('trim', explode(',', $receiverInput)));
        }

        if (is_numeric($receiverInput)) {
            $receiverInput = [$receiverInput];
        }

        if (!is_array($receiverInput)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(function ($receiverId) {
            if (is_array($receiverId)) {
                foreach (['receiver_id', 'officer_id', 'id', 'value', 'user_id'] as $field) {
                    if (isset($receiverId[$field]) && intval($receiverId[$field]) > 0) {
                        return intval($receiverId[$field]);
                    }
                }

                return 0;
            }

            if (is_object($receiverId)) {
                foreach (['receiver_id', 'officer_id', 'id', 'value', 'user_id'] as $field) {
                    if (isset($receiverId->{$field}) && intval($receiverId->{$field}) > 0) {
                        return intval($receiverId->{$field});
                    }
                }

                return 0;
            }

            return intval($receiverId);
        }, $receiverInput), function ($receiverId) {
            return $receiverId > 0;
        })));
    }

    private function canRedispatchTransaction($transaction, Request $request, $sender = null)
    {
        if ($transaction->status !== RecordModel::STATUS_PENDING) {
            return false;
        }

        if ($transaction->receiversPivot()->whereNotNull('accepted_at')->exists()) {
            return false;
        }

        if (
            $this->resolveRequestedOrganizationStructureId($request) > 0
            || !empty($this->resolveRequestedReceiverIds($request))
        ) {
            return true;
        }

        return $sender != null && $this->resolveWorkflowStep($sender) != null;
    }

    private function resolveRequestedTransaction(Request $request, $user = null)
    {
        foreach ([
            'transaction_id',
            'document_transaction_id',
            'id',
            'record_id',
            'selected_transaction',
        ] as $field) {
            $transactionId = $this->extractIdFromMixed($request->input($field));
            if ($transactionId > 0) {
                return RecordModel::find($transactionId);
            }
        }

        foreach (['transaction', 'record', 'current_transaction', 'currentTransaction', 'item', 'document'] as $containerField) {
            $container = $request->input($containerField);
            if (!is_array($container) && !is_object($container)) {
                continue;
            }

            foreach (['transaction_id', 'document_transaction_id', 'id'] as $field) {
                $transactionId = $this->extractIdFromMixed(data_get($container, $field));
                if ($transactionId > 0) {
                    return RecordModel::find($transactionId);
                }
            }

            foreach (['document_id', 'id'] as $field) {
                $documentId = $this->extractIdFromMixed(data_get($container, $field));
                if ($documentId > 0) {
                    $transaction = $this->findVisibleTransactionByDocumentId($documentId, $user);
                    if ($transaction != null) {
                        return $transaction;
                    }
                }
            }
        }

        foreach (['document_id', 'selected_document'] as $field) {
            $documentId = $this->extractIdFromMixed($request->input($field));
            if ($documentId > 0) {
                $transaction = $this->findVisibleTransactionByDocumentId($documentId, $user);
                if ($transaction != null) {
                    return $transaction;
                }
            }
        }

        return null;
    }

    private function findVisibleTransactionByDocumentId($documentId, $user = null)
    {
        $builder = RecordModel::query()
            ->where('document_id', $documentId)
            ->whereNull('deleted_at')
            ->orderByDesc('id');

        if ($user != null) {
            $receiverIds = $this->getAuthenticatedReceiverIds($user);
            $builder->where(function ($query) use ($user, $receiverIds) {
                $query->where('sender_id', $user->id);

                if (!empty($receiverIds)) {
                    $query->orWhereHas('receiversPivot', function ($queryBuilder) use ($receiverIds) {
                        $queryBuilder
                            ->whereNull('deleted_at')
                            ->whereIn('receiver_id', $receiverIds);
                    });
                }
            });
        }

        return $builder->first();
    }

    private function extractIdFromMixed($value)
    {
        if (is_numeric($value)) {
            return intval($value);
        }

        if (is_string($value) && intval($value) > 0) {
            return intval($value);
        }

        if (is_array($value)) {
            foreach (['id', 'value', 'transaction_id', 'document_transaction_id', 'organization_structure_id', 'organization_id', 'receiver_id', 'officer_id', 'document_id'] as $field) {
                if (isset($value[$field]) && intval($value[$field]) > 0) {
                    return intval($value[$field]);
                }
            }
        }

        if (is_object($value)) {
            foreach (['id', 'value', 'transaction_id', 'document_transaction_id', 'organization_structure_id', 'organization_id', 'receiver_id', 'officer_id', 'document_id'] as $field) {
                if (isset($value->{$field}) && intval($value->{$field}) > 0) {
                    return intval($value->{$field});
                }
            }
        }

        return 0;
    }

    private function resolveDocumentAccessTransaction($documentId, $user, Request $request)
    {
        $transaction = $this->resolveRequestedTransaction($request, $user);
        if ($transaction != null && intval($transaction->document_id) === intval($documentId)) {
            return $transaction;
        }

        return $this->findVisibleTransactionByDocumentId($documentId, $user);
    }

    private function resolveDocumentAccessReceiver($transaction, $user, $requireAccepted)
    {
        if ($transaction == null || $user == null) {
            return null;
        }

        $query = $transaction->receiversPivot()
            ->whereNull('deleted_at')
            ->whereIn('receiver_id', $this->getAuthenticatedReceiverIds($user));

        if ($requireAccepted) {
            $query->whereNotNull('accepted_at');
        }

        return $query->first();
    }

    private function resetTransactionForRedispatch($transaction, $actorId)
    {
        $timestamp = \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        $transaction->receiversPivot()
            ->whereNull('accepted_at')
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => $timestamp,
                'deleted_by' => $actorId,
                'updated_at' => $timestamp,
                'updated_by' => $actorId,
            ]);

        $transaction->update([
            'sent_at' => null,
            'status' => RecordModel::STATUS_PROGRESS,
            'updated_at' => $timestamp,
            'updated_by' => $actorId,
        ]);
    }

    private function resolveCurrentOrganizationStructureId($user)
    {
        if ($user == null || $user->officer == null) {
            return null;
        }

        $job = $user->officer->getCurrentJob();
        if ($job == null || $job->organizationStructurePosition == null) {
            return null;
        }

        return intval($job->organizationStructurePosition->organization_structure_id) > 0
            ? intval($job->organizationStructurePosition->organization_structure_id)
            : null;
    }

    private function serializeWorkflowOfficer($officer)
    {
        if ($officer == null) {
            return null;
        }

        $job = $officer->getCurrentJob();
        $positionName = $job != null
            && $job->organizationStructurePosition != null
            && $job->organizationStructurePosition->position != null
            ? $job->organizationStructurePosition->position->name
            : null;
        $organizationName = $job != null
            && $job->organizationStructurePosition != null
            && $job->organizationStructurePosition->organizationStructure != null
            && $job->organizationStructurePosition->organizationStructure->organization != null
            ? $job->organizationStructurePosition->organizationStructure->organization->name
            : null;
        $fullname = $officer->user != null
            ? trim($officer->user->lastname . ' ' . $officer->user->firstname)
            : '';

        return [
            'id' => $officer->id,
            'code' => $officer->code,
            'fullname' => $fullname,
            'Countesy' => optional($officer->people?->countesy)->name ?? '',
            'countesy_name' => optional($officer->people?->countesy)->name ?? '',
            'username' => $officer->user != null ? $officer->user->username : null,
            'email' => $officer->user != null ? $officer->user->email : $officer->email,
            'current_position' => $positionName,
            'current_organization' => $organizationName,
            'position' => [
                'name' => $positionName,
            ],
            'organization' => [
                'name' => $organizationName,
            ],
        ];
    }

    private function buildWorkflowReceiverPayloads($transactionId)
    {
        return \App\Models\Document\Receiver::query()
            ->where('document_transaction_id', $transactionId)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get()
            ->map(function ($receiverPivot) {
                $officer = \App\Models\Officer\Officer::with([
                    'people.countesy',
                    'user',
                    'jobs.organizationStructurePosition.position',
                    'jobs.organizationStructurePosition.organizationStructure.organization'
                ])->find($receiverPivot->receiver_id);

                return [
                    'id' => $receiverPivot->id,
                    'receiver_id' => $receiverPivot->receiver_id,
                    'accepted_at' => $receiverPivot->accepted_at,
                    'seen_at' => $receiverPivot->seen_at,
                    'preview_at' => $receiverPivot->preview_at,
                    'download_at' => $receiverPivot->download_at,
                    'created_at' => $receiverPivot->created_at,
                    'updated_at' => $receiverPivot->updated_at,
                    'user' => $this->serializeWorkflowOfficer($officer),
                ];
            })
            ->values();
    }

    private function resolveWorkflowStep($sender)
    {
        if ($sender == null || $sender->officer == null) {
            return null;
        }

        $job = $sender->officer->getCurrentJob();
        if ($job == null || $job->organizationStructurePosition == null) {
            return null;
        }

        $organizationStructureId = $job->organizationStructurePosition->organization_structure_id;
        $positionName = $job->organizationStructurePosition->position != null
            ? trim($job->organizationStructurePosition->position->name)
            : '';
        $organizationName = $job->organizationStructurePosition->organizationStructure != null
            && $job->organizationStructurePosition->organizationStructure->organization != null
            ? trim($job->organizationStructurePosition->organizationStructure->organization->name)
            : '';

        $isAdministrationDepartment = in_array($organizationName, [
            'នាយកដ្ឋានរដ្ឋបាល',
            'អគ្គនាយកដ្ឋានរដ្ឋបាល និងហិរញ្ញវត្ថុ',
        ], true);

        if ($isAdministrationDepartment && $positionName !== 'ប្រធាននាយកដ្ឋាន') {
            return [
                'organization_structure_id' => $organizationStructureId,
                'position_name' => 'ប្រធាននាយកដ្ឋាន',
            ];
        }

        if ($isAdministrationDepartment && $positionName === 'ប្រធាននាយកដ្ឋាន') {
            return [
                'organization_structure_id' => $this->resolveOrganizationStructureIdByName(
                    'ខុទ្ទកាល័យឯកឧត្តមឧបនាយករដ្ឋមន្រ្តីប្រចាំការ'
                ),
                'usernames' => ['docflow.cabinet.director@ocm.gov.kh'],
            ];
        }

        if ($organizationName === 'ខុទ្ទកាល័យឯកឧត្តមឧបនាយករដ្ឋមន្រ្តីប្រចាំការ' && $positionName === 'នាយកខុទ្ទកាល័យ') {
            return [
                'organization_structure_id' => $organizationStructureId,
                'usernames' => ['docflow.office.dpm@ocm.gov.kh'],
            ];
        }

        if ($organizationName === 'ខុទ្ទកាល័យឯកឧត្តមឧបនាយករដ្ឋមន្រ្តីប្រចាំការ' && $positionName === 'មន្ត្រី') {
            return [
                'organization_structure_id' => $this->resolveOrganizationStructureIdByName(
                    'នាយកដ្ឋានបច្ចេកវិទ្យានិងប្រតិបត្តិការឌីជីថល'
                ),
                'usernames' => ['docflow.specialist.unit@ocm.gov.kh'],
            ];
        }

        if ($organizationName === 'នាយកដ្ឋានបច្ចេកវិទ្យានិងប្រតិបត្តិការឌីជីថល') {
            return null;
        }

        return null;
    }

    private function resolveOrganizationStructureIdByName($organizationName)
    {
        static $cache = [];

        $organizationName = trim((string) $organizationName);
        if ($organizationName === '') {
            return null;
        }

        if (array_key_exists($organizationName, $cache)) {
            return $cache[$organizationName];
        }

        $organizationStructureId = \App\Models\Organization\OrganizationStructure::query()
            ->whereHas('organization', function ($query) use ($organizationName) {
                $query->where('name', $organizationName);
            })
            ->orderBy('id')
            ->value('id');

        $cache[$organizationName] = $organizationStructureId != null
            ? intval($organizationStructureId)
            : null;

        return $cache[$organizationName];
    }

    private function findOfficerIdsByWorkflowStep($workflowStep)
    {
        if (isset($workflowStep['usernames']) && is_array($workflowStep['usernames']) && !empty($workflowStep['usernames'])) {
            return \App\Models\Officer\Officer::query()
                ->whereHas('user', function ($query) use ($workflowStep) {
                    $query->whereIn('username', $workflowStep['usernames']);
                })
                ->pluck('id')
                ->filter(function ($id) {
                    return intval($id) > 0;
                })->unique()->values()->toArray();
        }

        $builder = \App\Models\Officer\OfficerJob::query()
            ->whereNull('end')
            ->whereHas('organizationStructurePosition', function ($query) use ($workflowStep) {
                $query->where('organization_structure_id', $workflowStep['organization_structure_id']);

                if (isset($workflowStep['position_name'])) {
                    $query->whereHas('position', function ($positionQuery) use ($workflowStep) {
                        $positionQuery->where('name', $workflowStep['position_name']);
                    });
                }
            });

        return $builder->pluck('officer_id')->filter(function ($id) {
            return intval($id) > 0;
        })->unique()->values()->toArray();
    }
}
