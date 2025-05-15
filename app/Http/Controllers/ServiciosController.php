<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ServiciosController extends Controller
{
    public function obtenerClientes(Request $request){
        
        $response = Http::post('http://192.168.50.18/herramientas/app/ServicioSMS/obtenerClientes', [
            'username'=>$request->username,
            'password'=>$request->password
        ]);
        

    }
}