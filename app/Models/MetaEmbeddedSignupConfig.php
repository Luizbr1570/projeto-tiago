<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaEmbeddedSignupConfig extends Model
{
    use BelongsToCompany, HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'facebook_app_id',
        'graph_api_version',
        'configuration_id',
        'redirect_uri',
        'integration_status',
        'last_connected_at',
        'last_callback_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'last_connected_at' => 'datetime',
            'last_callback_at' => 'datetime',
            'last_error' => 'array',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function sessions()
    {
        return $this->hasMany(MetaEmbeddedSignupSession::class);
    }
}
