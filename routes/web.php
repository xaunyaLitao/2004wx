<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::any('/info',function(){
     phpinfo();
});

Route::any('/weixin',"TestController@test1");  //接收事件推送
Route::any('/token',"TestController@getAccessToken");

Route::post('/test2',"TestController@test2");

