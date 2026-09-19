<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InternetPackageController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        \App\Models\InternetPackage::create($validated);

        return back()->with('success', 'Paket Internet berhasil ditambahkan.');
    }

    public function destroy(\App\Models\InternetPackage $internetPackage)
    {
        $internetPackage->delete();
        return back()->with('success', 'Paket Internet berhasil dihapus.');
    }
}
