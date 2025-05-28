<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index()
    {
        $barangs = Barang::withSum('stok', 'jumlah')
                       ->orderBy('kode', 'asc')
                       ->get();

        return view('kelola-barang.index', compact('barangs'));
    }

    public function create()
    {
        $satuanOptions = [
            'SAK', 'BUAH', 'KG', 'METER', 'LITER', 'LBR', 'BTG', 'KTK'
        ];
        
        return view('kelola-barang.create', compact('satuanOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode' => 'required|string|max:10|unique:barang,kode',
            'nama' => 'required|string|max:100',                  
            'satuan' => 'required|string',
        ]);

        Barang::create([
            'kode' => $request->kode,
            'nama' => $request->nama,
            'satuan' => $request->satuan,
        ]);

        return redirect()->route('barang.index')
                         ->with('success', 'Barang berhasil ditambahkan!');
    }

    public function edit(Barang $barang)
    {
        $satuanOptions = [
            'SAK', 'BUAH', 'KG', 'METER', 'LITER', 'LBR', 'BTG', 'KTK'
        ];

        return view('kelola-barang.edit', compact('barang', 'satuanOptions'));
    }

    public function update(Request $request, Barang $barang)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'satuan' => 'required|string',
        ]);

        $barang->update([
            'nama' => $request->nama,
            'satuan' => $request->satuan,
        ]);

        return redirect()->route('barang.index')
                        ->with('success', 'Data barang berhasil diperbarui!');
    }

    public function destroy(Barang $barang)
    {
        $barang->delete();

        return redirect()->route('barang.index')
                        ->with('success', 'Barang berhasil dihapus.');
    }
}
