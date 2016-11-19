<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', 'HomeController@index')->name('home');

// Account
Route::get('login/{provider}', 'Account\LoginController@login')->name('login.provider');
Route::get('login', function() {
    return redirect()->route('login.provider', ['provider' => 'evesso']);
})->name('login');
Route::post('logout', ['middleware' => 'auth', 'uses' => 'Account\LoginController@logout'])->name('logout');
Route::get('account', ['middleware' => 'auth', 'uses' => 'Account\AccountController@index'])->name('account.index');