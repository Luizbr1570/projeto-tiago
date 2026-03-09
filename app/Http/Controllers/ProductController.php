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

        $products = Product::withCount('interests')
            ->orderByDesc('id')
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
}