<?php

namespace App\Http\Controllers;

use App\Models\Disease;
use Illuminate\Http\Request;

class DiseaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $disease = Disease::all();
        return response(['disease' => $disease], 200);
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
    public function show(Disease $disease)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Disease $disease)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Disease $disease)
    {
        //
    }
}
