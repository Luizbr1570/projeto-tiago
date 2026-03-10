<?php

namespace App\Http\Controllers;

use App\DataTransferObjects\MetaEmbeddedSignupEventData;
use App\Models\MetaEmbeddedSignupConfig;
use App\Models\MetaEmbeddedSignupSession;
use App\Services\Meta\MetaEmbeddedSignupService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Throwable;

class MetaEmbeddedSignupController extends Controller
{
    public function __construct(
        private readonly MetaEmbeddedSignupService $embeddedSignupService,
    ) {
    }

    public function index()
    {
        $migrationRequired = !$this->metaTablesExist();
        $config = $this->getOrCreateConfig();
        $latestSession = $migrationRequired ? null : MetaEmbeddedSignupSession::latest('created_at')->first();
        $connectedNumbers = $migrationRequired
            ? new Collection()
            : MetaEmbeddedSignupSession::query()
                ->where(function ($query) {
                    $query->whereNotNull('phone_number_id')
                        ->orWhereNotNull('waba_id')
                        ->orWhereNotNull('business_id');
                })
                ->latest('created_at')
                ->limit(10)
                ->get();

        return view('meta.embedded-signup.index', [
            'config' => $config,
            'latestSession' => $latestSession,
            'connectedNumbers' => $connectedNumbers,
            'metaSystemTokenConfigured' => filled($this->embeddedSignupService->systemUserToken()),
            'migrationRequired' => $migrationRequired,
        ]);
    }

    public function callback()
    {
        $migrationRequired = !$this->metaTablesExist();
        $config = $this->getOrCreateConfig();
        $latestSession = $migrationRequired ? null : MetaEmbeddedSignupSession::latest('created_at')->first();

        return view('meta.embedded-signup.callback', [
            'config' => $config,
            'latestSession' => $latestSession,
            'callbackQuery' => request()->query(),
            'migrationRequired' => $migrationRequired,
        ]);
    }

    public function saveConfig(Request $request)
    {
        if (!$this->metaTablesExist()) {
            return back()->with('error', 'Execute as migrations da integração Meta antes de salvar a configuração.');
        }

        $validated = $request->validate([
            'facebook_app_id' => 'required|string|max:255',
            'graph_api_version' => 'required|string|max:32',
            'configuration_id' => 'required|string|max:255',
            'redirect_uri' => 'required|url|max:2048',
            'integration_status' => 'nullable|string|max:255',
        ]);

        $config = $this->getOrCreateConfig();
        $config->update($validated);

        return back()->with('success', 'Configuração da Meta atualizada com sucesso.');
    }

    public function storeSession(Request $request): JsonResponse
    {
        if (!$this->metaTablesExist()) {
            return response()->json([
                'message' => 'As tabelas da integração Meta ainda não foram criadas. Execute php artisan migrate.',
            ], 503);
        }

        $config = $this->getOrCreateConfig();

        $validator = Validator::make($request->all(), [
            'payload' => 'required|array',
            'source' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            $this->embeddedSignupService->markConfigError($config, 'Payload inválido recebido.', [
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'message' => 'Payload inválido.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $session = $this->embeddedSignupService->storeSession(
                $config,
                new MetaEmbeddedSignupEventData(
                    payload: $validator->validated()['payload'],
                    source: $validator->validated()['source'] ?? 'embedded_signup',
                )
            );

            return response()->json([
                'message' => 'Payload salvo com sucesso.',
                'session' => $this->serializeSession($session->fresh()),
            ], 201);
        } catch (Throwable $exception) {
            $this->embeddedSignupService->markConfigError($config, 'Erro ao persistir retorno da Meta.', [
                'exception' => $exception->getMessage(),
            ]);

            Log::error('Failed to store Meta Embedded Signup payload.', [
                'company_id' => $config->company_id,
                'exception' => $exception,
            ]);

            return response()->json([
                'message' => 'Não foi possível salvar o retorno da Meta.',
            ], 500);
        }
    }

    public function latest(): JsonResponse
    {
        if (!$this->metaTablesExist()) {
            return response()->json([
                'config' => $this->serializeConfig($this->getOrCreateConfig()),
                'latest' => null,
                'migration_required' => true,
                'message' => 'As tabelas da integração Meta ainda não foram criadas.',
            ]);
        }

        $session = MetaEmbeddedSignupSession::latest('created_at')->first();

        return response()->json([
            'config' => $this->serializeConfig($this->getOrCreateConfig()),
            'latest' => $session ? $this->serializeSession($session) : null,
        ]);
    }

    public function sessions(): JsonResponse
    {
        if (!$this->metaTablesExist()) {
            return response()->json([
                'data' => [],
                'migration_required' => true,
                'message' => 'As tabelas da integração Meta ainda não foram criadas.',
            ]);
        }

        $sessions = MetaEmbeddedSignupSession::latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (MetaEmbeddedSignupSession $session) => $this->serializeSession($session));

        return response()->json([
            'data' => $sessions,
        ]);
    }

    private function getOrCreateConfig(): MetaEmbeddedSignupConfig
    {
        $user = Auth::user();

        if (!$this->metaConfigTableExists()) {
            return new MetaEmbeddedSignupConfig([
                'company_id' => $user->company_id,
                'facebook_app_id' => (string) (config('services.meta.app_id') ?? ''),
                'graph_api_version' => config('services.meta.graph_api_version', 'v25.0'),
                'configuration_id' => (string) (config('services.meta.configuration_id') ?? ''),
                'redirect_uri' => config('services.meta.redirect_uri') ?: route('admin.meta.embedded-signup.callback'),
                'integration_status' => 'migration_required',
            ]);
        }

        return MetaEmbeddedSignupConfig::firstOrCreate(
            ['company_id' => $user->company_id],
            [
                'facebook_app_id' => (string) (config('services.meta.app_id') ?? ''),
                'graph_api_version' => config('services.meta.graph_api_version', 'v25.0'),
                'configuration_id' => (string) (config('services.meta.configuration_id') ?? ''),
                'redirect_uri' => config('services.meta.redirect_uri') ?: route('admin.meta.embedded-signup.callback'),
                'integration_status' => 'not_configured',
            ]
        );
    }

    private function serializeConfig(MetaEmbeddedSignupConfig $config): array
    {
        return [
            'id' => $config->id,
            'facebook_app_id' => $config->facebook_app_id,
            'graph_api_version' => $config->graph_api_version,
            'configuration_id' => $config->configuration_id,
            'redirect_uri' => $config->redirect_uri,
            'integration_status' => $config->integration_status,
            'last_connected_at' => $config->last_connected_at?->toIso8601String(),
            'last_callback_at' => $config->last_callback_at?->toIso8601String(),
            'last_error' => $config->last_error,
        ];
    }

    private function serializeSession(MetaEmbeddedSignupSession $session): array
    {
        return [
            'id' => $session->id,
            'source' => $session->source,
            'event_type' => $session->event_type,
            'connection_status' => $session->connection_status,
            'waba_id' => $session->waba_id,
            'phone_number_id' => $session->phone_number_id,
            'business_id' => $session->business_id,
            'display_name' => $session->display_name,
            'code' => $session->code,
            'access_token' => $session->access_token,
            'setup_info' => $session->setup_info,
            'raw_payload' => $session->raw_payload,
            'normalized_payload' => $session->normalized_payload,
            'meta_timestamp' => $session->meta_timestamp?->toIso8601String(),
            'created_at' => $session->created_at?->toIso8601String(),
        ];
    }

    private function metaTablesExist(): bool
    {
        return $this->metaConfigTableExists() && Schema::hasTable('meta_embedded_signup_sessions');
    }

    private function metaConfigTableExists(): bool
    {
        return Schema::hasTable('meta_embedded_signup_configs');
    }
}
