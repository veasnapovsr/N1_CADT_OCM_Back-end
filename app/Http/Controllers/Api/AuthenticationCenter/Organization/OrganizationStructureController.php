<?php

namespace App\Http\Controllers\Api\AuthenticationCenter\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\CrudController;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationStructure as RecordModel;
use App\Models\Organization\OrganizationStructurePosition ;


class OrganizationStructureController extends Controller
{
    private $fields = [ 'id', 'name' , 'organization_id','cids' , 'pid' , 'tpid' , 'desc', 'image' , 'pdf' , 'job_desp' , 'total_childs' ] ;
    private $renameFields = [
        'pid' => 'parentId'
    ];

    /**
     * Listing function
     */
    public function index(Request $request){
        /** Format from query string */
        $search = isset( $request->search ) && $request->serach !== "" ? $request->search : false ;
        $perPage = isset( $request->perPage ) && $request->perPage !== "" ? intval( $request->perPage ) : 50 ;
        $page = isset( $request->page ) && $request->page !== "" ? intval( $request->page ) : 1 ;
        $organization = isset( $request->organization_id ) && intval( $request->organization_id ) > 0 ? OrganizationStructure::find( $request->organization_id ) : null ; 

        $queryString = [
            "where" => [
                'default' => [
                    $organization != null && $organization->id > 0 
                    ? [
                        'field' => '$organization_id' ,
                        'value' => $organization->id
                    ] : [] ,
                    [
                        'deleted_at' => NULL
                    ]
                ],
                
                // 'in' => [] ,

                // 'not' => [
                //     [
                //         'field' => 'id' ,
                //         'value' => 4
                //     ]
                // ] ,
                // 'like' => [
                //     $root != null 
                //     ? [
                //         'field' => 'tpid' ,
                //         'value' => ( intval( $root->pid ) > 0 ? $root->pid.":" : '' ) . $root->id . "%"
                //     ]
                //     : [],
                //     // [
                //     //     'field' => 'year' ,
                //     //     'value' => $date === false ? "" : $date
                //     // ]
                // ] ,
            ] ,
            "pagination" => [
                'perPage' => $perPage,
                'page' => $page
            ],
            "search" => $search === false ? [] : [
                'value' => $search ,
                'fields' => [
                    'desc'
                ]
            ],
            "order" => [
                'field' => 'organizatoin_id' ,
                'by' => 'asc'
            ],
        ];
        $request->merge( $queryString );

        $crud = new CrudController(new RecordModel(), $request, $this->fields , false , $this->renameFields );

        $crud->setRelationshipFunctions([
            'organization' => [ 'id' , 'name' , 'desp' , 'tpid' , 'pid' , 'cids' , 'image' , 'prefix' ]
        ]);

        $builder = $crud->getListBuilder();
        /**
         * ចម្រោះយកតែអង្គភាពដែលមានមន្ត្រីហើយ និងមានតួនាទី
         */
        $builder->whereHas('structurePositions',function($queryBuilder){
            $queryBuilder->whereHas('officerJobs');
        });

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
}