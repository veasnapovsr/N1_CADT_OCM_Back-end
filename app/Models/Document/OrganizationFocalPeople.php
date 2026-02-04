<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganizationFocalPeople extends Model
{
    use SoftDeletes;
    protected $table = 'document_organization_focal_people';
    protected $guarded = ['id'];
    public function organization()
    {
        return $this->belongsTo(
            \App\Models\Organization\OrganizationStructure::class,
             'organization_structure_id' , 'id' );
    }

    /**
     * គណនីទីនេះគឺត្រូវតែមានភ្ជាប់ជាមួយមន្ត្រី។
     * ដោយសារក្នុងនេះភ្ជាប់ user នៅទីនេះដោយសារ user គឺជាអ្នកចូលប្រើប្រព័ន្ធ
     * តែ officer ជាមន្ត្រីក្នុងស្ថាប័នដែលត្រូវទទួលការងារជាអ្នកទទួលឯកសារ។
     */
    public function officer()
    {
        return $this->belongsTo(
            \App\Models\Officer\Officer::class,
            'officer_id', 'id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by', 'create_at');
    }

    public function destroyer()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }

    public function scopeActive($q)
    {
        return $q->whereNull('deleted_at');
    }

}
