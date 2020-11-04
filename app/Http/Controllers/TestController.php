<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;
class TestController extends Controller
{
    public function test1(){
    //    $res=DB::table('test')->get();
    //    dd($res);

    $key="2004wx";
    Redis::set($key,time());
    echo Redis::get($key);
    }
}
