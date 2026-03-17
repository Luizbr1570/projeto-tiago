<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::where('company_id', Auth::user()->company_id)
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', User::class);

        $validado = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,agent',
            'password' => ['required', 'confirmed', 'max:72', Password::min(8)->letters()->numbers()],
        ]);

        User::create([
            'name'       => $validado['name'],
            'email'      => $validado['email'],
            'role'       => $validado['role'],
            'password'   => Hash::make($validado['password']),
            'company_id' => Auth::user()->company_id,
        ]);

        return back()->with('success', '✅ Usuário criado com sucesso.');
    }

    public function update(Request $request, string $id)
    {
        $user = User::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('update', $user);

        if ($user->id === Auth::id() && $request->input('role') !== 'admin') {
            return back()->withErrors(['role' => 'Você não pode remover seu próprio papel de administrador.']);
        }

        $validado = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,agent',
        ]);

        $user->update($validado);

        return back()->with('success', '✅ Usuário atualizado com sucesso.');
    }

    public function resetPassword(Request $request, string $id)
    {
        $user = User::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('update', $user);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['password' => 'Use a página de configurações para alterar sua própria senha.']);
        }

        $validado = $request->validate([
            'password' => ['required', 'confirmed', 'max:72', Password::min(8)->letters()->numbers()],
        ]);

        $user->update(['password' => Hash::make($validado['password'])]);

        return back()->with('success', '✅ Senha redefinida com sucesso.');
    }

    public function destroy(string $id)
    {
        $user = User::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('delete', $user);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['delete' => 'Você não pode remover sua própria conta.']);
        }

        $user->delete();

        // FIX: retorna JSON quando chamado via fetch (confirmDelete JS)
        // Sem isso o JS não consegue processar a resposta e o item não some da tela
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Usuário removido']);
        }

        return back()->with('success', '✅ Usuário removido com sucesso.');
    }

    public function restore(string $id)
    {
        $user = User::withTrashed()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $this->authorize('restore', $user);

        $user->restore();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Usuário restaurado']);
        }

        return back()->with('success', '✅ Usuário restaurado.');
    }
}