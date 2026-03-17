<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        $user    = Auth::user();
        $company = $user->company;

        return view('settings.index', compact('user', 'company'));
    }

    public function updateCompany(Request $request)
    {
        // FIX #7: qualquer usuário autenticado conseguia alterar o nome da empresa.
        // Apenas admins devem poder modificar dados da empresa.
        // Alternativa: proteger a rota com middleware role:admin no routes/web.php.
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Apenas administradores podem alterar os dados da empresa.');
        }

        $validado = $request->validate([
            'name'              => 'required|string|max:255',
            'conversion_base'   => 'nullable|numeric|min:0|max:1',
            'conversion_with_ai' => 'nullable|numeric|min:0|max:1',
        ]);

        $user = User::findOrFail(Auth::id());
        $user->company->update($validado);

        return back()->with('success', '✅ Empresa atualizada com sucesso.');
    }

    public function updateProfile(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $validado = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update($validado);

        return back()->with('success', '✅ Perfil atualizado com sucesso.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', 'max:72', Password::min(8)->letters()->numbers()],
        ]);

        $user = User::findOrFail(Auth::id());

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Senha atual incorreta.']);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', '✅ Senha alterada com sucesso.');
    }
}