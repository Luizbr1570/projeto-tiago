<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiInsight extends Model
{
    use HasFactory, HasUuids, BelongsToCompany, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;

    // CORRIGIDO: desativa timestamps automáticos do Eloquent pois a tabela
    // só possui created_at (sem updated_at). O CURRENT_TIMESTAMP da migration
    // popula created_at no INSERT. Declarar CREATED_AT evita qualquer tentativa
    // do Eloquent de escrever em uma coluna updated_at inexistente.
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'company_id',
        'insight',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}