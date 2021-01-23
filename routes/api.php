<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => 'auth'], function () {
    Route::post('login', 'AuthController@login');
    Route::post('signup', 'AuthController@signup');
    Route::post('verify', 'AuthController@verify');
    Route::post('session', 'AuthController@session');
    
    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('profile', 'MsmeController@show');
        Route::post('create', 'MsmeController@store');
        Route::get('image', 'MsmeController@image');
        Route::post('decode', 'MsmeController@decode');
        Route::post('createProduct', 'ProductController@store');
        Route::get('product', 'ProductController@show');
        Route::get('logout', 'AuthController@logout');
    });
});