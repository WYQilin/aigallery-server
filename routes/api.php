<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user()->only('id', 'name', 'avatar');
})->middleware('auth:sanctum');

// 登录接口
Route::post('login', [\App\Http\Controllers\LoginController::class, 'login']);
