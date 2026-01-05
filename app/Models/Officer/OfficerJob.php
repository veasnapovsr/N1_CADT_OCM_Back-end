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
}
