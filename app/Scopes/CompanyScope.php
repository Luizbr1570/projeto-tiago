<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check()) {
            $builder->where('company_id', Auth::user()->company_id);
            return;
        }

        // Sem usuário autenticado, bloqueia todos os resultados.
        // Evita vazamento de dados em contextos inesperados (artisan, testes, etc).
        // Jobs e services devem usar withoutGlobalScopes() + filtro manual de company_id.
        $builder->whereRaw('1 = 0');
    }
}