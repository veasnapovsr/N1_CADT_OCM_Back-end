<?php

namespace App\Models\Officer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class OfficerJob extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function organizationStructurePosition(){
        return $this->belongsTo( \App\Models\Organization\OrganizationStructurePosition::class , 'organization_structure_position_id' , 'id' );
    }
    public function countesy(){
        return $this->belongsTo( \App\Models\People\Countesy::class , 'countesy_id' , 'id' );
    }
    public function officer(){
        return $this->belongsTo( \App\Models\Officer\Officer::class , 'officer_id' , 'id' );
    }
    public function totalWorkingDays(){
        return strlen( $this->start ) > 0
            ? Carbon::parse( $this->start )->diffInDays( strlen( $this->end ) > 0 ? Carbon::parse( $this->end ) : Carbon::now() )
            : 0 ;
    }
    /**
     * មុខងារចាប់យករចនាសម្ព័ន្ធនៃតួនាទី
     */
    public function getParentIdsInStructure(){
        /**
         * លេខសម្គាល់ថ្នាក់ដឹកនាំក្នុងអង្គភាព
         */
        $parentIds = [];
        if( $this->organizationStructurePosition != null ){
            $parentIds = array_filter( explode(':',$this->organizationStructurePosition->tpid) , function($id){ return intval( $id ) > 0 ;} );
        }
        /**
         * លេខសម្គាល់ថ្នាក់ដឹកនាំក្នុងស្ថាប័នទាំងមូល
         */
        $organizatoinStructgureIds = [] ;
        if( $this->organizationStructurePosition->organizatoinStructure != null ){
            $organizatoinStructgureIds = array_filter( function($id){ return intval( $id ) > 0 ;} , explode(':',$this->organizationStructurePosition->organizatoinStructure->tpid) );
            /**
             * អាចយកលេខសម្គាល់នៃអង្គភាពនីមួយដែលជាឋានានុក្រុមខាងលើនៃ អង្គភាពបច្ចុប្បដែលតួនាទីរបស់មន្ត្រីនៅ
             */
        }

        return $parentIds ;
    }
}
