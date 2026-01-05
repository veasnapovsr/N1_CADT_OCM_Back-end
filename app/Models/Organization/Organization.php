<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * This class is use to identify the organization of the regulator
 */
class Organization extends Model
{
    use HasFactory , SoftDeletes;

    protected $guarded = ['id'];
    /**
     * Abstract methods
     */
    protected static function getTree($nodeId=false){
        $node = intval( $nodeId ) ? self::find( intval($nodeId) ) : [] ;
        if( $node != null && $node->childNodes != null && !$node->childNodes->isEmpty() ){
            $node->childNodes = $node->childNodes()->select('id','name','desp')->where('active',1)->orderby('record_index','asc')->get()->map(function($c){
                return self::getChilds( $c );
            }) ;
        }
        return $node ;
    }
    private static function getChilds($node ){
        if( !$node->childNodes->isEmpty() ){
            return $node->childNodes()->select('id','name','desp')->where('active',1)->orderby('record_index','asc')->get()->map(function($c){ 
                return self::getChilds( $c );
            });
        }
        return $node ;
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
    public function childs(){
        return $this->hasManyThrough(self::class, \App\Models\Organization\OrganizationStructure::class ,'child_id','parent_id' );
    }
    public function parents(){
        return $this->hasManyThrough(self::class, \App\Models\Organization\OrganizationStructure::class ,'parent_id','child_id' );
    }
    public function totalStaffsOfAllLevels(){
        $staffs = $this->where('tpid',"LIKE", $this->tpid . ":" . $this->id . "%" )->pluck('id')->map(function($organizationId){
            if( ( $organization = Organization::find( $organizationId ) ) != null ){
                return [
                    'totalOfficers' => 
                    // ( $organization->leader != null ? $organization->leader->count() : 0 ) + 
                    ( $organization->staffs != null ? $organization->staffs->count() : 0 )
                ];
            }
            return [
                'totalOfficers' => 0
            ] ;
        });
        return $staffs->sum('totalOfficers');
    }
    /**
     * Relationship
     */
    /**
     * Organization
     */
    public function regulators(){
        return $this->belongsToMany( \App\Models\Regulator\Regulator::class ,'organization_regulators', 'organization_id', 'regulator_id' );
    }
    public function officers(){
        return $this->hasMany( \App\Models\Officer\Officer::class , 'organization_id', 'id' );
    }
    public function meetings(){
        return $this->belongsToMany( Meeting::class , MeetingOrganization::class , 'organization_id' , 'meeting_id' );
    }
    public function attendantChecktimes(){
        return $this->hasMany( App\Models\Attendant\AttendantCheckTime::class,'organization_id','id');
    }
    public function industries(){
        return $this->hasManyThrough( \App\Models\Organization\Industry::class, \App\Models\Organization\OrganizationIndustry::class , 'industry_id' , 'organization_id' );
    }
    public function organizationIndustry(){
        return $this->hasMany( \App\Models\Organization\Industry::class , 'industry_id' , 'id' );
    }
    public static function move($pid,$parentNode=null){
        $node = intval( $pid ) 
            ? \App\Models\Regulator\Tag\Organization::find( intval($pid) ) : 
            [] ;
        if( $node != null ){
            $parent = Organization::create([
                'name' => $node->name ,
                'desp' => $node->desp ,
                'tpid' => $parentNode != null ? $parentNode->tpid : 0 ,
                'pid' => $parentNode != null ? $parentNode->id : 0 ,
                'record_index' => $node->record_index ,
                'active' => $node->active ,
                'created_at' => $node->created_at ,
                'updated_at' => $node->updated_at ,
                'prefix' => $node->code
            ]);
            // Update the relationship
            \DB::table('organization_people')->where('organization_id',$node->id)->update([
                'organization_id'=>$parent->id ,
                'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
                'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
                'code' => $parent->prefix
            ]);
            \DB::table('organization_leader')->where('organization_id',$node->id)->update([
                'organization_id'=>$parent->id ,
                'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
                'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
            ]);
            if( $node->childNodes != null && !$node->childNodes->isEmpty() ){
                $node->childNodes()->get()->map(function($c) use($parent) {
                    return self::getChildsMove( $c , $parent );
                }) ;
            }
        }
    }
    private static function getChildsMove($node , $parentNode ){
        $parent = Organization::create([
            'name' => $node->name ,
            'desp' => $node->desp ,
            'tpid' => $parentNode != null && intval( $parentNode->tpid ) ? $parentNode->tpid : $parentNode->pid ,
            'pid' => $parentNode != null ? $parentNode->id : 0 ,
            'record_index' => $node->record_index ,
            'active' => $node->active ,
            'created_at' => $node->created_at ,
            'updated_at' => $node->updated_at ,
            'prefix' => $node->code
        ]);
        // Update the relationship
        \DB::table('organization_people')->where('organization_id',$node->id)->update([
            'organization_id'=>$parent->id ,
            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
            'code' => $parent->prefix
        ]);
        \DB::table('organization_leader')->where('organization_id',$node->id)->update([
            'organization_id'=>$parent->id ,
            'created_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s') ,
            'updated_at' => \Carbon\Carbon::now()->format('Y-m-d H:i:s')
        ]);
        if( $node->childNodes != null && !$node->childNodes->isEmpty() ){
            $node->childNodes()->get()->map(function($c) use($parent) {
                return self::getChildsMove( $c , $parent );
            }) ;
        }
    }
    /**
     * Positions
     */
    public function positions(){
        return $this->belongsToMany(\App\Models\Position\Position::class , 'organization_positions','organization_id','position_id');
    }
    public function structure(){
        return $this->hasMany( \App\Models\Organization\OrganizationStructure::class , 'organization_id' , 'id' );
    }
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
