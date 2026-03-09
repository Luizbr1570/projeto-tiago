<?php

namespace App\Traits;

use App\Scopes\CompanyScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Aplica o scope automaticamente em todas as queries
        static::addGlobalScope(new CompanyScope);

        // Injeta company_id automaticamente ao criar
        static::creating(function ($model) {
            if (Auth::check() && empty($model->company_id)) {
                $model->company_id = Auth::user()->company_id;
            }
        });
    }
}