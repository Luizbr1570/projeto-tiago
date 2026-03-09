<?php

namespace App\Http\Controllers;

use App\Models\ProductInterest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductInterestController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ProductInterest::class);

        $interests = ProductInterest::with(['lead', 'product'])
            ->latest()
            ->paginate(20);

        return view('product_interests.index', compact('interests'));
    }

    public function store(Request $request)
    {
        $validado = $request->validate([
            'lead_id' => [
                'required',
                'uuid',
                // Garante que o lead pertence à empresa do usuário
                \Illuminate\Validation\Rule::exists('leads', 'id')->where('company_id', Auth::user()->company_id),
            ],
            'product_id' => [
                'required',
                'uuid',
                // Garante que o produto pertence à empresa do usuário
                \Illuminate\Validation\Rule::exists('products', 'id')->where('company_id', Auth::user()->company_id),
            ],
        ]);

        ProductInterest::firstOrCreate(
            [
                'lead_id'    => $validado['lead_id'],
                'product_id' => $validado['product_id'],
            ],
            [
                'company_id' => Auth::user()->company_id,
            ]
        );

        return back()->with('success', 'Interesse registrado');
    }

    public function destroy(string $id)
    {
        $interest = ProductInterest::where('company_id', Auth::user()->company_id)->findOrFail($id);

        $this->authorize('delete', $interest);

        $interest->delete();

        return back()->with('success', 'Interesse removido');
    }
}