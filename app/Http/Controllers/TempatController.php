<?php

namespace App\Http\Controllers;

use App\Models\Tempat;
use Illuminate\Http\Request;

class TempatController extends Controller
{
    public function index()
    {
        $tempats = Tempat::orderBy('nama', 'asc')->get();
        return view('kelola-tempat.index', compact('tempats'));
    }

    public function create()
    {
        return view('kelola-tempat.create');
    }

    public function store(Request $request)
    {
        $request->validate(['nama' => 'required|string|max:255|unique:tempat,nama']);
        Tempat::create($request->all());
        return redirect()->route('tempat.index')->with('success', 'Tempat berhasil ditambahkan.');
    }

    public function edit(Tempat $tempat)
    {
        return view('kelola-tempat.edit', compact('tempat'));
    }

    public function update(Request $request, Tempat $tempat)
    {
        $request->validate(['nama' => 'required|string|max:255|unique:tempat,nama,' . $tempat->id]);
        $tempat->update($request->all());
        return redirect()->route('tempat.index')->with('success', 'Tempat berhasil diperbarui.');
    }

    public function destroy(Tempat $tempat)
    {
        $tempat->delete();
        return redirect()->route('tempat.index')->with('success', 'Tempat berhasil dihapus.');
    }
}