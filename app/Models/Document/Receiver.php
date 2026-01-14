<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;

class Receiver extends Model
{
    protected $guarded = ['id'] ;
    protected $table = "document_transaction_receivers" ;
    /**
     * អ្នកទទួលឯកសារ នៅតាមតំណាក់កាលនីមួយ
     * ព័ត៌មាននៃការផ្ញើរ គឺលេខសម្គាល់ លេខអ្នកទទួល
     * ស្ថានភាពនៃការប្រែប្រួលនៃឯកសារ៖ បានទាញយក បានមើល
     */
    /**
     * Relationships
     */
    public function transaction(){
        return $this->belongsTo( \App\Models\Document\Transaction::class , 'document_transaction_id' , 'id' );
    }
    public function receiver(){
        return $this->belongsTo( \App\Models\User::class , 'receiver_id' , 'id' );
    }
    /**
     * Functions
     */
    public function isSeen(){
        return $this->seen_at != null ? \Carbon\Carbon::parse( $this->seen_at ) : false ;
    }
    public function isDownloaded(){
        return $this->download_at != null ? \Carbon\Carbon::parse( $this->download_at ) : false ;
    }
    public function isPreviewed(){
        return $this->preview_at != null ? \Carbon\Carbon::parse( $this->preview_at ) : false ;
    }
    
}