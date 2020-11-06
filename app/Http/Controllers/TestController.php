<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Redis;
class TestController extends Controller
{
    public function test1(){
        $echostr = request()->get("echostr", "");
        if ($this->checkSignature() && !empty($echostr)) {
            //第一次接入
            echo $echostr;
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token ="Li";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }


//   获取access_token
    public function getAccessToken(){
        $key="1234";
        $response=Redis::get($key);
        if(!$response){
            echo "没有缓存";
            $grant_type="client_credential";
            $appid="wxc8e73af28fb246ce";
            $secret="e3b11750e1de175e6f94cde4ebdfed72";
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=".$grant_type."&appid=".$appid."&secret=".$secret;

            $arrContextOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ];
            $response = file_get_contents($url, false, stream_context_create($arrContextOptions));

            $response=json_decode($response,true);
            $response=$response['access_token'];
            Redis::set($key,$response);
            Redis::expire($key,3600);
        }
        echo $response;
    }

}
