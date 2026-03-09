<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInterest extends Model
{
    use HasFactory, HasUuids, BelongsToCompany;

    protected $table = 'product_interest';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'lead_id', 'product_id'
    ];

    public function company() { return $this->belongsTo(Company::class); }
    public function lead() { return $this->belongsTo(Lead::class); }
    public function product() { return $this->belongsTo(Product::class); }
}