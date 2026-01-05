<?php

namespace App\Models\People;

use Illuminate\Database\Eloquent\Model;
use App\Models\Meeting\Meeting;
use App\Models\Meeting\MeetingMember;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class People extends Model
{

  use  SoftDeletes;
  protected $table = 'people';

   /*
  |--------------------------------------------------------------------------
  | GLOBAL VARIABLES
  |--------------------------------------------------------------------------
  */

  //protected $table = 'document_users';
  protected $primaryKey = 'id';
  public $timestamps = true;
  protected $guarded = ['id'];
  // protected $fillable = ['firstname', 'lastname', 'gender', 'dob', 'mobile_phone', 'office_phone', 'email', 'nid', 'father', 'mother', 'image', 'marry_status'];
  protected $hidden = ['deleted_at', 'created_by', 'updated_by', 'deleted_by'];
  // protected $dates = [];

  /*
  |--------------------------------------------------------------------------
  | FUNCTIONS
  |--------------------------------------------------------------------------
  */

  /*
  |--------------------------------------------------------------------------
  | RELATIONS
  |--------------------------------------------------------------------------
  */
  public function languages(){
    return $this->hasMany( \App\Models\People\PeopleLanguage::class , 'people_id' , 'id' );
}
  public function cards(){
    return $this->hasMany( \App\Models\People\Card::class , 'people_id' , 'id' );
  }
  public function officers(){
    return $this->hasMany( \App\Models\Officer\Officer::class , 'people_id' , 'id' );
  }
  public function users(){
    return $this->hasMany( \App\Models\User::class , 'people_id' , 'id' );
  }
  public function countesy(){
    return $this->belongsTo( \App\Models\People\Countesy::class , 'countesy_id' , 'id' );
  }
  public function position(){
    return $this->belongsTo( \App\Models\Position\Position::class , 'position_id' , 'id' );
  }
  public function organization(){
    return $this->belongsTo( \App\Models\Organization\Organization::class , 'organization_id' , 'id' );
  }
  public function certificates(){
    return $this->hasMany( \App\Models\People\Certificate::class , 'people_id' , 'id' );
  }
  public function certificatesHighSchool(){
    return $this->certificates()->where('certificate_group_id','<=' , 3 )->get()->map(function($record){
      $record->group;
      return $record;
    });
  }
  public function certificatesPostGraduated(){
    return $this->certificates()->where('certificate_group_id', '>' , 3 )->where('certificate_group_id','<=',8)->get()->map(function($record){
      $record->group;
      return $record;
    });
  }
  public function certificatesOthers(){
    return $this->certificates()->where('certificate_group_id', '>' , 8 )->get()->map(function($record){
      $record->group;
      return $record;
    });
  }
  public function nationalCards(){
    return $this->hasMany( \App\Models\People\People::class , 'people_id' , 'id' );
  }
  public function passports(){
    return $this->hasMany( \App\Models\People\Passport::class , 'people_id' , 'id' );
  }
  public function birthCertificates(){
    return $this->hasMany( \App\Models\People\BirthCertificates::class , 'people_id' , 'id' );
  }
  public function weddingCertificates(){
    return $this->hasMany( \App\Models\People\WeddingCertificate::class , 'people_id' , 'id' );
  }
  public function fatherKids(){
    return $this->hasMany( \App\Models\People\People::class , 'father_id' , 'id' );
  }
  public function motherKids(){
    return $this->hasMany( \App\Models\People\People::class , 'mother_id' , 'id' );
  }
  public function kidFather(){
    return $this->belongsTo( \App\Models\People\People::class , 'father_id' , 'id' );
  }
  public function kidMother(){
    return $this->blongsTo( \App\Models\People\People::class , 'mother_id' , 'id' );
  }
  public function addressProvince(){
    return $this->belongsTo( \App\Models\Location\Province::class , 'address_province_id' , 'id' );
  }
  public function addressDistrict(){
    return $this->belongsTo( \App\Models\Location\District::class , 'address_district_id' , 'id' );
  }
  public function addressCommune(){
    return $this->belongsTo( \App\Models\Location\Commune::class , 'address_commune_id' , 'id' );
  }
  public function addressVillage(){
    return $this->belongsTo( \App\Models\Location\Village::class , 'address_village_id' , 'id' );
  }
  public function currentAddressProvince(){
    return $this->belongsTo( \App\Models\Location\Province::class , 'current_address_province_id' , 'id' );
  }
  public function currentAddressDistrict(){
    return $this->belongsTo( \App\Models\Location\District::class , 'current_address_district_id' , 'id' );
  }
  public function currentAddressCommune(){
    return $this->belongsTo( \App\Models\Location\Commune::class , 'current_address_commune_id' , 'id' );
  }
  public function currentAddressVillage(){
    return $this->belongsTo( \App\Models\Location\Village::class , 'current_address_village_id' , 'id' );
  }
  public function pobProvince(){
    return $this->belongsTo( \App\Models\Location\Province::class , 'pob_province_id' , 'id' );
  }
  public function pobDistrict(){
    return $this->belongsTo( \App\Models\Location\District::class , 'pob_district_id' , 'id' );
  }
  public function pobCommune(){
    return $this->belongsTo( \App\Models\Location\Commune::class , 'pob_commune_id' , 'id' );
  }
  public function pobVillage(){
    return $this->belongsTo( \App\Models\Location\Village::class , 'pob_village_id' , 'id' );
  }
  public function emergencyProvince(){
    return $this->belongsTo( \App\Models\Location\Province::class , 'emergency_address_province_id' , 'id' );
  }
  public function emergencyDistrict(){
    return $this->belongsTo( \App\Models\Location\District::class , 'emergency_address_district_id' , 'id' );
  }
  public function emergencyCommune(){
    return $this->belongsTo( \App\Models\Location\Commune::class , 'emergency_address_commune_id' , 'id' );
  }
  public function emergencyVillage(){
    return $this->belongsTo( \App\Models\Location\Village::class , 'emergency_address_village_id' , 'id' );
  }
  /*
  |--------------------------------------------------------------------------
  | SCOPES
  |--------------------------------------------------------------------------
  */

  /*
  |--------------------------------------------------------------------------
  | ACCESORS
  |--------------------------------------------------------------------------
  */

  /*
  |--------------------------------------------------------------------------
  | MUTATORS
  |--------------------------------------------------------------------------
  */
  /*
  |----------
  | FUNCTIONS
  |---------- 
  */
}
