<?php

namespace App\Models;

use App\Models\ChatSession;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes, HasFactory, HasUuids, BelongsToCompany;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id', 'phone', 'first_contact', 'city', 'status', 'source'
    ];
     protected $casts = [
        'first_contact' => 'datetime',
    ];

    public function company()
    { 
        return $this->belongsTo(Company::class); 
    }

    public function conversations() { return $this->hasMany(Conversation::class); }
    public function productInterests() { return $this->hasMany(ProductInterest::class); }
    public function followups() { return $this->hasMany(Followup::class); }
    public function chatSessions() { return $this->hasMany(ChatSession::class); }
}