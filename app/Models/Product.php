<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, HasFactory, HasUuids, BelongsToCompany;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id', 'name', 'category', 'avg_price'
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function interests() { return $this->hasMany(ProductInterest::class); }
    public function sales(){return $this->hasMany(\App\Models\Sale::class);}
}