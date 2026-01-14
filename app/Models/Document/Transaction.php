<?php

namespace App\Models\Document;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $guarded = ['id'] ;
    /**
     * ការបញ្ជូនឯកសារត្រូវមានដូចជា៖ 
     * ១. ព័ត៌មានគោល៖ អ្នកបញ្ជូន(មានកន្លែងបញ្ជូន និងតួនាទីភ្ជាប់ជាមួយ) អ្នកទទួល(មានកន្លែងទទួល និងតួនាទីភ្ជាប់ជាមួយ) ឯកសារ(អាចជា Word និង PDF ឬមានតែWord)
     * ២. កត់ត្រា ម៉ោងផ្ញើរ ម៉ោងទទួល 
     * ៣​. កត់ត្រា ការបើកឯកសារ (បានមើល ឬទាញយក)
     * ៤. ភ្ជាប់ការដឹកជញ្ជូន (ភ្ជាប់ការដឹកជញ្ជូនដែល មានពីមុន និងបន្ទាប់ ជាមួយខ្លួនឯង)
     */
    protected $table="document_transactions" ;
    /**
     * Relationships
     */

    public function document(){
        return $this->belongsTo( \App\Models\Document\Document::class , 'document_id' , 'id' );
    }
    public function sourceOrganization(){
        return $this->hasOne( \App\Models\Organization\Organization::class , 'source_organization_id' , 'id' );
    }
    public function destinationOrganization(){
        return $this->hasOne( \App\Models\Organization\Organization::class , 'destination_organization_id' , 'id' );
    }
    public function sender(){
        return $this->belongsTo( \App\Models\User::class , 'sender_id' , 'id' );
    }
    public function receivers(){
        return $this->hasMany( \App\Models\Document\Receiver::class , 'document_transaction_id' , 'id' );
    }
    public function previous(){
        return $this->hasOne( \App\Models\Document\Transaction::class , 'id' , 'previous_transaction_id' );
    }
    public function next(){
        return $this->hasOne( \App\Models\Document\Transaction::class , 'id' , 'next_transaction_id' );
    }
}
