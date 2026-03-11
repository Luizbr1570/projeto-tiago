<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaEmbeddedSignupSession extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'meta_embedded_signup_config_id',
        'source',
        'event_type',
        'connection_status',
        'waba_id',
        'phone_number_id',
        'business_id',
        'display_name',
        'code',
        'access_token',
        'setup_info',
        'raw_payload',
        'normalized_payload',
        'meta_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'setup_info' => 'array',
            'raw_payload' => 'array',
            'normalized_payload' => 'array',
            'meta_timestamp' => 'datetime',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function config()
    {
        return $this->belongsTo(MetaEmbeddedSignupConfig::class, 'meta_embedded_signup_config_id');
    }
}
