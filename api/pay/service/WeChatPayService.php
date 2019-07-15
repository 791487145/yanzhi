<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------
namespace api\pay\service;

use api\common\model\CommonModel;

use api\model\PayPaymentModel;
use think\Db;
use think\Exception;
use think\Request;
use think\Log;

class WeChatPayService
{

    protected $appid = 'wx1c8352db69481563';
    protected $mch_id = '1518356971';
    protected $key = 'Cu4zloMTzwkOSXrPWFid6Ts5wT6BMqJy';
    protected $body;
    protected $nonce_str;
    protected $notify_url;
    protected $spbill_create_ip;
    protected $trade_type = 'MWEB';

    protected $scene_info_array = array(
        '{"h5_info": {"type":"IOS","app_name": "趣约视频","bundle_id": "com.shenlanhuanbao.goodlife"}}', //ios
        '{"h5_info": {"type":"Android","app_name": "趣约视频","package_name": "com.shenlanhuanbao.goodlife"}}', //安卓
        '{"h5_info": {"type":"Wap","wap_url": "https://pay.qq.com","wap_name": "腾讯充值"}} ' //wap
    );


    public function __construct()
    {
        $request = Request::instance();
        $host = $request->host();
        $this->notify_url = "http://".$host."/api/pay/public/wechat_notify";
        //$this->spbill_create_ip = $this->get_server_ip();
    }

    /**
     * @param Request $request
     * @param PayPaymentModel $paymentModel 充值记录
     * @param $pay_orderid 订单编号
     * @param $param 订单详情
     * @param $good_title 商品类型：vip，趣币
     * @param $body 支付页标题
     * @param int $mobile_type 手机类型
     * @param null $pay_callbackurl  回跳页面url
     * @return \SimpleXMLElement
     */
    public function pay(Request $request,PayPaymentModel $paymentModel,$pay_orderid,$param,$good_title,$body,$mobile_type = 2,$pay_callbackurl = null)
    {
        $this->body($paymentModel,$body,$good_title);
        $unified = array(
            'appid' => $this->appid,
            'attach' => '2342',
            'body' => $this->body,
            'mch_id' => $this->mch_id,
            'nonce_str' => self::nonce_str(),
            'notify_url' => $this->notify_url,
            'out_trade_no' => $pay_orderid,
            'spbill_create_ip' => self::get_server_ip()?:'127.0.0.1',//终端的ip
            'total_fee' => bcmul($param['money'],100,2),       //单位 分
            'trade_type' => 'MWEB',//交易类型 默认
            'scene_info' => $this->scene_info($mobile_type)
        );
        //halt($unified);
        $unified['sign'] = self::getSign($unified, $this->key);//签名
        Log::alert('$unified_'.print_r($unified,true));
        $responseXml = HttpService::postRequest('https://api.mch.weixin.qq.com/pay/unifiedorder', self::arrayToXml($unified));

        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($unifiedOrder === false) {
            die('parse xml error');
        }
        if ($unifiedOrder->return_code != 'SUCCESS') {
            die($unifiedOrder->return_msg);
        }
        if ($unifiedOrder->result_code != 'SUCCESS') {
            die($unifiedOrder->err_code);
        }

        Log::alert('$unifiedOrder_'.print_r($unifiedOrder,true));
        $res = (array)$unifiedOrder->mweb_url;
        return array('mweb_url' => $res[0]);
    }

    public function notify()
    {
        $postStr = file_get_contents('php://input');
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($postObj === false) die('parse xml error');
        if ($postObj->return_code != 'SUCCESS') die($postObj->return_msg);
        if ($postObj->result_code != 'SUCCESS') die($postObj->err_code);
        $arr = (array)$postObj;
        unset($arr['sign']);

        echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        return $arr;

    }


    public function body($paymentModel,$body,$mobile_type)
    {
        return $this->body = $body.'-'.$paymentModel->type_name[$mobile_type];
    }

    //随机32位字符串
    public static function nonce_str(){
        $result = '';
        $str = 'QWERTYUIOPASDFGHJKLZXVBNMqwertyuioplkjhgfdsamnbvcxz';
        for ($i=0;$i<32;$i++){
            $result .= $str[rand(0,48)];
        }
        return $result;
    }

    public function get_server_ip() {
        $ip = '';
        if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }elseif(isset($_SERVER['HTTP_CLIENT_IP'])){
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $ip_arr = explode(',', $ip);
        return $ip_arr[0];
    }

    public function scene_info($mobile_type)
    {
        return $this->scene_info_array[$mobile_type];
    }

    /**
     * 获取签名
     */
    public function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }


    //数组转XML
    public static function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}
