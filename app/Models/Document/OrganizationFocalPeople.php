<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationFocalPeople extends Model
{
    use SoftDeletes;
    protected $guarded = ['id'];
    public function organization(){
        return $this->belongsTo( \App\Models\Organization\OrganizationStructure::class , 'organization_structure_id' , 'id' );
    }
    /**
     * គណនីទីនេះគឺត្រូវតែមានភ្ជាប់ជាមួយមន្ត្រី។
     * ដោយសារក្នុងនេះភ្ជាប់ user នៅទីនេះដោយសារ user គឺជាអ្នកចូលប្រើប្រព័ន្ធ
     * តែ officer ជាមន្ត្រីក្នុងស្ថាប័នដែលត្រូវទទួលការងារជាអ្នកទទួលឯកសារ។
     */
    public function officer(){
        return $this->belongsTo( \App\Models\Officer\Officer::class , 'officer_id' , 'id' );
    }
    
}
