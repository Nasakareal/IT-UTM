<?php

namespace App\Http\Controllers;

use App\Models\Requerimiento;
use Illuminate\Http\Request;

class RequerimientoController extends Controller
{
    public function index()
    {
        $documentosPendientes = Requerimiento::where('user_id', auth()->id())
                                ->where('estado', 'pendiente')
                                ->get();

        return view('home', compact('documentosPendientes'));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(Requerimiento $requerimiento)
    {
        //
    }

    public function edit(Requerimiento $requerimiento)
    {
        //
    }

    public function update(Request $request, Requerimiento $requerimiento)
    {
        //
    }

    public function destroy(Requerimiento $requerimiento)
    {
        //
    }
}
