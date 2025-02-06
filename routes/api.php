<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user()->only('id', 'name', 'avatar');
})->middleware('auth:sanctum');

// 登录接口
Route::post('login', [\App\Http\Controllers\LoginController::class, 'login']);
// 图片列表接口
Route::get('images', [\App\Http\Controllers\ImageController::class, 'index']);
// 合集列表接口
Route::get('collections', [\App\Http\Controllers\ImageController::class, 'collections']);
// 贡献热力图
Route::get('contribution', [\App\Http\Controllers\ImageController::class, 'getContributionData']);
// 图片转视频接口
Route::post('images2video', [\App\Http\Controllers\ImageController::class, 'imagesMergeVideo']);
