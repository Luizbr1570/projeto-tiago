<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

        protected $fillable = [
            'name',
            'slug',
            'plan',
            'active'
        ];

        public function users()
        {
            return $this->hasMany(User::class);
        }

        public function leads()
        {
            return $this->hasMany(Lead::class);
        }

        public function products()
        {
            return $this->hasMany(Product::class);
        }

        public function conversations()
        {
            return $this->hasMany(Conversation::class);
        }

        public function followups()
        {
            return $this->hasMany(Followup::class);
        }
        
        public function metrics()
        {
            return $this->hasMany(DailyMetric::class);
        }

        public function aiInsights()
        {
            return $this->hasMany(\App\Models\AiInsight::class);
        }

        public function chatSessions()
        {
            return $this->hasMany(\App\Models\ChatSession::class);
        }

        public function metaEmbeddedSignupConfig()
        {
            return $this->hasOne(\App\Models\MetaEmbeddedSignupConfig::class);
        }

        public function metaEmbeddedSignupSessions()
        {
            return $this->hasMany(\App\Models\MetaEmbeddedSignupSession::class);
        }
}
