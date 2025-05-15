<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Servicios\ServicioDiscado;
use App\Http\Controllers\Servicios\ServicioEmail;
use App\Http\Controllers\Servicios\ServicioSMS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login',[AuthController::class,'login']);
Route::get('/sms/obtenerClientes',[ServicioSMS::class,'ObtenerClientes']);
Route::get('/sms/obtenerDidGerente',[ServicioSMS::class,'obtenerDidGerente']);
Route::get('/sms/obtenerWhatsAppGerente',[ServicioSMS::class,'obtenerWhatsAppGerente']);
Route::get('/sms/obtenerMascaraCliente',[ServicioSMS::class,'obtenerMascaraCliente']);
Route::get('/sms/construirBitacoraResultadosSS',[ServicioSMS::class,'construirBitacoraResultadosSS']);
Route::get('/sms/construirBitacoraResumenEnvios',[ServicioSMS::class,'construirBitacoraResumenEnvios']);
Route::get('/sms/construirBitacoraResumenPlantillas',[ServicioSMS::class,'construirBitacoraResumenPlantillas']);
Route::get('/sms/construirReporteBitacoraResultados',[ServicioSMS::class,'construirReporteBitacoraResultados']);
Route::get('/sms/construirReporteBitacoraResumenEnvios',[ServicioSMS::class,'construirReporteBitacoraResumenEnvios']);
Route::resource('ServicioDiscado',ServicioDiscado::class);
Route::resource('ServicioSMSEmail',ServicioEmail::class);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
