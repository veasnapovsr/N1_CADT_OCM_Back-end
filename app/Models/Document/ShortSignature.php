<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;

class ShortSignature extends Model
{
    protected $guarded = ['id'] ;
    /**
     * ការចុះហត្ថលេខាសង្ខេបលើឯកសារ
     */
    protected $table = 'document_short_signatures';
    public function document(){
        return $this->belongsTo( \App\Models\Document\Document::class , 'document_id' , 'user_id' );
    }
}
