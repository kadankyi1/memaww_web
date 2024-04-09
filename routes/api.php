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

// PLACE ORDER
Route::middleware('auth:api')->post('/v1/user/request-collection', [App\Http\Controllers\version1\UserController::class, 'requestCollection']);

// PLACE CALLBACK REQUEST
Route::middleware('auth:api')->post('/v1/user/request-collection-callback', [App\Http\Controllers\version1\UserController::class, 'requestCollectionCallBack']);

// GET MY ORDERS
Route::middleware('auth:api')->post('/v1/user/get-my-orders', [App\Http\Controllers\version1\UserController::class, 'getMyOrdersListing']);