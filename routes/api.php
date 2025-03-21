<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
P{2gVVEjYnEFq{,S
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
//Route::middleware('auth:api')->post('/v1/user/send-message', [App\Http\Controllers\version1\UserController::class, 'sendMessage']);

// GET MY MESSAGES
//Route::middleware('auth:api')->post('/v1/user/get-my-messages', [App\Http\Controllers\version1\UserController::class, 'getMyMessages']);

// UPDATE ORDER PAYMENT
Route::middleware('auth:api')->post('/v1/user/update-order-payment', [App\Http\Controllers\version1\UserController::class, 'updateOrderPaymentStatus']);

// UPDATE ORDER PAYMENT
Route::middleware('auth:api')->post('/v1/user/update-order', [App\Http\Controllers\version1\UserController::class, 'updateOrder']);

// UPDATE ORDER PAYMENT
Route::middleware('auth:api')->post('/v1/user/send-notification', [App\Http\Controllers\version1\UserController::class, 'sendNotification']);

// GET MY NOTIFICATIONS
Route::middleware('auth:api')->post('/v1/user/get-my-notifications', [App\Http\Controllers\version1\UserController::class, 'getMyNotificationsListing']);

// UPDATE USER INFO
Route::middleware('auth:api')->post('/v1/user/update-user-info', [App\Http\Controllers\version1\UserController::class, 'updateUserInfo']);

// GET MY MESSAGES
Route::middleware('auth:api')->post('/v1/user/get-subscription-pricing', [App\Http\Controllers\version1\UserController::class, 'getSubscriptionPricing']);

// GET MY MESSAGES
Route::middleware('auth:api')->post('/v1/user/set-user-subscription', [App\Http\Controllers\version1\UserController::class, 'setUserSubscription']);

// GET MY MESSAGES
Route::middleware('auth:api')->post('/v1/user/get-todays-subscription-pickups', [App\Http\Controllers\version1\UserController::class, 'getTodaysSubscriptionPickups']);