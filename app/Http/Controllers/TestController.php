<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Log;
use Illuminate\Support\Facades\Redis;
use App\Model\UserModel;
use App\Model\ImageModel;
use GuzzleHttp\Client;
use App\Model\HistoryModel;
class TestController extends Controller
{
//    事件推送
    public function test1(){
        $echostr = request()->get("echostr", "");
        if ($this->checkSignature() && !empty($echostr)) {
            //第一次接入
            echo $echostr;
        }else{
            // $access_token=$this->get_access_token();  //跳方法  调 access_token  获取access_token
            $str=file_get_contents("php://input");
            $obj = simplexml_load_string($str,"SimpleXMLElement",LIBXML_NOCDATA);
            // $obj=json_decode($obj, true);
            // file_put_contents("aaa.txt",$obj);
            // echo "ok";
            file_put_contents('wx_event.log',$str,FILE_APPEND);
                switch($obj->MsgType){
                    //  关注
                    case "event":
                if($obj->Event=="subscribe"){
                    //用户扫码的 openID
                $openid=$obj->FromUserName;//获取发送方的 openid
                $access_token=$this->get_access_token();//获取token
                $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
                    //掉接口
                $user=file_get_contents($url);
                $user=json_decode($user,true);//跳方法 用get  方式调第三方类库
                    // $this->writeLog($fens);
                if(isset($user["errcode"])){
                $this->writeLog("获取用户信息失败");
                }else{

                //说明查找成功 //可以加入数据库
//                                if(!Redis::get($openid)){
//                                    Redis::set($openid,'111');
//                                    $content="您好!感谢您的关注";
//                                }else{
//                                 $content="感谢您的再次关注";
//                                }

                //查数据库有这个用户没有
                $user_id=UserModel::where('openid',$openid)->first();
                if($user_id){
                    $user_id->subscribe=1;
                    $user_id->save();
                    $content="谢谢再次回来！";
                }else{
                    $res=[
                        "subscribe" => $user['subscribe'],
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
                    UserModel::insert($res);
                    $content="谢谢关注@！";
                }
            }
        }
            // 取消关注
            if($obj->Event=="unsubscribe"){
                $user_id->subscribe=0;
                $user_id->save();
            }
            echo $this->xiaoxi($obj,$content);
            break;

                    case "text":
                        //  天气
                        $city=urlencode(str_replace("天气:","",$obj->Content));   //城市
                        $key="50ad65400349c7a71553ab6b23b92acb";  //key
                        $url="http://apis.juhe.cn/simpleWeather/query?city=".$city."&key=".$key;  //url地址
                        $shuju=file_get_contents($url);
                        $shuju=json_decode($shuju,true);
                        if($shuju["error_code"]==0){
                            $today=$shuju["result"]["realtime"];
                            $content="查询天气的城市:".$shuju["result"]["city"]."当天天气"."/n";  //查询的城市
                            $content.="天气详细情况：".$today["info"];
                            $content.="温度：".$today["temperature"]."\n";
                            $content.="湿度：".$today["humidity"]."\n";
                            $content.="风向：".$today["direct"]."\n";
                            $content.="风力：".$today["power"]."\n";
                            $content.="空气质量指数：".$today["aqi"]."\n";
                            //获取一个星期的
                            $future=$shuju["result"]["future"];
                            foreach($future as $k=>$v){
                                $content.="日期:".date("Y-m-d",strtotime($v["date"])).$v['temperature'].",";
                                $content.="天气:".$v['weather']."\n";
                            }

                        }else{
                            $content="你的查询天气失败，你的格式是天气:城市,这个城市不属于中国";
                        }

                        echo $this->xiaoxi($obj,$content);
                        break;


                    //    图片
                    case "image";
//                        file_put_contents('image.log',$str);
                      $data=[
                          'tousername'=>$obj->ToUserName,
                          'openid'=>$obj->FromUserName,
                          'createtime'=>$obj->CreateTime,
                          'msgtype'=>$obj->MsgType,
                          'pricurl'=>$obj->PicUrl,
                          'msgid'=>$obj->MsgId,
                          'media_id'=>$obj->MediaId
                      ];
                      $token=$this->get_access_token();
                        $media_id=($data['media_id']);
                        $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$token."&media_id=".$media_id;
                        $img = file_get_contents($url);
                        $res=file_put_contents("cat.jpg",$img);
                        var_dump($res);



                          HistoryModel::insert($data);
                        break;

                    //   语音
                    case "voice";
//                        file_put_contents("2004.txt",$str);
                        $data=[
                            'tousername'=>$obj->ToUserName,
                            'openid'=>$obj->FromUserName,
                            'createtime'=>$obj->CreateTime,
                            'msgtype'=>$obj->MsgType,
                            'msgid'=>$obj->MsgId,
                            'media_id'=>$obj->MediaId
                        ];
                        HistoryModel::insert($data);
                        break;

                    //  视频
                    case "video";
                        $data=[
                            'tousername'=>$obj->ToUserName,
                            'openid'=>$obj->FromUserName,
                            'createtime'=>$obj->CreateTime,
                            'msgtype'=>$obj->MsgType,
                            'thumbmediaId'=>$obj->thumbmediaId,
                            'msgid'=>$obj->MsgId,
                            'media_id'=>$obj->MediaId
                        ];
                        HistoryModel::insert($data);

                        break;
                      }


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




    private function writeLog($data){
        if(is_object($data) || is_array($data)){   //不管是数据和对象都转json 格式
            $data=json_encode($data);
        }
        file_put_contents('2004.txt',$data);die;
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

            //        使用guzzl发起get请求
            $client= new Client();    // 实例化 客户端

            $response=$client->request('GET',$url,['verify'=>false]);  // 发起请求并接受响应
            $json_str = $response->getBody();  //服务器的响应数据


            $tao=json_decode($json_str,true);
            $response=$tao['access_token'];
            Redis::set($key,$response);
            Redis::expire($key,3600);
        }
        return $response;
    }




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



// 测试2
    public function test2(){
        echo '<pre>';print_r($_POST);echo '</pre>';

        $data=file_get_contents("php://input");
        echo $data;
    }


    public function guzzle1(){
        $appid="wxc8e73af28fb246ce";
        $secret="e3b11750e1de175e6f94cde4ebdfed72";
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appid."&secret=".$secret;
//        使用guzzl发起get请求
        $client= new Client();    // 实例化 客户端

        $response=$client->request('GET',$url,['verify'=>false]);  // 发起请求并接受响应
       $json_str = $response->getBody();  //服务器的响应数据
        echo $json_str;
    }

//    上传素材
    public function guzzle2(){
        $access_token=$this->getAccessToken();
        $type="image";
        $url="https://api.weixin.qq.com/cgi-bin/media/upload?access_token=".$access_token."&type=".$type;
//        使用guzzle发送get请求
        $client= new Client();   //实例化 客户端
        $response=$client->request("POST",$url,[
            'verify'=>false,
            'multipart'=> [
                [
                    'name'=>'media',
                    'contents'=>fopen('8_03.jpg','r')
                ],
            ]
        ]);
        $data=$response->getBody();
        echo $data;
    }




//    文本
//private function text($toUser,$fromUser,$content){
//    $template="<xml>
//  <ToUserName><![CDATA[%s]]></ToUserName>
//  <FromUserName><![CDATA[%s]]></FromUserName>
//  <CreateTime>%s</CreateTime>
//  <MsgType><![CDATA[%s]]></MsgType>
//  <Content><![CDATA[%s]]></Content>
//</xml>";
//    $info=sprintf($template,$toUser,$fromUser,time(),'text',$content);
//    return $info;
//}


//    图片
//private function image($toUser,$fromUser,$content)
//{
//    $template="<xml>
//  <ToUserName><![CDATA[%s]]></ToUserName>
//  <FromUserName><![CDATA[%s]]></FromUserName>
//  <CreateTime>%s</CreateTime>
//  <MsgType><![CDATA[%s]]></MsgType>
//  <Image>
//  <MediaId><![CDATA[%s]]></MediaId>
//  </Image>
//</xml>";
//    $info=sprintf($template,$toUser,$fromUser,time(),'image',$content);
//    return $info;
//}


//  语音
//private function voice($toUser,$fromUser,$content){
//    $template="<xml>
//  <ToUserName><![CDATA[%s]]></ToUserName>
//  <FromUserName><![CDATA[%s]]></FromUserName>
//  <CreateTime>%s</CreateTime>
//  <MsgType><![CDATA[%s]]></MsgType>
//  <Voice>
//  <MediaId><![CDATA[%s]]></MediaId>
//  </Voice>
//</xml>";
//    $info=sprintf($template,$toUser,$fromUser,time(),'voice',$content);
//    return $info;
//}



//    视频

//private function video($toUser,$fromUser,$content,$title,$description){
//    $template = "<xml>
//                              <ToUserName><![CDATA[%s]]></ToUserName>
//                              <FromUserName><![CDATA[%s]]></FromUserName>
//                              <CreateTime><![CDATA[%s]]></CreateTime>
//                              <MsgType><![CDATA[%s]]></MsgType>
//                              <Video>
//                                <MediaId><![CDATA[%s]]></MediaId>
//                                <Title><![CDATA[%s]]></Title>
//                                <Description><![CDATA[%s]]></Description>
//                              </Video>
//                            </xml>";
//    $info=sprintf($template,$toUser,$fromUser,time(),'video',$content,$title,$description);
//    return $info;
//}

//  音乐
//    private function music($toUser,$fromUser,$title,$description,$musicurl,$content)
//    {
//        $template = "<xml>
//                  <ToUserName><![CDATA[%s]]></ToUserName>
//                  <FromUserName><![CDATA[%s]]></FromUserName>
//                  <CreateTime><![CDATA[%s]]></CreateTime>
//                  <MsgType><![CDATA[%s]]></MsgType>
//                  <Music>
//                    <Title><![CDATA[%s]]></Title>
//                    <Description><![CDATA[%s]]></Description>
//                    <MusicUrl><![CDATA[%s]]></MusicUrl>
//                    <HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
//                    <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
//                  </Music>
//                </xml>";
//        $info = sprintf($template, $toUser, $fromUser, time(), 'music', $title,$description,$musicurl,$musicurl,$content);
//        return $info;
//    }

// 图文
//    private function image_text($toUser,$fromUser,$title,$description,$content,$url){
//        $template = "<xml>
//                              <ToUserName><![CDATA[%s]]></ToUserName>
//                              <FromUserName><![CDATA[%s]]></FromUserName>
//                              <CreateTime>%s</CreateTime>
//                              <MsgType><![CDATA[%s]]></MsgType>
//                              <ArticleCount><![CDATA[%s]]></ArticleCount>
//                              <Articles>
//                                <item>
//                                  <Title><![CDATA[%s]]></Title>
//                                  <Description><![CDATA[%s]]></Description>
//                                  <PicUrl><![CDATA[%s]]></PicUrl>
//                                  <Url><![CDATA[%s]]></Url>
//                                </item>
//                              </Articles>
//                            </xml>";
//        $info = sprintf($template, $toUser, $fromUser, time(), 'news', 1 ,$title,$description,$content,$url);
//        return $info;
//    }


}
