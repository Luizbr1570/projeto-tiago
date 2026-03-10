<?php

namespace Tests\Feature;

use App\Models\MetaEmbeddedSignupSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaEmbeddedSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_meta_embedded_signup_page(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get(route('admin.meta.embedded-signup.index'));

        $response->assertOk();
        $response->assertSee('Meta / Embedded Signup');
    }

    public function test_admin_can_persist_embedded_signup_payload(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->post(route('admin.meta.embedded-signup.config.save'), [
            'facebook_app_id' => '123456',
            'graph_api_version' => 'v25.0',
            'configuration_id' => 'config-abc',
            'redirect_uri' => 'https://example.test/admin/meta/embedded-signup/callback',
            'integration_status' => 'ready',
        ])->assertRedirect();

        $payload = [
            'type' => 'WA_EMBEDDED_SIGNUP',
            'status' => 'connected',
            'code' => 'oauth-code',
            'data' => [
                'waba_id' => 'waba-1',
                'phone_number_id' => 'phone-1',
                'business_id' => 'business-1',
                'display_name' => 'Main Number',
                'setup_info' => [
                    'quality_rating' => 'GREEN',
                ],
            ],
        ];

        $response = $this->actingAs($user)->postJson(route('api.meta.embedded-signup.session.store'), [
            'payload' => $payload,
            'source' => 'post_message',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('session.waba_id', 'waba-1');
        $response->assertJsonPath('session.phone_number_id', 'phone-1');
        $response->assertJsonPath('session.connection_status', 'connected');

        $this->assertDatabaseHas('meta_embedded_signup_sessions', [
            'company_id' => $user->company_id,
            'waba_id' => 'waba-1',
            'phone_number_id' => 'phone-1',
            'business_id' => 'business-1',
            'code' => 'oauth-code',
        ]);

        $this->assertNotNull(MetaEmbeddedSignupSession::first()?->normalized_payload);
    }
}
