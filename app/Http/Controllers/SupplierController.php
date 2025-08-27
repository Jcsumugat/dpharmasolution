<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    // Display all suppliers
    public function index()
    {
        $suppliers = Supplier::all();
        return view('suppliers.suppliers', compact('suppliers'));
    }

    // Show form to create a new supplier
    public function create()
    {
        return view('suppliers.create');
    }

    // Store a new supplier
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string|max:255',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier added successfully!');
    }

    // Show the edit form
    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        return view('suppliers.edit', compact('supplier'));
    }

    // Update the supplier
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'contact_person' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
            'email'          => 'nullable|email|max:100',
            'address'        => 'nullable|string|max:255',
        ]);

        $supplier = Supplier::findOrFail($id);
        $supplier->update($validated);

        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully!');
    }

    // Delete the supplier
    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully!');
    }
}
