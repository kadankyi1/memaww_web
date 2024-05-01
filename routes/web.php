<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

Route::get('/contact', function () {
    return view('contact');
});

Route::get('/welcome', function () {
    return view('welcome');
});

// ADMINER
//Route::any('/adminer', '\Aranyasen\LaravelAdminer\AdminerController@index');
Route::any('adminer', '\Aranyasen\LaravelAdminer\AdminerAutologinController@index');