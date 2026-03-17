<?php

namespace App\Providers;

use App\Models\AiInsight;
use App\Models\DailyMetric;
use App\Models\ChatSession;
use App\Models\Conversation;
use App\Models\Followup;
use App\Models\Lead;
use App\Models\Product;
use App\Models\ProductInterest;
use App\Models\Sale;
use App\Policies\AiInsightPolicy;
use App\Policies\DailyMetricPolicy;
use App\Policies\ChatSessionPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\FollowupPolicy;
use App\Policies\LeadPolicy;
use App\Policies\ProductInterestPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SalePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Lead::class            => LeadPolicy::class,
        Product::class         => ProductPolicy::class,
        ProductInterest::class => ProductInterestPolicy::class,
        Conversation::class    => ConversationPolicy::class,
        Followup::class        => FollowupPolicy::class,
        ChatSession::class     => ChatSessionPolicy::class,
        AiInsight::class       => AiInsightPolicy::class,
        DailyMetric::class     => DailyMetricPolicy::class,
        Sale::class            => SalePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}