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

        // Kirim data yang sudah diurutkan dan sudah ada total stoknya ke view
        return view('kelola-barang.index', compact('barangs'));
    }

    public function create()
    {
        // Definisikan opsi satuan
        $satuanOptions = [
            'SAK', 'BUAH', 'KG', 'METER', 'LITER', 'LBR', 'BTG', 'KTK'
        ];
        
        // Kirim opsi tersebut ke view
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
        // Definisikan opsi satuan, sama seperti di method create()
        $satuanOptions = [
            'SAK', 'BUAH', 'KG', 'METER', 'LITER', 'LBR', 'BTG', 'KTK'
        ];

        // Kirim data barang yang mau diedit dan opsi satuan ke view
        return view('kelola-barang.edit', compact('barang', 'satuanOptions'));
    }

    public function update(Request $request, Barang $barang)
    {
        // 1. Validasi input
        $request->validate([
            // 'kode' tidak perlu divalidasi karena readonly
            'nama' => 'required|string|max:100',
            'satuan' => 'required|string',
        ]);

        // 2. Update data di database
        $barang->update([
            'nama' => $request->nama,
            'satuan' => $request->satuan,
        ]);

        // 3. Redirect kembali ke halaman utama dengan pesan sukses
        return redirect()->route('barang.index')
                        ->with('success', 'Data barang berhasil diperbarui!');
    }

    public function destroy(Barang $barang) // <--- UBAH DI SINI
    {
        // Kita tidak perlu mencari barang lagi, Laravel sudah melakukannya untuk kita.
        $barang->delete();

        // Redirect kembali dengan pesan sukses
        return redirect()->route('barang.index')
                        ->with('success', 'Barang berhasil dihapus.');
    }
}
