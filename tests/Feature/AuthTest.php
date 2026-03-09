<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── Helpers ─────────────────────────────────────────────────

    private function makeUser(array $attrs = []): User
    {
        return User::factory()->create($attrs);
    }

    private function makeUnverifiedUser(): User
    {
        return User::factory()->unverified()->create();
    }

    // ─── Login ───────────────────────────────────────────────────

    public function test_pagina_login_carrega(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_login_com_credenciais_validas(): void
    {
        $user = $this->makeUser();

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_com_credenciais_invalidas(): void
    {
        $this->makeUser(['email' => 'test@test.com']);

        $this->post('/login', [
            'email'    => 'test@test.com',
            'password' => 'senha-errada',
        ])->assertRedirect()
          ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_email_nao_existente(): void
    {
        $this->post('/login', [
            'email'    => 'naoexiste@test.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_senha_acima_de_72_chars_bloqueada(): void
    {
        $this->post('/login', [
            'email'    => 'test@test.com',
            'password' => str_repeat('a', 73),
        ])->assertSessionHasErrors('password');
    }

    public function test_login_bloqueado_sem_email_verificado(): void
    {
        $user = $this->makeUnverifiedUser();

        $this->post('/login', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_autenticado_nao_acessa_pagina_login(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    // ─── Register ────────────────────────────────────────────────

    public function test_pagina_register_carrega(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_registro_cria_empresa_e_usuario(): void
    {
        $this->post('/register', [
            'company_name'          => 'Minha Empresa',
            'name'                  => 'João Silva',
            'email'                 => 'joao@empresa.com',
            'password'              => 'Senha123',
            'password_confirmation' => 'Senha123',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('companies', ['name' => 'Minha Empresa']);
        $this->assertDatabaseHas('users', ['email' => 'joao@empresa.com', 'role' => 'admin']);
    }

    public function test_registro_senha_muito_curta_bloqueada(): void
    {
        $this->post('/register', [
            'company_name'          => 'Empresa',
            'name'                  => 'João',
            'email'                 => 'joao@empresa.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');
    }

    public function test_registro_senha_sem_letras_bloqueada(): void
    {
        $this->post('/register', [
            'company_name'          => 'Empresa',
            'name'                  => 'João',
            'email'                 => 'joao@empresa.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
        ])->assertSessionHasErrors('password');
    }

    public function test_registro_senha_acima_de_72_chars_bloqueada(): void
    {
        $senha = str_repeat('Aa1', 25); // 75 chars

        $this->post('/register', [
            'company_name'          => 'Empresa',
            'name'                  => 'João',
            'email'                 => 'joao@empresa.com',
            'password'              => $senha,
            'password_confirmation' => $senha,
        ])->assertSessionHasErrors('password');
    }

    public function test_registro_email_duplicado_bloqueado(): void
    {
        $this->makeUser(['email' => 'existente@test.com']);

        $this->post('/register', [
            'company_name'          => 'Empresa',
            'name'                  => 'Outro',
            'email'                 => 'existente@test.com',
            'password'              => 'Senha123',
            'password_confirmation' => 'Senha123',
        ])->assertSessionHasErrors('email');
    }

    public function test_registro_confirmacao_senha_incorreta_bloqueada(): void
    {
        $this->post('/register', [
            'company_name'          => 'Empresa',
            'name'                  => 'João',
            'email'                 => 'joao@empresa.com',
            'password'              => 'Senha123',
            'password_confirmation' => 'SenhaDiferente123',
        ])->assertSessionHasErrors('password');
    }

    // ─── Logout ──────────────────────────────────────────────────

    public function test_logout_funciona(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    // ─── Empresa inativa ─────────────────────────────────────────

    public function test_usuario_de_empresa_inativa_e_bloqueado(): void
    {
        $company = Company::factory()->inactive()->create();
        $user    = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect('/login');
    }
}
