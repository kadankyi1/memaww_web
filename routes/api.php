<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
*/

// LOGIN
Route::post('/v1/user/sign-in',[App\Http\Controllers\version1\UserController::class, 'enterApp']);