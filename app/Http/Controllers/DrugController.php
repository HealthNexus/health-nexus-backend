<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use Illuminate\Http\Request;

class DrugController extends Controller
{
    public function index()
    {
        if (auth()->user()->role->slug == 'admin' || auth()->user()->role->slug == 'doctor') {
            $symptoms = Drug::latest()->get();
            return response(['drugs' => $symptoms], 200);
        } else {
            return response(['message' => 'Unauthorized'], 401);
        }
    }
}
