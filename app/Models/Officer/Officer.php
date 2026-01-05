<?php

namespace App\Models\Officer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Officer extends Model
{
    use HasFactory , SoftDeletes;

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();
        static::deleting(function($obj) {
        if( $obj->image !== "" && $obj->image !== null ) \Storage::disk('public')->delete($obj->image);
        });
    }
    public function rank(){
        return $this->belongsTo( \App\Models\Officer\Rank::class , 'rank_id' , 'id' );
    }
    public function position(){
        return $this->belongsTo( \App\Models\Position\Position::class , 'position_id' , 'id' );
    }
    public function organization(){
        return $this->belongsTo( \App\Models\Organization\Organization::class , 'organization_id' , 'id' );
    }
    public function countesy(){
        return $this->belongsTo( \App\Models\People\Countesy::class , 'countesy_id' , 'id' );
    }
    public function people(){
        return $this->belongsTo( \App\Models\People\People::class , 'people_id' , 'id' );
    }
    public function card(){
        return $this->hasOne( \App\Models\People\Card::class , 'officer_id', 'id' );
    }
    public function user(){
        return $this->belongsTo( \App\Models\User::class , 'user_id' ,'id' );
    }
    public function jobs(){
        return $this->hasMany( \App\Models\Officer\OfficerJob::class , 'officer_id' , 'id' );
    }
    public function getCurrentJob(){
        return $this->jobs()->whereNull('end')->orderby('start','desc')->first();
    }
    public function jobBackgrounds(){
        return $this->hasMany( \App\Models\Officer\OfficerJobBackground::class , 'officer_id' , 'id' );
    }
    public function rankingByCertificates(){
        return $this->hasMany( \App\Models\Officer\OfficerRankByCertificate::class , 'officer_id' , 'id' );
    }
    public function rankingByWorkings(){
        return $this->hasMany( \App\Models\Officer\OfficerRankByWorking::class , 'officer_id' , 'id' );
    }
    public function pendingWorks(){
        return $this->hasMany( \App\Models\Officer\OfficerWorkPending::class , 'officer_id' , 'id' );
    }
    public function paneltyHistories(){
        return $this->hasMany( \App\Models\Officer\OfficerPenaltyHistory::class , 'officer_id' , 'id' );
    }
    public function medalHistories(){
        return $this->hasMany( \App\Models\Officer\OfficerMedalHistory::class , 'officer_id' , 'id' );
    }

    /**
     * Meetings
     */
    public function meetings(){
    return $this->belongsToMany( Meeting::class , MeetingMember::class , 'people_id' , 'meeting_id' );
    }

    public function meetingsJoinedAsLeaderOfLeadMeeting(){
    return $this->belongsToMany( Meeting::class , MeetingMember::class , 'people_id' , 'meeting_id' )
        ->wherePivot('role','leader')->where('group','lead_meeting');
    }

    public function meetingsJoinedAsDeputyLeaderOfLeadMeeting(){
    return $this->belongsToMany( Meeting::class , MeetingMember::class , 'people_id' , 'meeting_id' )
        ->wherePivot('role','deputy_leader')->where('group','lead_meeting');
    }

    public function meetingsJoinedAsMemberOfLeadMeeting(){
    return $this->belongsToMany( Meeting::class , MeetingMember::class , 'people_id' , 'meeting_id' )
        ->wherePivot('role','member')->where('group','lead_meeting');
    }

    public function meetingsJoinedAsLeaderOfDefender(){
    return $this->belongsToMany( Meeting::class , MeetingMember::class , 'people_id' , 'meeting_id' )
        ->wherePivot('role','leader')->where('group','defender');
    }

    public function meetingsJoinedAsDeputyLeaderOfDefender(){
    return $this->belongsToMany( Meeting::class , MeetingMember::class , 'people_id' , 'meeting_id' )
        ->wherePivot('role','deputy_leader')->where('group','defender');
    }

    public function meetingsJoinedAsMemberOfDefender(){
    return $this->belongsToMany( Meeting::class , MeetingMember::class , 'people_id' , 'meeting_id' )
        ->wherePivot('role','member')->where('group','defender');
    }

    public function meetingsJoinedAsMember(){
    return $this->belongsToMany( Meeting::class , MeetingMember::class , 'people_id' , 'meeting_id' )
        ->wherePivot('role','member')->where('group','audient');
    }
    
    public function ministries(){
    return $this->belongsToMany('\App\Models\Ministry','ministry_people','people_id','ministry_id');
    }

    /**
     * Get total meetings which lead by each leader and each meeting types
     */
    public static function totalMeetingsByType($leaderId =false , $creatorIds=[]){
    $builder = static::whereIn('id', 
        // Fetching the people_id from meeting_members table to cut down the number of the records
        \App\Models\Meeting\MeetingMember::selectRaw('people_id')->where('role','leader')->where('group','lead_meeting')->groupby('people_id')->pluck('people_id')
    );
    if( $leaderId != false && intval( $leaderId ) > 0 ){
        $builder->where('id',$leaderId);
    }
    return $builder->get()->map(function($people) use( $creatorIds ) {
        $builder = $people->meetingsJoinedAsLeaderOfLeadMeeting();
        if( !empty( $creatorIds ) ) $builder->whereIn('created_by',$creatorIds) ;
        // Copy the builder 
        // $copyBuilder = new \Illuminate\Database\Eloquent\Builder(clone $builder->getQuery());
        // $copyBuilder->setModel($builder->getModel());
        return [
        'total' => $builder->count() ,
        'totalSpentMinutes' => $builder->get()->map(function($meeting){ return $meeting->totalSpentMinutes();})->sum() ,
        'people' => [
            'id' => $people->id ,
            'lastname' => $people->lastname ,
            'firstname' => $people->firstname ,
            'countesies' => $people->countesies ,
            'organizations' => $people->organizations ,
            'positions' => $people->positions
        ] ,
        'meetings' => $builder->get()->map(function($record){
            $record->updateStatus();
            $record->createdBy ;
            $record->updatedBy ;
            $record->type ;

            // $record->seichdey_preeng = collect( $record->seichdey_preeng )->map(function($preeng){
            //     return strlen($preeng) && Storage::disk('meeting')->exists( $preeng ) ? Storage::disk("meeting")->url( $preeng ) : false ;
            // });
            // $record->reports = collect( $record->reports )->map(function($report){
            //     return strlen($report) && Storage::disk('meeting')->exists( $report ) ? Storage::disk("meeting")->url( $report ) : false ;
            // });
            // $record->other_documents = collect( $record->other_documents )->map(function($other){
            //     return strlen($other) && Storage::disk('meeting')->exists( $other ) ? Storage::disk("meeting")->url( $other ) : false ;
            // });
            // $record->regulators = $record->regulators()->get()->map(function($regulator){
            //     $regulator->pdf = strlen( $regulator->pdf ) > 0 
            //         ? (
            //             \Storage::disk('regulator')->exists( $regulator->pdf )
            //             ? \Storage::disk('regulator')->url( $regulator->pdf )
            //             : (
            //                 \Storage::disk('document')->exists( $regulator->pdf )
            //                 ? \Storage::disk('document')->url( $regulator->pdf )
            //                 : false
            //             )
            //         )
            //         : false ;
            //     return [
            //         "id" => $regulator->id ,
            //         "fid" => $regulator->fid ,
            //         "title" => $regulator->title ,
            //         "objective" => $regulator->objective ,
            //         "pdf" => $regulator->pdf ,
            //         "year" => $regulator->year
            //     ];
            // });
            $record->organizations = $record->organizations()->get()->map(function($organization) use( $record ){
                return [
                    "id" => $organization->id ,
                    "name" => $organization->name
                ];
            });
            $record->members = $record->members()->get()->map(function($member) use( $record ){
                $meetingMember = $record->listMembers()->where('people_id', $member->id)->first();
                return [
                    "id" => $member->id ,
                    "firstname" => $member->firstname ,
                    "lastname" => $member->lastname ,
                    "role" => $meetingMember->role ,
                    "group" => $meetingMember->group ,
                    "remark" => $meetingMember->remark
                ];
            });
            $record->rooms = $record->rooms()->get()->map(function($place) use( $record ){
                return [
                    "id" => $place->id ,
                    "organization" => $place->organization == null ? null : [
                        'id' => $place->organization->id ,
                        'name' => $place->organization->name
                    ] ,
                    "name" => $place->name ,
                    "desp" => $place->desp
                ];
            });
            // List members
            $record->listMembers = $record->listMembers->map(function($meetingMember){
                return [
                    'id' => $meetingMember->id ,
                    'role' => $meetingMember->role ,
                    'group' => $meetingMember->group ,
                    'remark' => $meetingMember->remark ,
                    'member' => $meetingMember->member == null ? null : [ 
                        'id' => $meetingMember->member->id , 
                        'firstname' => $meetingMember->member->firstname , 
                        'lastname' => $meetingMember->member->lastname
                    ] ,
                    'attendant' => $meetingMember->attendant == null ? null :
                        [ 
                            'id' => $meetingMember->attendant->id , 
                            'checktime' => $meetingMember->attendant->checktime , 
                            'remark' => $meetingMember->attendant->remark , 
                            'member' => $meetingMember->attendant->member == null ? null : 
                            [ 
                                'id' => $meetingMember->attendant->member->id , 
                                'firstname' => $meetingMember->attendant->member->firstname , 
                                'lastname' => $meetingMember->attendant->member->lastname
                            ] 
                        ]
                ];
            });
            return $record ;
        }) ,
        'totalMeetingsByTypes' => $builder->selectRaw('type_id, count(type_id) as total')->groupby('type_id','people_id','meeting_id')->get()->map(function($meeting) {
            return [
                'type' => [
                    'id' => $meeting->type->id ,
                    'name' => $meeting->type->name ,
                ],
                'total' => $meeting->total
            ];
        })
        ];
    });
    }
    /**
     * Get total meetings which lead by each leader and each meeting types within this week
     */
    public static function totalMeetingsByTypeThisWeek($creatorIds=[]){
    $builder = static::whereIn('id', 
        // Fetching the people_id from meeting_members table to cut down the number of the records
        \App\Models\Meeting\MeetingMember::selectRaw('people_id')->where('role','leader')->where('group','lead_meeting')->groupby('people_id')->pluck('people_id')
    );
    return $builder->get()->map(function($people) use( $creatorIds ) {
        $today = Carbon::now();
        $builder = $people->meetingsJoinedAsLeaderOfLeadMeeting()
        ->whereBetween('official_date', [ $today->startOfWeek()->format('Y-m-d') , $today->endOfWeek()->format('Y-m-d') ] );
        if( !empty( $creatorIds ) ) $builder->whereIn('created_by',$creatorIds) ;
        return [
        'totalSpentMinutes' => $builder->get()->map(function($meeting){ return $meeting->totalSpentMinutes();})->sum() ,
        'people' => [
            'id' => $people->id ,
            'lastname' => $people->lastname ,
            'firstname' => $people->firstname ,
            'countesies' => $people->countesies ,
            'organizations' => $people->organizations ,
            'positions' => $people->positions
        ] ,
        'total' => $builder->count() ,
        'meetings' => $builder->get()->map(function($record){
            $record->updateStatus();
            $record->createdBy ;
            $record->updatedBy ;
            $record->type ;

            $record->seichdey_preeng = collect( $record->seichdey_preeng )->map(function($preeng){
                return strlen($preeng) && Storage::disk('meeting')->exists( $preeng ) ? Storage::disk("meeting")->url( $preeng ) : false ;
            });
            $record->reports = collect( $record->reports )->map(function($report){
                return strlen($report) && Storage::disk('meeting')->exists( $report ) ? Storage::disk("meeting")->url( $report ) : false ;
            });
            $record->other_documents = collect( $record->other_documents )->map(function($other){
                return strlen($other) && Storage::disk('meeting')->exists( $other ) ? Storage::disk("meeting")->url( $other ) : false ;
            });
            $record->regulators = $record->regulators()->get()->map(function($regulator){
                $regulator->pdf = strlen( $regulator->pdf ) > 0 
                    ? (
                        \Storage::disk('regulator')->exists( $regulator->pdf )
                        ? \Storage::disk('regulator')->url( $regulator->pdf )
                        : (
                            \Storage::disk('document')->exists( $regulator->pdf )
                            ? \Storage::disk('document')->url( $regulator->pdf )
                            : false
                        )
                    )
                    : false ;
                return [
                    "id" => $regulator->id ,
                    "fid" => $regulator->fid ,
                    "title" => $regulator->title ,
                    "objective" => $regulator->objective ,
                    "pdf" => $regulator->pdf ,
                    "year" => $regulator->year
                ];
            });
            $record->organizations = $record->organizations()->get()->map(function($organization) use( $record ){
                return [
                    "id" => $organization->id ,
                    "name" => $organization->name
                ];
            });
            $record->members = $record->members()->get()->map(function($member) use( $record ){
                $meetingMember = $record->listMembers()->where('people_id', $member->id)->first();
                return [
                    "id" => $member->id ,
                    "firstname" => $member->firstname ,
                    "lastname" => $member->lastname ,
                    "role" => $meetingMember->role ,
                    "group" => $meetingMember->group ,
                    "remark" => $meetingMember->remark
                ];
            });
            $record->rooms = $record->rooms()->get()->map(function($place) use( $record ){
                return [
                    "id" => $place->id ,
                    "organization" => $place->organization == null ? null : [
                        'id' => $place->organization->id ,
                        'name' => $place->organization->name
                    ] ,
                    "name" => $place->name ,
                    "desp" => $place->desp
                ];
            });
            // List members
            $record->listMembers = $record->listMembers->map(function($meetingMember){
                return [
                    'id' => $meetingMember->id ,
                    'role' => $meetingMember->role ,
                    'group' => $meetingMember->group ,
                    'remark' => $meetingMember->remark ,
                    'member' => $meetingMember->member == null ? null : [ 
                        'id' => $meetingMember->member->id , 
                        'firstname' => $meetingMember->member->firstname , 
                        'lastname' => $meetingMember->member->lastname
                    ] ,
                    'attendant' => $meetingMember->attendant == null ? null :
                        [ 
                            'id' => $meetingMember->attendant->id , 
                            'checktime' => $meetingMember->attendant->checktime , 
                            'remark' => $meetingMember->attendant->remark , 
                            'member' => $meetingMember->attendant->member == null ? null : 
                            [ 
                                'id' => $meetingMember->attendant->member->id , 
                                'firstname' => $meetingMember->attendant->member->firstname , 
                                'lastname' => $meetingMember->attendant->member->lastname
                            ] 
                        ]
                ];
            });
            return $record ;
        }) ,
        'totalMeetingsByTypes' => $builder->selectRaw('type_id, count(type_id) as total')->groupby('type_id','people_id','meeting_id')->get()->map(function($meeting) {
            return [
                'type' => [
                    'id' => $meeting->type->id ,
                    'name' => $meeting->type->name ,
                ],
                'total' => $meeting->total
            ];
        })
        ];
    });
    }
    /**
     * Get total meetings which lead by each leader and each meeting types within this month
     */
    public static function totalMeetingsByTypeThisMonth($creatorIds=[]){
    $builder = static::whereIn('id', 
        // Fetching the people_id from meeting_members table to cut down the number of the records
        \App\Models\Meeting\MeetingMember::selectRaw('people_id')->where('role','leader')->where('group','lead_meeting')->groupby('people_id')->pluck('people_id')
    );
    return $builder->get()->map(function($people) use( $creatorIds ) {
        $today = Carbon::now();
        $builder = $people->meetingsJoinedAsLeaderOfLeadMeeting()
        ->whereBetween('official_date', [ $today->startOfMonth()->format('Y-m-d') , $today->endOfMonth()->format('Y-m-d') ] );
        if( !empty( $creatorIds ) ) $builder->whereIn('created_by',$creatorIds) ;
        return [
        'totalSpentMinutes' => $builder->get()->map(function($meeting){ return $meeting->totalSpentMinutes();})->sum() ,
        'people' => [
            'id' => $people->id ,
            'lastname' => $people->lastname ,
            'firstname' => $people->firstname ,
            'countesies' => $people->countesies ,
            'organizations' => $people->organizations ,
            'positions' => $people->positions
        ] ,
        'total' => $builder->count() ,
        'meetings' => $builder->get()->map(function($record){
            $record->updateStatus();
            $record->createdBy ;
            $record->updatedBy ;
            $record->type ;

            $record->seichdey_preeng = collect( $record->seichdey_preeng )->map(function($preeng){
                return strlen($preeng) && Storage::disk('meeting')->exists( $preeng ) ? Storage::disk("meeting")->url( $preeng ) : false ;
            });
            $record->reports = collect( $record->reports )->map(function($report){
                return strlen($report) && Storage::disk('meeting')->exists( $report ) ? Storage::disk("meeting")->url( $report ) : false ;
            });
            $record->other_documents = collect( $record->other_documents )->map(function($other){
                return strlen($other) && Storage::disk('meeting')->exists( $other ) ? Storage::disk("meeting")->url( $other ) : false ;
            });
            $record->regulators = $record->regulators()->get()->map(function($regulator){
                $regulator->pdf = strlen( $regulator->pdf ) > 0 
                    ? (
                        \Storage::disk('regulator')->exists( $regulator->pdf )
                        ? \Storage::disk('regulator')->url( $regulator->pdf )
                        : (
                            \Storage::disk('document')->exists( $regulator->pdf )
                            ? \Storage::disk('document')->url( $regulator->pdf )
                            : false
                        )
                    )
                    : false ;
                return [
                    "id" => $regulator->id ,
                    "fid" => $regulator->fid ,
                    "title" => $regulator->title ,
                    "objective" => $regulator->objective ,
                    "pdf" => $regulator->pdf ,
                    "year" => $regulator->year
                ];
            });
            $record->organizations = $record->organizations()->get()->map(function($organization) use( $record ){
                return [
                    "id" => $organization->id ,
                    "name" => $organization->name
                ];
            });
            $record->members = $record->members()->get()->map(function($member) use( $record ){
                $meetingMember = $record->listMembers()->where('people_id', $member->id)->first();
                return [
                    "id" => $member->id ,
                    "firstname" => $member->firstname ,
                    "lastname" => $member->lastname ,
                    "role" => $meetingMember->role ,
                    "group" => $meetingMember->group ,
                    "remark" => $meetingMember->remark
                ];
            });
            $record->rooms = $record->rooms()->get()->map(function($place) use( $record ){
                return [
                    "id" => $place->id ,
                    "organization" => $place->organization == null ? null : [
                        'id' => $place->organization->id ,
                        'name' => $place->organization->name
                    ] ,
                    "name" => $place->name ,
                    "desp" => $place->desp
                ];
            });
            // List members
            $record->listMembers = $record->listMembers->map(function($meetingMember){
                return [
                    'id' => $meetingMember->id ,
                    'role' => $meetingMember->role ,
                    'group' => $meetingMember->group ,
                    'remark' => $meetingMember->remark ,
                    'member' => $meetingMember->member == null ? null : [ 
                        'id' => $meetingMember->member->id , 
                        'firstname' => $meetingMember->member->firstname , 
                        'lastname' => $meetingMember->member->lastname
                    ] ,
                    'attendant' => $meetingMember->attendant == null ? null :
                        [ 
                            'id' => $meetingMember->attendant->id , 
                            'checktime' => $meetingMember->attendant->checktime , 
                            'remark' => $meetingMember->attendant->remark , 
                            'member' => $meetingMember->attendant->member == null ? null : 
                            [ 
                                'id' => $meetingMember->attendant->member->id , 
                                'firstname' => $meetingMember->attendant->member->firstname , 
                                'lastname' => $meetingMember->attendant->member->lastname
                            ] 
                        ]
                ];
            });
            return $record ;
        }) ,
        'totalMeetingsByTypes' => $builder->selectRaw('type_id, count(type_id) as total')->groupby('type_id','people_id','meeting_id')->get()->map(function($meeting) {
            return [
                'type' => [
                    'id' => $meeting->type->id ,
                    'name' => $meeting->type->name ,
                ],
                'total' => $meeting->total
            ];
        })
        ];
    });
    }
    /**
     * Get total meetings which lead by each leader and each meeting types within this first term
     */
    public static function totalMeetingsByTypeFirstTerm($creatorIds=[]){
    $builder = static::whereIn('id', 
        // Fetching the people_id from meeting_members table to cut down the number of the records
        \App\Models\Meeting\MeetingMember::selectRaw('people_id')->where('role','leader')->where('group','lead_meeting')->groupby('people_id')->pluck('people_id')
    );
    return $builder->get()->map(function($people) use( $creatorIds ) {
        $start = Carbon::now()->startOfYear();
        $end = $start->copy()->addMonths(2);
        $builder = $people->meetingsJoinedAsLeaderOfLeadMeeting()
        ->whereBetween('official_date', [ $start->startOfMonth()->format('Y-m-d') , $end->endOfMonth()->format('Y-m-d') ] );
        if( !empty( $creatorIds ) ) $builder->whereIn('created_by',$creatorIds) ;
        return [
        'totalSpentMinutes' => $builder->get()->map(function($meeting){ return $meeting->totalSpentMinutes();})->sum() ,
        'people' => [
            'id' => $people->id ,
            'lastname' => $people->lastname ,
            'firstname' => $people->firstname ,
            'countesies' => $people->countesies ,
            'organizations' => $people->organizations ,
            'positions' => $people->positions
        ] ,
        'total' => $builder->count() ,
        'meetings' => $builder->get()->map(function($record){
            $record->updateStatus();
            $record->createdBy ;
            $record->updatedBy ;
            $record->type ;

            $record->seichdey_preeng = collect( $record->seichdey_preeng )->map(function($preeng){
                return strlen($preeng) && Storage::disk('meeting')->exists( $preeng ) ? Storage::disk("meeting")->url( $preeng ) : false ;
            });
            $record->reports = collect( $record->reports )->map(function($report){
                return strlen($report) && Storage::disk('meeting')->exists( $report ) ? Storage::disk("meeting")->url( $report ) : false ;
            });
            $record->other_documents = collect( $record->other_documents )->map(function($other){
                return strlen($other) && Storage::disk('meeting')->exists( $other ) ? Storage::disk("meeting")->url( $other ) : false ;
            });
            $record->regulators = $record->regulators()->get()->map(function($regulator){
                $regulator->pdf = strlen( $regulator->pdf ) > 0 
                    ? (
                        \Storage::disk('regulator')->exists( $regulator->pdf )
                        ? \Storage::disk('regulator')->url( $regulator->pdf )
                        : (
                            \Storage::disk('document')->exists( $regulator->pdf )
                            ? \Storage::disk('document')->url( $regulator->pdf )
                            : false
                        )
                    )
                    : false ;
                return [
                    "id" => $regulator->id ,
                    "fid" => $regulator->fid ,
                    "title" => $regulator->title ,
                    "objective" => $regulator->objective ,
                    "pdf" => $regulator->pdf ,
                    "year" => $regulator->year
                ];
            });
            $record->organizations = $record->organizations()->get()->map(function($organization) use( $record ){
                return [
                    "id" => $organization->id ,
                    "name" => $organization->name
                ];
            });
            $record->members = $record->members()->get()->map(function($member) use( $record ){
                $meetingMember = $record->listMembers()->where('people_id', $member->id)->first();
                return [
                    "id" => $member->id ,
                    "firstname" => $member->firstname ,
                    "lastname" => $member->lastname ,
                    "role" => $meetingMember->role ,
                    "group" => $meetingMember->group ,
                    "remark" => $meetingMember->remark
                ];
            });
            $record->rooms = $record->rooms()->get()->map(function($place) use( $record ){
                return [
                    "id" => $place->id ,
                    "organization" => $place->organization == null ? null : [
                        'id' => $place->organization->id ,
                        'name' => $place->organization->name
                    ] ,
                    "name" => $place->name ,
                    "desp" => $place->desp
                ];
            });
            // List members
            $record->listMembers = $record->listMembers->map(function($meetingMember){
                return [
                    'id' => $meetingMember->id ,
                    'role' => $meetingMember->role ,
                    'group' => $meetingMember->group ,
                    'remark' => $meetingMember->remark ,
                    'member' => $meetingMember->member == null ? null : [ 
                        'id' => $meetingMember->member->id , 
                        'firstname' => $meetingMember->member->firstname , 
                        'lastname' => $meetingMember->member->lastname
                    ] ,
                    'attendant' => $meetingMember->attendant == null ? null :
                        [ 
                            'id' => $meetingMember->attendant->id , 
                            'checktime' => $meetingMember->attendant->checktime , 
                            'remark' => $meetingMember->attendant->remark , 
                            'member' => $meetingMember->attendant->member == null ? null : 
                            [ 
                                'id' => $meetingMember->attendant->member->id , 
                                'firstname' => $meetingMember->attendant->member->firstname , 
                                'lastname' => $meetingMember->attendant->member->lastname
                            ] 
                        ]
                ];
            });
            return $record ;
        }) ,
        'totalMeetingsByTypes' => $builder->selectRaw('type_id, count(type_id) as total')->groupby('type_id','people_id','meeting_id')->get()->map(function($meeting) {
            return [
                'type' => [
                    'id' => $meeting->type->id ,
                    'name' => $meeting->type->name ,
                ],
                'total' => $meeting->total
            ];
        })
        ];
    });
    }
    /**
     * Get total meetings which lead by each leader and each meeting types within this first semester
     */
    public static function totalMeetingsByTypeFirstSemester($creatorIds=[]){
    $builder = static::whereIn('id', 
        // Fetching the people_id from meeting_members table to cut down the number of the records
        \App\Models\Meeting\MeetingMember::selectRaw('people_id')->where('role','leader')->where('group','lead_meeting')->groupby('people_id')->pluck('people_id')
    );
    return $builder->get()->map(function($people) use( $creatorIds ) {
        $start = Carbon::now()->startOfYear();
        $end = $start->copy()->addMonths(5);
        $builder = $people->meetingsJoinedAsLeaderOfLeadMeeting()
        ->whereBetween('official_date', [ $start->startOfMonth()->format('Y-m-d') , $end->endOfMonth()->format('Y-m-d') ] );
        if( !empty( $creatorIds ) ) $builder->whereIn('created_by',$creatorIds) ;
        return [
        'totalSpentMinutes' => $builder->get()->map(function($meeting){ return $meeting->totalSpentMinutes();})->sum() ,
        'people' => [
            'id' => $people->id ,
            'lastname' => $people->lastname ,
            'firstname' => $people->firstname ,
            'countesies' => $people->countesies ,
            'organizations' => $people->organizations ,
            'positions' => $people->positions
        ] ,
        'total' => $builder->count() ,
        'meetings' => $builder->get()->map(function($record){
            $record->updateStatus();
            $record->createdBy ;
            $record->updatedBy ;
            $record->type ;

            $record->seichdey_preeng = collect( $record->seichdey_preeng )->map(function($preeng){
                return strlen($preeng) && Storage::disk('meeting')->exists( $preeng ) ? Storage::disk("meeting")->url( $preeng ) : false ;
            });
            $record->reports = collect( $record->reports )->map(function($report){
                return strlen($report) && Storage::disk('meeting')->exists( $report ) ? Storage::disk("meeting")->url( $report ) : false ;
            });
            $record->other_documents = collect( $record->other_documents )->map(function($other){
                return strlen($other) && Storage::disk('meeting')->exists( $other ) ? Storage::disk("meeting")->url( $other ) : false ;
            });
            $record->regulators = $record->regulators()->get()->map(function($regulator){
                $regulator->pdf = strlen( $regulator->pdf ) > 0 
                    ? (
                        \Storage::disk('regulator')->exists( $regulator->pdf )
                        ? \Storage::disk('regulator')->url( $regulator->pdf )
                        : (
                            \Storage::disk('document')->exists( $regulator->pdf )
                            ? \Storage::disk('document')->url( $regulator->pdf )
                            : false
                        )
                    )
                    : false ;
                return [
                    "id" => $regulator->id ,
                    "fid" => $regulator->fid ,
                    "title" => $regulator->title ,
                    "objective" => $regulator->objective ,
                    "pdf" => $regulator->pdf ,
                    "year" => $regulator->year
                ];
            });
            $record->organizations = $record->organizations()->get()->map(function($organization) use( $record ){
                return [
                    "id" => $organization->id ,
                    "name" => $organization->name
                ];
            });
            $record->members = $record->members()->get()->map(function($member) use( $record ){
                $meetingMember = $record->listMembers()->where('people_id', $member->id)->first();
                return [
                    "id" => $member->id ,
                    "firstname" => $member->firstname ,
                    "lastname" => $member->lastname ,
                    "role" => $meetingMember->role ,
                    "group" => $meetingMember->group ,
                    "remark" => $meetingMember->remark
                ];
            });
            $record->rooms = $record->rooms()->get()->map(function($place) use( $record ){
                return [
                    "id" => $place->id ,
                    "organization" => $place->organization == null ? null : [
                        'id' => $place->organization->id ,
                        'name' => $place->organization->name
                    ] ,
                    "name" => $place->name ,
                    "desp" => $place->desp
                ];
            });
            // List members
            $record->listMembers = $record->listMembers->map(function($meetingMember){
                return [
                    'id' => $meetingMember->id ,
                    'role' => $meetingMember->role ,
                    'group' => $meetingMember->group ,
                    'remark' => $meetingMember->remark ,
                    'member' => $meetingMember->member == null ? null : [ 
                        'id' => $meetingMember->member->id , 
                        'firstname' => $meetingMember->member->firstname , 
                        'lastname' => $meetingMember->member->lastname
                    ] ,
                    'attendant' => $meetingMember->attendant == null ? null :
                        [ 
                            'id' => $meetingMember->attendant->id , 
                            'checktime' => $meetingMember->attendant->checktime , 
                            'remark' => $meetingMember->attendant->remark , 
                            'member' => $meetingMember->attendant->member == null ? null : 
                            [ 
                                'id' => $meetingMember->attendant->member->id , 
                                'firstname' => $meetingMember->attendant->member->firstname , 
                                'lastname' => $meetingMember->attendant->member->lastname
                            ] 
                        ]
                ];
            });
            return $record ;
        }) ,
        'totalMeetingsByTypes' => $builder->selectRaw('type_id, count(type_id) as total')->groupby('type_id','people_id','meeting_id')->get()->map(function($meeting) {
            return [
                'type' => [
                    'id' => $meeting->type->id ,
                    'name' => $meeting->type->name ,
                ],
                'total' => $meeting->total
            ];
        })
        ];
    });
    }
    /**
     * Get total meetings which lead by each leader and each meeting types within this Year
     */
    public static function totalMeetingsByTypeThisYear($creatorIds=[]){
        $builder = static::whereIn('id', 
            // Fetching the people_id from meeting_members table to cut down the number of the records
            \App\Models\Meeting\MeetingMember::selectRaw('people_id')->where('role','leader')->where('group','lead_meeting')->groupby('people_id')->pluck('people_id')
        );
        return $builder->get()->map(function($people) use( $creatorIds ) {
            $start = Carbon::now()->startOfYear();
            $end = $start->copy()->addMonths(11);
            $builder = $people->meetingsJoinedAsLeaderOfLeadMeeting()
            ->whereBetween('official_date', [ $start->startOfMonth()->format('Y-m-d') , $end->endOfMonth()->format('Y-m-d') ] );
            if( !empty( $creatorIds ) ) $builder->whereIn('created_by',$creatorIds) ;
            return [
            'totalSpentMinutes' => $builder->get()->map(function($meeting){ return $meeting->totalSpentMinutes();})->sum() ,
            'people' => [
                'id' => $people->id ,
                'lastname' => $people->lastname ,
                'firstname' => $people->firstname ,
                'countesies' => $people->countesies ,
                'organizations' => $people->organizations ,
                'positions' => $people->positions
            ] ,
            'total' => $builder->count() ,
            'meetings' => $builder->get()->map(function($record){
                $record->updateStatus();
                $record->createdBy ;
                $record->updatedBy ;
                $record->type ;

                $record->seichdey_preeng = collect( $record->seichdey_preeng )->map(function($preeng){
                    return strlen($preeng) && Storage::disk('meeting')->exists( $preeng ) ? Storage::disk("meeting")->url( $preeng ) : false ;
                });
                $record->reports = collect( $record->reports )->map(function($report){
                    return strlen($report) && Storage::disk('meeting')->exists( $report ) ? Storage::disk("meeting")->url( $report ) : false ;
                });
                $record->other_documents = collect( $record->other_documents )->map(function($other){
                    return strlen($other) && Storage::disk('meeting')->exists( $other ) ? Storage::disk("meeting")->url( $other ) : false ;
                });
                $record->regulators = $record->regulators()->get()->map(function($regulator){
                    $regulator->pdf = strlen( $regulator->pdf ) > 0 
                        ? (
                            \Storage::disk('regulator')->exists( $regulator->pdf )
                            ? \Storage::disk('regulator')->url( $regulator->pdf )
                            : (
                                \Storage::disk('document')->exists( $regulator->pdf )
                                ? \Storage::disk('document')->url( $regulator->pdf )
                                : false
                            )
                        )
                        : false ;
                    return [
                        "id" => $regulator->id ,
                        "fid" => $regulator->fid ,
                        "title" => $regulator->title ,
                        "objective" => $regulator->objective ,
                        "pdf" => $regulator->pdf ,
                        "year" => $regulator->year
                    ];
                });
                $record->organizations = $record->organizations()->get()->map(function($organization) use( $record ){
                    return [
                        "id" => $organization->id ,
                        "name" => $organization->name
                    ];
                });
                $record->members = $record->members()->get()->map(function($member) use( $record ){
                    $meetingMember = $record->listMembers()->where('people_id', $member->id)->first();
                    return [
                        "id" => $member->id ,
                        "firstname" => $member->firstname ,
                        "lastname" => $member->lastname ,
                        "role" => $meetingMember->role ,
                        "group" => $meetingMember->group ,
                        "remark" => $meetingMember->remark
                    ];
                });
                $record->rooms = $record->rooms()->get()->map(function($place) use( $record ){
                    return [
                        "id" => $place->id ,
                        "organization" => $place->organization == null ? null : [
                            'id' => $place->organization->id ,
                            'name' => $place->organization->name
                        ] ,
                        "name" => $place->name ,
                        "desp" => $place->desp
                    ];
                });
                // List members
                $record->listMembers = $record->listMembers->map(function($meetingMember){
                    return [
                        'id' => $meetingMember->id ,
                        'role' => $meetingMember->role ,
                        'group' => $meetingMember->group ,
                        'remark' => $meetingMember->remark ,
                        'member' => $meetingMember->member == null ? null : [ 
                            'id' => $meetingMember->member->id , 
                            'firstname' => $meetingMember->member->firstname , 
                            'lastname' => $meetingMember->member->lastname
                        ] ,
                        'attendant' => $meetingMember->attendant == null ? null :
                            [ 
                                'id' => $meetingMember->attendant->id , 
                                'checktime' => $meetingMember->attendant->checktime , 
                                'remark' => $meetingMember->attendant->remark , 
                                'member' => $meetingMember->attendant->member == null ? null : 
                                [ 
                                    'id' => $meetingMember->attendant->member->id , 
                                    'firstname' => $meetingMember->attendant->member->firstname , 
                                    'lastname' => $meetingMember->attendant->member->lastname
                                ] 
                            ]
                    ];
                });
                return $record ;
            }) ,
            'totalMeetingsByTypes' => $builder->selectRaw('type_id, count(type_id) as total')->groupby('type_id','people_id','meeting_id')->get()->map(function($meeting) {
                return [
                    'type' => [
                        'id' => $meeting->type->id ,
                        'name' => $meeting->type->name ,
                    ],
                    'total' => $meeting->total
                ];
            })
            ];
        });
    }
    /**
     * Medal
     */
    public function medals(){
        return $this->hasManyThrough( \App\Models\Officer\Modal::class , \App\Models\Officer\OfficerMedal::class , 'officer_id' , 'medal_id' );
    }
    public function officerMedals(){
        return $this->hasMany( \App\Models\Officer\OfficerMedal::class , 'officer_id' , 'id' );
    }
    public function ranks(){
        return $this->hasManyThrough( \App\Models\Officer\Rank::class , \App\Models\Officer\OfficerRank::class , 'officer_id' , 'rank_id' );
    }
    public function officerRanks(){
        return $this->hasMany( \App\Models\Officer\OfficerRank::class , 'officer_id' , 'id' );
    }
    public function officerJobs(){
        return $this->hasMany( \App\Models\Officer\OfficerJob::class , 'officer_id' , 'id' );
    }
    public function archeivements(){
        return $this->hasMany( \App\Models\Officer\Archeivement::class , 'officer_id' , 'id' );
    }
}
