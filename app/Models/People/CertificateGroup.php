<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateGroup extends Model
{
    use HasFactory, SoftDeletes;
    public function people(){
        return $this->belongsTo( \App\Models\People\People::class , 'people_id' , 'id' );
    }
    public function certificates(){
        return $this->hasMany( \App\Models\People\Certificate::class , 'certificate_group_id' , 'id' );
      }
}
