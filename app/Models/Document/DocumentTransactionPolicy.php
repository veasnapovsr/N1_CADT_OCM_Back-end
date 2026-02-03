<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTransactionPolicy extends Model
{
    use HasFactory , SoftDeletes;
    protected $guarded = ['id'];
    /**
     * Relationship
     */
    /**
     * Organization
     */
    public function officer(){
        return $this->hasOne( \App\Models\Officer\Officer::class , 'officer_id', 'id' );
    }
    public function organization(){
        return $this->hasOne( \App\Models\Organization\Organization::class , 'organization' , 'id' );
    }
    public function childNodes(){
        return $this->hasMany(self::class,'pid','id');
    }
     public function parentNode(){
        return $this->hasOne(self::class,'id','pid');
    }
    public function totalChildNodesOfAllLevels(){
        return $this->where('tpid',"LIKE", $this->tpid . ":" . $this->id . "%" )->count();
    }
    /**
     * Positions
     */
    public static function generateKeyname(){
        echo "START GENERATE : " . PHP_EOL;
        self::all()->map(function($record){
            echo 'Name : ' . $record->name . ' => Keyname : ';
            $record->update([ 'keyname' => str_replace( [' ','​' ] , '' , $record->name ) ]);
            echo $record->keyname . PHP_EOL;
        });
        echo "END GENERATE : " . PHP_EOL;
    }
}
