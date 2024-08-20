<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Caisse;

class CaisseController extends Controller
{
    protected $table = 'caisse';
    public function index()
    {
        $caisses = Caisse::all();

        return response()->json($caisses);
    }
}
