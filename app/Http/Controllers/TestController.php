<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Log;
use Illuminate\Support\Facades\Redis;
class TestController extends Controller
{
    public function test1(){
        $echostr = request()->get("echostr", "");
        if ($this->checkSignature() && !empty($echostr)) {
            //第一次接入
            echo $echostr;
        }else{

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
            echo $_GET['echostr'];
        }else{
           echo "111";
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

            $tao=json_decode($response,true);
            $response=$tao['access_token'];
            Redis::set($key,$response);
            Redis::expire($key,3600);
        }
        echo $response;
    }



//    微信关注和取消关注
    public function wxEvent()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token ="Li";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){  //验证通过
            $xml_str=file_get_contents("php://input");
//            file_put_contents('wx_event.log',$xml_str);
            Log::info($xml_str);
            $pos=simplexml_load_string($xml_str);
            $Content="";
        }
        $info=$this->info($pos,$Content);
    }

    public function info($pos,$Content){
        $ToUserName=$pos->FromUserName;
        $FromUserName=$pos->ToUserName;
        $CreateTime=time();
        $MsgType="text";
        $xml="
        <xml>
  <ToUserName><![CDATA[%s]]></ToUserName>
  <FromUserName><![CDATA[%s]]></FromUserName>
  <CreateTime>%s</CreateTime>
  <MsgType><![CDATA[%s]]></MsgType>
  <Content><![CDATA[%s]]></Content>
</xml>";
        $info=sprintf($xml,$ToUserName,$FromUserName,$CreateTime,$MsgType,$Content);
        Log::info($info);
        echo $info;
    }
}
