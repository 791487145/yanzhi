<?php
// +----------------------------------------------------------------------
// | 临时接口文件
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\test\controller;

use api\model\PayPaymentModel;
use api\pay\controller\PublicController;
use api\pay\service\WeChatPayService;
use api\test\model\UserModel;
use api\user\service\QiNiuService;
use cmf\controller\RestBaseController;
use think\Db;
use think\Log;
use think\Request;
use Qiniu\Auth;
use Qiniu\Cdn\CdnManager;

class IndexController extends RestBaseController
{  
    public function index(Request $request)
    {
        if($request->isPost()){
            halt($_POST['tag_id']);
        }

        return view('index');
    }

    public function qiniu()
    {
        $qiniu = new QiNiuService();
        $qiniu->cdn_refresh('/upload/head/000/000/7346.jpg',0);
        /*$accessKey = 'nhDhP4R-24sB2QZrBQ25smH1IpTu9fJy7yodm9l-';
        $secretKey = 'i5PnhBL9UAeybhnGTK1ehRrsWGQPjg8yyjXXqPL-';

        $auth = new Auth($accessKey, $secretKey);

        $urls = array(
            "http://img.miyueba.cn/upload/head/000/000/7346.jpg",
        );

        $cdnManager = new CdnManager($auth);

        list($refreshResult, $refreshErr) = $cdnManager->refreshUrls($urls);
        if ($refreshErr != null) {
            var_dump($refreshErr);
        } else {
            echo "refresh request sent\n";
            print_r($refreshResult);
        }*/
    }

    public function test(WeChatPayService $weChatPayService)
    {
        /*$res = $weChatPayService->notify();
        //判断订单状态
        $order = PayPaymentModel::where(['sn'=> $res['out_trade_no']])->find();
        Log::alert('$order_'.print_r($order,true));
        if (!$order){
            exit("fail");
        }
        if (($res['total_fee']/100) != $order['money']){
            exit("fail");
        }
        Log::alert('jindu_1');
        if ($order['status'] == 0)//未支付订单
        {
            Log::alert('jindu_2');
            $update = [
                'status'    => 1,
                'pay_time'  => time(),
                'pay_sn'    => $res['transaction_id'],
                'more'      => json_encode($res)
            ];
            PublicController::updateOrder($order,$update);
        }
        Log::alert('jindu_3');
        exit("success");*/
    }
}