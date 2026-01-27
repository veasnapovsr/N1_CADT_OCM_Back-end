<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationFocalPeople extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];
    public function organization(){
        return $this->belongsTo( \App\Models\Organization\Organization::class , 'organization_id' , 'id' );
    }
    public function user(){
        return $this->belongsTo( \App\Models\User::class , 'user_id' , 'id' );
    }
}
