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
<CreateTime>1605003147</CreateTime>
<MsgType><![CDATA[image]]></MsgType>
<PicUrl><![CDATA[http://mmbiz.qpic.cn/mmbiz_jpg/TJhU9PFibVQdzWcNN1eGlqmibQeLbMWIOzgjOSpbRmhDE3BAKrqm0m4DFViab2lwhztFicOPeVQ1Luy3uicd7DiaDk7w/0]]></PicUrl>
<MsgId>22978119963270840</MsgId>
<MediaId><![CDATA[oUhvt_GeEkLFrp-kmvs5Gw-WYsD65ClH2OF9L-C9dIBWbcytHczNV1dmRnDKGefe]]></MediaId>
</xml>";
        $obj = simplexml_load_string($xml,"SimpleXMLElement",LIBXML_NOCDATA);
        dd($obj);
    }
}
