<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use GuzzleHttp\Client;
class WxController extends Controller
{
    //    创建自定义菜单
    public function createMenu(){
        $access_token=$this->get_access_token();
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token;

        $menu=[
            'button'=>[
               "name"=>2004,
                "sub_button"=> [
                [
                    'type'  =>'click',
                    'name'  =>'WX2004',
                    'key'   =>'k_wx_2004'

                ],
                [
                    'type'  =>'view',
                    'name'  =>'百度',
                    'url'   =>'https://www.baidu.com'
                ],
                [
                    'type'  =>'view',
                    'name'  =>'京东',
                    'url'   =>'https://www.jd.com'
                ],
                    [
                        'type'  =>'click',
                        'name'  =>'签到',
                        'key'   =>'Li'

                    ]
                ]
            ]
        ];

//        使用guzzel发起post请求
        $client= new Client();    // 实例化 客户端
        $response=$client->request('POST',$url,[
            'verify'=>false,
            'body' => json_encode($menu,JSON_UNESCAPED_UNICODE)
        ]);
        $data=$response->getBody();
        echo $data;

    }

    public function test1(){
        $xml="<xml><ToUserName><![CDATA[gh_f652006ce463]]></ToUserName>
<FromUserName><![CDATA[oc3dpwjYlt0VnkEymHUuCozdllIU]]></FromUserName>
<CreateTime>1605060305</CreateTime>
<MsgType><![CDATA[voice]]></MsgType>
<MediaId><![CDATA[GTpS6kFK6Z6CBFI2tBWeVxuKkZ4Vs2EmSdravqwcQzsSKczXjG_6wUHwVImObfI9]]></MediaId>
<Format><![CDATA[amr]]></Format>
<MsgId>22978942147288014</MsgId>
<Recognition><![CDATA[]]></Recognition>
</xml>";
        $obj = simplexml_load_string($xml,"SimpleXMLElement",LIBXML_NOCDATA);
        dd($obj);
    }


}
