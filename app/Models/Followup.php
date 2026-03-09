<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Followup extends Model
{
    use SoftDeletes, HasFactory, HasUuids, BelongsToCompany;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'lead_id',
        'status',
        'sent_at',
        'recovered' // recolocado
    ];

    protected $casts = [
        'recovered' => 'boolean',
        'sent_at'   => 'datetime',
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function lead() { return $this->belongsTo(Lead::class); }
}