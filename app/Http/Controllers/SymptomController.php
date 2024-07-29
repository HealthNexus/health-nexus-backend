<?php

namespace App\Http\Controllers;

use App\Models\Symptom;
use Illuminate\Http\Request;

class SymptomController extends Controller
{
    public function index()
    {
        if (auth()->user()->role->slug == 'admin' || auth()->user()->role->slug == 'doctor') {
            $symptoms = Symptom::latest()->get();
            return response(['symptoms' => $symptoms], 200);
        } else {
            return response(['message' => 'Unauthorized'], 401);
        }
    }
}
