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

// SEND MESSAGE
Route::middleware('auth:api')->post('/v1/user/send-message', [App\Http\Controllers\version1\UserController::class, 'sendMessage']);

// GET MY MESSAGES
Route::middleware('auth:api')->post('/v1/user/get-my-messages', [App\Http\Controllers\version1\UserController::class, 'getMyMessages']);

// UPDATE ORDER PAYMENT
Route::middleware('auth:api')->post('/v1/user/update-order-payment', [App\Http\Controllers\version1\UserController::class, 'updateOrderPaymentStatus']);

// UPDATE ORDER PAYMENT
Route::middleware('auth:api')->post('/v1/user/update-order', [App\Http\Controllers\version1\UserController::class, 'updateOrder']);