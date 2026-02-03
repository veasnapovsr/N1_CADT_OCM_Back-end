<?php

namespace App\Http\Controllers\Api\AuthenticationCenter\Document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionPolicyController extends Controller
{
    private $model = null ;
    private $fields = [ 'id','name', 'keyname' , 'desp' , 'pid' , 'tpid' , 'prefix', 'record_index' , 'active' ,'officer_id' , 'organization_id'] ;
    public function __construct(){
        $this->model = new RecordModel();
    }
    /**
     * Listing function
     */
    public function index(Request $request){
        /** Format from query string */
        $search = isset( $request->search ) && $request->serach !== "" ? $request->search : false ;
        $perPage = isset( $request->perPage ) && $request->perPage !== "" ? intval( $request->perPage ) : 50 ;
        $page = isset( $request->page ) && $request->page !== "" ? intval( $request->page ) : 1 ;
        $id = isset( $request->id ) && intval( $request->id ) > 0? intval( $request->id ) : false ; 

        $root = $id > 0 
            ? RecordModel::where('id',$id)->first()
            : null ;
        if( $root != null ){
            $root->parentNode;
            $root->totalChilds = $root->totalChildNodesOfAllLevels();   
        }
        
        $queryString = [
            "where" => [
                // 'default' => [
                //     $pid > 0 ? [
                //         'field' => 'pid' ,
                //         'value' => $pid
                //     ] : [] ,
                // ],
                
                // 'in' => [] ,

                // 'not' => [
                //     [
                //         'field' => 'id' ,
                //         'value' => 4
                //     ]
                // ] ,
                'like' => [
                    $root != null 
                    ? [
                        'field' => 'tpid' ,
                        'value' => ( intval( $root->pid ) > 0 ? $root->pid.":" : '' ) . $root->id . "%"
                    ]
                    : [],
                    // [
                    //     'field' => 'year' ,
                    //     'value' => $date === false ? "" : $date
                    // ]
                ] ,
            ] ,
            "pagination" => [
                'perPage' => $perPage,
                'page' => $page
            ],
            "search" => $search === false ? [] : [
                'value' => $search ,
                'fields' => [
                    'name' , 'desp'
                ]
            ],
            "order" => [
                'field' => 'name' ,
                'by' => 'asc'
            ],
        ];
        $request->merge( $queryString );

        $crud = new CrudController(new RecordModel(), $request, $this->fields , false , false , [
            'totalChilds' => function($record){
                // return $record->totalChildNodesOfAllLevels();
                return 0 ;
            },
            'parentId' => function($record){
                return $record->pid;
            }
        ] );

        $crud->setRelationshipFunctions([
            'parentNode' => [
                'id' , 'name'
            ],
            'childNodes' => [
                'id' , 'name'
            ]
        ]);

        $builder = $crud->getListBuilder();
        $responseData = $crud->pagination(true , $builder );
        $responseData['message'] = __("crud.read.success");
        $responseData['ok'] = true ;
        return response()->json($responseData);
    }
    public function read(Request $request){
        if( !isset( $request->id ) || $request->id < 0 ){
            return response()->json([
                'ok' => false ,
                'message' => 'សូមបញ្ជាក់អំពីលេខសម្គាល់។'
            ],201);
        }
        $record = RecordModel::find($request->id);
        if( $record == null ){
            return response()->json([
                'ok' => false ,
                'message' => 'មិនមានព័ត៌មាននេះឡើយ។'
            ],201);
        }
        return response()->json([
            'record' => $record ,
            'ok' => true ,
            'message' => 'រួចរាល់'
        ],200);
    }
    /**
     * Create an account
     */
    public function store(Request $request){
        // Get parent
        $parentNode = isset( $request->pid ) && intval( $request->pid ) > 0 ? RecordModel::find( $request->pid ) : null ;

        // let create organization without the parent organization
        // if( $parentNode == null ){
        //     return response()->json([
        //         'ok' => false ,
        //         'message' => 'ស្ថាប័នមេ នេះមិនមានឡើយ។'
        //     ],403);
        // }
        $record = strlen( $request->name ) > 0 ? RecordModel::where('keyname', str_replace( [ ' ' , '​' ] , '' , $request->name ) )->first() : null ;
        if( $record != null ){
            return response()->json([
                'ok' => false ,
                'message' => 'ឈ្មោះនេះមានរួចហើយ'
            ],403);
        }
        // អ្នកប្រើប្រាស់ មិនទាន់មាននៅឡើយទេ
        $record = RecordModel::create([
            'keyname' => str_replace( [ ' ' , '​' ] , '' , $request->name ) ,
            'name' => $request->name,
            'desp' => $request->desp ,
            'pid' => $parentNode == null ? null : $parentNode->id ,
            'tpid' => $parentNode != null ? ( $parentNode->tpid != null && $parentNode->tpid != "" ? $parentNode->tpid .':'. $parentNode->id . ':' : "0:".$parentNode->id ). ':' : "0". ':',
            'image' => null ,
            'prefix' => $request->prefix??''
        ]);

        if( $record ){
            return response()->json([
                'record' => $record ,
                'ok' => true ,
                'message' => 'បង្កើតបានរួចរាល់'
            ], 200);

        }else {
            return response()->json([
                'user' => null ,
                'ok' => false ,
                'message' => 'មានបញ្ហា។'
            ], 201);
        }
    }
    /**
     * Update an account
     */
    public function update(Request $request){
        // Get parent
        $parentNode = intval( $request->pid ) > 0 ? RecordModel::find( $request->pid ) : 0 ;
        // if( $parentNode == null ){
        //     return response()->json([
        //         'ok' => false ,
        //         'message' => 'ស្ថាប័នមេនេះមិនមានឡើយ។'
        //     ],403);
        // }
        $record = strlen( $request->name ) > 0 ? RecordModel::where([
            'keyname' => str_replace( [ ' ' , '​' ] , '' , $request->name )
        ])->first() : null ;

        if( $record != null && $record->id != $request->id ){
            return response()->json([
                'ok' => false ,
                'message' => 'ឈ្មោះនេះមានរួចហើយ'
            ],403);
        }
        $record = intval( $request->id ) > 0 ? RecordModel::find($request->id) : null ;
        if( $record == null ){
            return response()->json([
                'ok' => false ,
                'message' => 'សូមបញ្ជាក់លេខសម្គាល់ស្ថាប័ន។'
            ],403);
        }
        $updateData = [
            'keyname' => str_replace( [ ' ' , '​' ] , '' , $request->name ) ,
            'name' => $request->name ,
            'desp' => $request->desp ,
            'pid' => $parentNode == null ? null : $parentNode->id ,
            'tpid' => $parentNode != null ? ( $parentNode->tpid != null && $parentNode->tpid != "" ? $parentNode->tpid .':'. $parentNode->id. ':' : "0:".$parentNode->id. ':' ) : "0". ':',
            'prefix' => $request->prefix??''
        ];
        $record->update( $updateData );
        return response()->json([
            'record' => $record ,
            'message' => 'កែប្រែព័ត៌មានរួចរាល់ !' ,
            'ok' => true
        ], 200);
    }
    /**
     * Active function of the account
     */
    public function active(Request $request){
        $record = RecordModel::find($request->id) ;
        if( $record ){
            $record->active = $request->active ;
            $record->save();
            // record does exists
            return response([
                'record' => $record ,
                'ok' => true ,
                'message' => 'ជោគជ័យ !' 
                ],
                200
            );
        }else{
            // record does not exists
            return response([
                'record' => null ,
                'ok' => false ,
                'message' => 'សូមទោស មិនមានទេ !' 
                ],
                201
            );
        }
    }
    /**
     * Unactive function of the account
     */
    public function unactive(Request $request){
        $record = RecordModel::find($request->id) ;
        if( $record ){
            $record->active = 0 ;
            $record->save();
            // Urecordser does exists
            return response([
                'ok' => true ,
                'record' => $record ,
                'message' => 'ជោគជ័យ !' 
                ],
                200
            );
        }else{
            // User does not exists
            return response([
                'record' => null ,
                'ok' => false ,
                'message' => 'សូមទោសមិនមានទេ !' ],
                201
            );
        }
    }
    /**
     * Function delete an account
     */
    public function destroy(Request $request){
        $record = RecordModel::find($request->id) ;
        if( $record ){
            $record->deleted_at = \Carbon\Carbon::now() ;
            $record->save();
            // User does exists
            return response([
                'ok' => true ,
                'record' => $record ,
                'message' => ' បានលុបដោយជោគជ័យ !' ,
                'ok' => true 
                ],
                200
            );
        }else{
            // User does not exists
            return response([
                'ok' => false ,
                'record' => null ,
                'message' => 'សូមទោស ព័ត៌មាននេះមិនមានទេ !' ],
                201
            );
        }
    }
    /**
     * Create child
     */
    public function addChild(Request $request){
        $parent = intval( $request->pid ) > 0 
            ? RecordModel::find($request->pid) 
            : null ;
        $child = intval( $request->cid ) > 0 
            ? RecordModel::find($request->cid) 
            : null ;
        if( $parent == null || $child == null ){
            return response()->json([
                'ok' => false ,
                'message' => "សូមជ្រើសរើស អង្គភាពមេ និង អង្គភាពចំណុះ។"
            ],350);
        }
        $child->pid = $parent->id ;
        $child->tpid = $parent != null && $parent->tpid != "" ? $parent->tpid .':'. $parent->id . ':' : "0:".$parent->id . ':' ;
        
        $child->save();
        return response()->json([
            'child' => $child ,
            'parent' => $parent ,
            'ok' => true ,
            'message' => 'បានភ្ជាប់អង្គភាបចំណុះរួចរាល់។'
        ], 200);
    }
    /**
     * Get staffs of the organization
     */
    public function getStaffs(Request $request){
        $organization = intval( $request->id ) > 0 ? RecordModel::find( $request->id ) : null ;
        if( $organization == null ){
            return response()->json([
                'message' => 'សូមបញ្ជាក់អង្គភាព។' ,
                'ok' => false
            ],500);
        }
        
        return response([
            'ok' => true ,
            // 'records' => $records ,
            // 'message' => count( $records ) > 0 ? " មានថតឯកសារចំនូួន ៖ " . count( $records ) : "មិនមានថតឯកសារត្រូវជាមួយការស្វែងរកនេះឡើយ !"
        ],200 );
    }
    
    public function getStructure(Request $request){
        $transactionPolicy = intval( $request->transaction_policy_id ) ? RecordModel::find( $request->transaction_policy_id ) : null ;
        if( $transactionPolicy == null ){
            return response()->json([
                'ok' => true ,
                'records' => [] ,
                'message' => 'មិនមានបញ្ជាក់អង្គភាពមេ។'
            ],200);
        }

        return response()->json([
            'ok' => true ,
            'message' => 'រួចរាល់។'
        ],200);
    }
    /**
     * Create organization structure
     */
    public function addStructure(Request $request){
        $user = \Auth::user() ;
        if( $user == null ){
            return response()->json([
                'ok' => false ,
                'message' => 'សូមបញ្ជាក់អត្តសញ្ញាណ។'
            ],403);
        }
        // Get parent
        $parentNode = isset( $request->pid ) && intval( $request->pid ) > 0 ? RecordModel::find( $request->pid ) : null ;
        // Get child
        $organizationStructure = isset( $request->organization_structure_id ) && intval( $request->organization_structure_id ) > 0 ? \App\Models\Document\OrganizatoinStructure::find( $request->organization_structure_id ) : null ;
        if( $organizatoinStructure == null ){
            return response()->json([
                'ok' => false ,
                'message' => 'ស្ថាប័ន នេះមិនមានឡើយ។'
            ],500);
        }
        if( $organizationStructure != null ){
            // Get the root
            // $tpid = explode( ':' , $organizationStructure->tpid );
            // if( 
            //     count( $tpid ) > 1 &&
            //     ( 
            //         $root = ( 
            //             ( $rootId = intval( $tpid[1] ) ) > 0 
            //                 ? OrganizationStructure::find( $rootId ) 
            //                 : null 
            //         )
            //     )  != null
            // ){
            //     // return with the existing OrganizationStructure
            //     $root->organization;
            //     return response()->json([
            //         'ok' => true ,
            //         'record' => $root ,
            //         'records' => OrganizationStructure::where('tpid','like', $root->tpid .':'. $root->id . '%' )->with('organization')->get() ,
            //         'message' => 'អង្គភាពនេះបានប្រើរួចហើយ'
            //     ],200);
            // }else{
                $organizationStructure->organization;
                return response()->json([
                    'ok' => true ,
                    'record' => $organizationStructure ,
                    'records' => OrganizationStructure::where('tpid','like', $organizationStructure->tpid .':'. $organizationStructure->id . '%' )->with('organization')->get() ,
                    'message' => 'អង្គភាពនេះបានប្រើរួចហើយ'
                ],200);
            // }
        }

        $organizationStructure = null ;
        if( $parentNode == null ){
            // This is root
            $organizationStructure = OrganizationStructure::where('organization_id',$organization->id)->where(function($query){
                $query->whereNull('pid')
                ->orWhere('pid',0);
            })->first() ;
            if( $organizationStructure == null ){
                $organizationStructure = OrganizationStructure::create([
                    'organization_id' => $organization->id ,
                    'pid' => 0 ,
                    'tpid' => $parentNode == null > 0 ? '0'. ':' : $parentNode->tpid .':'. $parentNode->id . ':',
                    'desp' => '' ,
                    'image' => '' ,
                    'pdf' => '' ,
                    'active' => 1 ,
                    'created_at' => \Carbon\Carbon::now()->format('Y-m-d') , 
                    'updated_at' => \Carbon\Carbon::now()->format('Y-m-d') ,
                    'created_by' => $user->id ,
                    'updated_by' => $user->id
                ]);
                $organizationStructure->updateNumberOfChilds();
                // $organizationStructure->organization;
                // return response()->json([
                //     'ok' => true ,
                //     'record' => $organizationStructure ,
                //     'records' => OrganizationStructure::where('tpid','like', $organizationStructure->tpid .':'. $organizationStructure->id )->with('organization')->get() ,
                //     'message' => 'បានបញ្ចូលមេអង្គភាពរួចហើយ។'
                // ],200);
                $tpid = explode( ':' , $organizationStructure->tpid );
                if( 
                    count( $tpid ) > 1 &&
                    ( 
                        $root = ( 
                            ( $rootId = intval( $tpid[1] ) ) > 0 
                                ? OrganizationStructure::find( $rootId ) 
                                : null 
                        )
                    )  != null
                ){
                    // return with the existing OrganizationStructure
                    $root->organization;
                    $root->updateNumberOfChilds();
                    return response()->json([
                        'ok' => true ,
                        'tpid' => $tpid ,
                        'record' => $root ,
                        'records' => OrganizationStructure::where('tpid','like', $root->tpid .':'. $root->id . ':' . '%' )->with('organization')->get() ,
                        'message' => 'បានបញ្ចូលមេអង្គភាពរួចហើយ។'
                    ],200);
                }else{
                    $organizationStructure->organization;
                    return response()->json([
                        'ok' => true ,
                        'tpid' => $tpid ,
                        'record' => $organizationStructure ,
                        'records' => OrganizationStructure::where('tpid','like', $organizationStructure->tpid .':'. $organizationStructure->id . ':' . '%' )->with('organization')->get() ,
                        'message' => 'បានបញ្ចូលមេអង្គភាពរួចហើយ។'
                    ],200);
                }

            }else{
                $organizationStructure->organization;
                return response()->json([
                    'ok' => false ,
                    'record' => $organizationStructure ,
                    'records' => OrganizationStructure::where('tpid','like', $organizationStructure->tpid .':'. $organizationStructure->id . ':' . '%' )->with('organization')->get() ,
                    'message' => 'មានរួចហើយ'
                ],200);
            }
        }else{
            $organizationStructure = $organization->structure()->where('pid',$parentNode->id)->where('organization_id',$organization->id)->first();
            if( $organizationStructure == null ){
                // This is child
                // $organizationStructure = $organization->structure()->create([
                $organizationStructure = OrganizationStructure::create([
                    'organization_id' => $organization->id ,
                    'pid' => $parentNode->id ,
                    'tpid' => $parentNode->tpid != null && strlen( $parentNode->tpid ) > 0 ? $parentNode->tpid .':'. $parentNode->id. ':' : '0:'.$parentNode->id . ':',
                    'desp' => '' ,
                    'image' => '' ,
                    'pdf' => '' ,
                    'active' => 1 ,
                    'created_at' => \Carbon\Carbon::now()->format('Y-m-d') , 
                    'updated_at' => \Carbon\Carbon::now()->format('Y-m-d') ,
                    'created_by' => $user->id ,
                    'updated_by' => $user->id
                ]);
                // Update child list of parentnode
                $cids = strlen( trim($parentNode->cids) ) > 0 ? explode( ',' , trim($parentNode->cids) ) : [] ;
                $cids[] = $organizationStructure->id ;
                $parentNode->update([
                    'cids' => count( $cids ) > 1 ? implode(',',$cids) : $cids[0]
                ]);
                $parentNode->updateNumberOfChilds();

                $tpid = explode( ':' , $organizationStructure->tpid );
                if( 
                    count( $tpid ) > 1 &&
                    ( 
                        $root = ( 
                            ( $rootId = intval( $tpid[1] ) ) > 0 
                                ? OrganizationStructure::find( $rootId ) 
                                : null 
                        )
                    )  != null
                ){
                    // return with the existing OrganizationStructure
                    $root->organization;
                    return response()->json([
                        'ok' => true ,
                        'tpid' => $tpid ,
                        'record' => $root ,
                        'records' => OrganizationStructure::where('tpid','like', $root->tpid .':'. $root->id . '%' )->with('organization')->get() ,
                        'message' => 'បានបញ្ចូលកូនអង្គភាពរួចហើយ។'
                    ],200);
                }else{
                    $organizationStructure->organization;
                    return response()->json([
                        'ok' => true ,
                        'tpid' => $tpid ,
                        'record' => $organizationStructure ,
                        'records' => OrganizationStructure::where('tpid','like', $organizationStructure->tpid .':'. $organizationStructure->id . '%' )->with('organization')->get() ,
                        'message' => 'បានបញ្ចូលកូនអង្គភាពរួចហើយ។'
                    ],200);
                }
            }else{
                $tpid = explode( ':' , $organizationStructure->tpid );
                if( 
                    count( $tpid ) > 1 &&
                    ( 
                        $root = ( 
                            ( $rootId = intval( $tpid[1] ) ) > 0 
                                ? OrganizationStructure::find( $rootId ) 
                                : null 
                        )
                    )  != null
                ){
                    // return with the existing OrganizationStructure
                    $root->organization;
                    return response()->json([
                        'ok' => true ,
                        'tpid' => $tpid ,
                        'record' => $root ,
                        'records' => OrganizationStructure::where('tpid','like', $root->tpid .':'. $root->id . '%' )->with('organization')->get() ,
                        'message' => 'មានរួចហើយ'
                    ],200);
                }else{
                    $organizationStructure->organization;
                    return response()->json([
                        'ok' => true ,
                        'tpid' => $tpid ,
                        'record' => $organizationStructure ,
                        'records' => OrganizationStructure::where('tpid','like', $organizationStructure->tpid .':'. $organizationStructure->id . '%' )->with('organization')->get() ,
                        'message' => 'មានរួចហើយ'
                    ],200);
                }
            }
        }
    }
    /**
     * Move organization structure
     */
    public function moveStructure(Request $request){
        $user = \Auth::user() ;
        if( $user == null ){
            return response()->json([
                'ok' => false ,
                'message' => 'សូមបញ្ជាក់អត្តសញ្ញាណ។'
            ],403);
        }
        // Get parent
        $organization = isset( $request->parent_organization_id ) && intval( $request->parent_organization_id ) > 0 ? RecordModel::find( $request->parent_organization_id ): null ;
        $parentNode = null ;
        if( $organization->structure->count() > 1 ){
            return response()->json([
                'ok' => false ,
                'parent' => $organization->structure ,
                'message' => 'អង្គភាពមេនេះត្រូវបានប្រើប្រាស់នៅកន្លែងផ្សេងៗគ្នា។'
            ],500);
        }
        else if( $organization->structure->count() == 1 ){
            $parentNode = $organization->structure->first(); 
        }

        // Get child
        $childNode = isset( $request->child_organization_id ) && intval( $request->child_organization_id ) > 0 ? OrganizationStructure::find( $request->child_organization_id ) : null ;
        if( $childNode == null || $parentNode == null ){
            return response()->json([
                'ok' => false ,
                'child' => $childNode ,
                'parent' => $parentNode ,
                'message' => 'សូមបញ្ជាក់អង្គភាពមេ និង អង្គភាពចំណុះ។'
            ],500);
        }

        $childNode->update([
            'pid' => $parentNode->id , 
            // Update tpid because the last id of the tpid is the id of the previous parent node and we need to change it to the new parent node
            'tpid' => $parentNode->tpid .':'. $parentNode->id . ':',
            'updated_by' => $user->id
        ] );
        $parentNode->organization;
        $childNode->organization;
        $parent->updateNumberOfChilds();
        $childNode->updateNumberOfChilds();
        return response()->json([
            'ok' => true ,
            'record' => $parentNode ,
            'records' => OrganizationStructure::where('tpid','like', $parentNode->tpid .':'. $parentNode->id . ':' . '%' )->with('organization')->get() ,
            'message' => 'អង្គភាពនេះបានប្រើរួចហើយ'
        ],200);
    }
    public function deleteStructureNode(Request $request){
        $user = \Auth::user() ;
        if( $user == null ){
            return response()->json([
                'ok' => false ,
                'message' => 'សូមបញ្ជាក់អត្តសញ្ញាណ។'
            ],403);
        }
        $organizationStructure = intval( $request->id ) > 0 ? OrganizationStructure::find( $request->id ) : null ;
        if( $organizationStructure != null ){
            $cids = strlen( trim($organizationStructure->cids) ) > 0 ? explode( ',' , trim($organizationStructure->cids) ) : [] ;
            if( count( $cids ) > 0 ){
                foreach( $cids as $index => $id ){
                    if( ( $organizationStructure == OrganizationStructure::find( $id ) ) != null ){
                        return response()->json([
                            'ok' => false , 
                            'record' => $organizationStructure ,
                            'records' => OrganizationStructure::where('tpid','like', $organizationStructure->tpid .':'. $organizationStructure->id . ':' )->with('organization')->get() ,
                            'message' => 'មិនអាចលុបបានដោយសារមានអង្គភាពពីក្រោមបង្គាប់ ' . count( $cids ). '។' 
                        ],500);
                    }
                }   
            }
            $organizationStructure->update([ 'deleted_by' => $user->id ]);
            $organizationStructure->organization;
            $organizationStructure->updateNumberOfChilds();
            return response()->json([
                'record' => $organizationStructure ,
                'ok' => $organizationStructure->delete() , 
                'message' => 'បាបលុបរួចរាល់។'
            ],200);
        }
        return response()->json([
            'ok' => true , 
            'message' => 'មិនមានអ្វីលុបឡើយ។'
        ],200);
    }
    public function getStructureOrganizations(Request $request){
        /** Format from query string */
        $search = isset( $request->search ) && $request->serach !== "" ? $request->search : false ;
        $perPage = isset( $request->perPage ) && $request->perPage !== "" ? intval( $request->perPage ) : 50 ;
        $page = isset( $request->page ) && $request->page !== "" ? intval( $request->page ) : 1 ;
        $id = isset( $request->id ) && intval( $request->id ) > 0? intval( $request->id ) : false ;

        $root = $id > 0
            ? RecordModel::where('id',$id)->first()
            : null ;
        if( $root != null ){
            $root->parentNode;
            $root->totalChilds = $root->totalChildNodesOfAllLevels();
        }

        $queryString = [
            "where" => [
                // 'default' => [
                //     $pid > 0 ? [
                //         'field' => 'pid' ,
                //         'value' => $pid
                //     ] : [] ,
                // ],

                // 'in' => [] ,

                // 'not' => [
                //     [
                //         'field' => 'id' ,
                //         'value' => 4
                //     ]
                // ] ,
                'like' => [
                    $root != null
                    ? [
                        'field' => 'tpid' ,
                        'value' => ( intval( $root->pid ) > 0 ? $root->pid.":" : '' ) . $root->id . "%"
                    ]
                    : [],
                    // [
                    //     'field' => 'year' ,
                    //     'value' => $date === false ? "" : $date
                    // ]
                ] ,
            ] ,
            "pagination" => [
                'perPage' => $perPage,
                'page' => $page
            ],
            "search" => $search === false ? [] : [
                'value' => $search ,
                'fields' => [
                    'name' , 'desp'
                ]
            ],
            "order" => [
                'field' => 'name' ,
                'by' => 'asc'
            ],
        ];
        $request->merge( $queryString );

        $crud = new CrudController(new RecordModel(), $request, $this->fields , false , [] , [
            'totalChilds' => function($record){
                // return $record->totalChildNodesOfAllLevels();
                return 0 ;
            },
            'pid' => function($record){
                return $record->pid;
            }
        ] );

        $crud->setRelationshipFunctions([
            'parentNode' => [
                'id' , 'name'
            ],
            'childNodes' => [
                'id' , 'name'
            ]
        ]);

        $builder = $crud->getListBuilder();
        $builder->whereHas('structure');
        $responseData = $crud->pagination(true , $builder );
        $responseData['message'] = __("crud.read.success");
        $responseData['ok'] = true ;
        return response()->json($responseData);
    }
}
