<?php

namespace App\Http\Controllers;

use App\Models\Encuesta;
use Illuminate\Http\Request;

class EncuestaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return response()->json(Encuesta::all(),200); 
        return response()->json([
            'message' => 'es una prueba', 
            'success' => true,
            ], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Encuesta $encuesta)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Encuesta $encuesta)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Encuesta $encuesta)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Encuesta $encuesta)
    {
        //
    }
}
