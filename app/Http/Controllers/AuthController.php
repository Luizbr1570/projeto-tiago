<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validado = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|max:72'
        ]);

        if (Auth::attempt($validado)) {
            $request->session()->regenerate();

            return redirect()->intended('/dashboard');
        }

        return back()->withErrors(['email' => 'Credenciais inválidas.'])->onlyInput('email');
    }

    public function register(Request $request)
    {
        $validado = $request->validate([
            'company_name' => 'required|string|max:255',
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users',
            'password'     => ['required', 'confirmed', 'max:72', Password::min(8)->letters()->numbers()]
        ]);

        // FIX: slug gerado DENTRO da transaction com lockForUpdate() para evitar
        // race condition quando dois registros simultaneos usam o mesmo nome de empresa.
        $user = DB::transaction(function () use ($validado) {
            $baseSlug = Str::slug($validado['company_name']);
            $slug     = $baseSlug;
            $count    = 1;

            while (Company::where('slug', $slug)->lockForUpdate()->exists()) {
                $slug = "{$baseSlug}-{$count}";
                $count++;
            }
            $company = Company::create([
                'name'   => $validado['company_name'],
                'slug'   => $slug,
                'plan'   => 'free',
                'active' => true,
            ]);

            return User::create([
                'name'       => $validado['name'],
                'email'      => $validado['email'],
                'password'   => Hash::make($validado['password']),
                'company_id' => $company->id,
                'role'       => 'admin',
            ]);
        });

        Auth::login($user);

        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}