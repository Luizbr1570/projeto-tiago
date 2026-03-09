<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyMetric extends Model
{
    use HasFactory, HasUuids, BelongsToCompany;

    protected $keyType = 'string';
    public $incrementing = false;

    // FIX B05: a migration só cria created_at (sem updated_at).
    // Sem essa linha, o Eloquent tenta fazer UPDATE em updated_at que não existe,
    // quebrando o UpdateDailyMetricsJob com "column not found" em produção.
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'date', 'leads', 'conversations', 'recovered_leads', 'estimated_revenue'
    ];

    public function company() { return $this->belongsTo(Company::class); }
}