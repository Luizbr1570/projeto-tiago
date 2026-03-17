<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes, HasFactory, HasUuids, BelongsToCompany;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id', 'lead_id', 'product_id', 'value', 'notes', 'sold_at',
    ];

    protected $casts = [
        'sold_at' => 'datetime',
        'value'   => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}