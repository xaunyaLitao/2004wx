<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Log;
use Illuminate\Support\Facades\Redis;
use App\Model\UserModel;
class TestController extends Controller
{
    public function test1()
    {
        $echostr = request()->get("echostr", "");
        if ($this->checkSignature() && !empty($echostr)) {
            //第一次接入
            echo $echostr;
        } else {
            // $access_token=$this->get_access_token();  //跳方法  调 access_token  获取access_token
            $str = file_get_contents("php://input");
            $obj = simplexml_load_string($str, "SimpleXMLElement", LIBXML_NOCDATA);
            // $obj=json_decode($obj, true);
            // file_put_contents("aaa.txt",$obj);
            // echo "ok";

            if ($obj->Event == "subscribe") {
                //用户扫码的 openID
                $openid = $obj->FromUserName;//获取发送方的 openid
                $access_token = $this->get_access_token();//获取token,
                $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" . $access_token . "&openid=" . $openid . "&lang=zh_CN";
                //掉接口
                $user = json_decode($this->http_get($url), true);//跳方法 用get  方式调第三方类库
                // $this->writeLog($fens);
                if (isset($user["errcode"])) {
                    $this->writeLog("获取用户信息失败");
                } else {
                    //说明查找成功 //可以加入数据库
                    $res = UserModel::where("openid", $openid)->first();//查看用户表中是否有该用户,查看用户是否关注过
                    if ($res) {//说明该用户关注过
                        $openid = $obj->FromUserName;
                        $res = UserModel::where("openid", $openid)->first();
                        $res->subscribe = 1;
                        $res->save();
                        $content = "欢迎您再次关注！";
                    } else {
                        $data = [
                            "subscribe" =>1,
                            "openid" => $user["openid"],
                            "nickname" => $user["nickname"],
                            "sex" => $user["sex"],
                            "city" => $user["city"],
                            "country" => $user["country"],
                            "province" => $user["province"],
                            "language" => $user["language"],
                            "headimgurl" => $user["headimgurl"],
                            "subscribe_time" => $user["subscribe_time"],
                            "subscribe_scene" => $user["subscribe_scene"]
                        ];
                        UserModel::create($data);
                        $content = "欢迎关注";
                    }
                }
            }
            // 取消关注
            if ($obj->Event == "unsubscribe") {
                $openid = $obj->FromUserName;
                $res = UserModel::where("openid", $openid)->first();
                $res->subscribe = 0;
                $res->save();
            }
            echo $this->xiaoxi($obj,$content);
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



////    微信关注和取消关注
//    public function wxEvent()
//    {
//        $signature = $_GET["signature"];
//        $timestamp = $_GET["timestamp"];
//        $nonce = $_GET["nonce"];
//
//        $token ="Li";
//        $tmpArr = array($token, $timestamp, $nonce);
//        sort($tmpArr, SORT_STRING);
//        $tmpStr = implode( $tmpArr );
//        $tmpStr = sha1( $tmpStr );
//
//        if( $tmpStr == $signature ){  //验证通过
//            $xml_str=file_get_contents("php://input");
////            file_put_contents('wx_event.log',$xml_str);
//            Log::info($xml_str);
//            $pos=simplexml_load_string($xml_str);
//            $Content="感谢关注";
//        }
//        $info=$this->info($pos,$Content);
//    }


//    public function info($pos,$Content){
//        $ToUserName=$pos->FromUserName;
//        $FromUserName=$pos->ToUserName;
//        $CreateTime=time();
//        $MsgType="text";
//        $xml="
//        <xml>
//  <ToUserName><![CDATA[%s]]></ToUserName>
//  <FromUserName><![CDATA[%s]]></FromUserName>
//  <CreateTime>%s</CreateTime>
//  <MsgType><![CDATA[%s]]></MsgType>
//  <Content><![CDATA[%s]]></Content>
//</xml>";
//        $info=sprintf($xml,$ToUserName,$FromUserName,$CreateTime,$MsgType,$Content);
//        Log::info($info);
//        echo $info;
//    }






    function xiaoxi($obj,$content){ //返回消息
        //我们可以恢复一个文本|图片|视图|音乐|图文列如文本
        //接收方账号
        $toUserName=$obj->FromUserName;
        //开发者微信号
        $fromUserName=$obj->ToUserName;
        //时间戳
        $time=time();
        //返回类型
        $msgType="text";

        $xml = "<xml>
                      <ToUserName><![CDATA[%s]]></ToUserName>
                      <FromUserName><![CDATA[%s]]></FromUserName>
                      <CreateTime>%s</CreateTime>
                      <MsgType><![CDATA[%s]]></MsgType>
                      <Content><![CDATA[%s]]></Content>
                    </xml>";
        //替换掉上面的参数用 sprintf
        echo sprintf($xml,$toUserName,$fromUserName,$time,$msgType,$content);

    }
}
