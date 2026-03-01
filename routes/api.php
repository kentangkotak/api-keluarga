<?php

use App\Http\Controllers\PohonKeluargaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Ambil family tree berdasarkan root anggota
Route::get('/family-tree', [PohonKeluargaController::class, 'tampil']);
Route::post('/family-tree-simpan', [PohonKeluargaController::class, 'store']);

Route::get('/cari-ortu', [PohonKeluargaController::class, 'cariortu']);
