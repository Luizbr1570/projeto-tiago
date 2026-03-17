<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::where('company_id', Auth::user()->company_id)
            ->withCount('interests')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Product::class);

        $validado = $request->validate([
            'name'      => 'required|string|max:255',
            'category'  => 'nullable|string|max:255',
            'avg_price' => 'nullable|numeric|min:0'
        ]);

        // FIX #2: company_id injetado explicitamente antes do create.
        // O BelongsToCompany trait injeta via evento creating(), mas depende
        // de Auth::check() — injeção explícita é mais segura e legível.
        $validado['company_id'] = Auth::user()->company_id;

        Product::create($validado);

        return back()->with('success', '✅ Produto criado com sucesso.');
    }

    public function update(Request $request, string $id)
    {
        $product = Product::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('update', $product);

        $product->update($request->validate([
            'name'      => 'required|string|max:255',
            'category'  => 'nullable|string|max:255',
            'avg_price' => 'nullable|numeric|min:0'
        ]));

        return back()->with('success', '✅ Produto atualizado com sucesso.');
    }

    public function destroy(string $id)
    {
        $product = Product::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('delete', $product);

        $product->delete();

        return redirect(route('products.index'))
            ->with('success', '✅ Produto removido com sucesso.');
    }
    public function restore(string $id)
    {
        $product = Product::withoutGlobalScopes()
            ->onlyTrashed()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);
    
        $this->authorize('restore', $product);
    
        $product->restore();
    
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Produto restaurado']);
        }
    
        return back()->with('success', '✅ Produto restaurado com sucesso.');
    }
}