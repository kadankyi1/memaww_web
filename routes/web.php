<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::get('/contact', function () {
    return view('contact');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('/service-policy', function () {
    return view('service-policy');
});

Route::get('/welcome', function () {
    return view('welcome');
});


Route::get('/order-payment-update', function () {
    return view('update-order-payment');
});

// ADMINER
//Route::any('/adminer', '\Aranyasen\LaravelAdminer\AdminerController@index');
Route::any('adminer', '\Aranyasen\LaravelAdminer\AdminerAutologinController@index');