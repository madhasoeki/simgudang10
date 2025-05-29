<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Ambil semua data riwayat dan urutkan dari yang terbaru
        $histories = History::with('user')->latest()->get();
        return view('history.index', compact('histories'));
    }
}