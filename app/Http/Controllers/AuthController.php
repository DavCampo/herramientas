<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username'=>'required|string',
            'password'=>'required|string'
        ]);
        $response = Http::post('http://192.168.50.18/herramientas/app/Session/signIn', [
            'username'=>$request->username,
            'password'=>$request->password
        ]);
        
        $data = $response->json();
        return response()->json(['mensaje'=>$data]);
        
    }
}
