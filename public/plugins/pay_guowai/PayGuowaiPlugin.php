<?php
// +----------------------------------------------------------------------
// | 虎虎网络科技
// +----------------------------------------------------------------------
// | Author: Lixue
// +----------------------------------------------------------------------
namespace plugins\pay_guowai;//Demo插件英文名，改成你的插件英文就行了
use cmf\lib\Plugin;

/**
 * MobileCodeDemoPlugin
 */
class PayGuowaiPlugin extends Plugin
{

    public $info = [
        'name'        => 'PayGuowai',
        'title'       => '国外支付插件',
        'description' => '国外支付插件',
        'status'      => 1,
        'author'      => 'Lixue',
        'version'     => '1.0'
    ];

    public $has_admin = 0;//插件是否有后台管理界面

    public function install() //安装方法必须实现
    {
        return true;//安装成功返回true，失败false
    }

    public function uninstall() //卸载方法必须实现
    {
        return true;//卸载成功返回true，失败false
    }

    private $ratio = 100;

    //实现的pay_guowai钩子方法
    public function getPayUrl($param)
    {
        $config        = $this->getConfig();

        $total_fee = $param['money'] * $this->ratio;//单位：分

        switch ($param['pay_mode']){
            case "1":
                $type = "ALI";
                break;
            case "2":
                $type = "WX";
                if ($param['device'] == "android"){//安卓微信支付，使用SDK，否则使用H5
                    $type .= "_APP";
                }
                break;
            default:
                return false;
        }

        $vars ="body=".$config['name']
            ."&mch_id=".$config['mch_id']
            ."&notify_url=".$config['notify']
            ."&out_trade_no=".$param['sn'];
        if($type == 'WX_APP'){
            $vars .="&package=".$config['package_name'];
        }
        $vars .="&redirect_url=".$config['redirect']
            ."&spbill_create_ip=".$param['ip']
            ."&total_fee=".$total_fee
            ."&trade_type=".$type;
        $sign = md5($vars . "&key=".$config['key']);

        $vars .= "&sign=" . $sign;

        $payUrl = $config['pay_url'] . "?" . $vars;

        return $payUrl;
    }

    // 支付验证
    public function checkPaySign($param)
    {
        $config        = $this->getConfig();
        if ($param['mch_id'] != $config['mch_id']) {
            return [
                'succ'     => false,
                'message'  => '商户信息错误'
            ];
        }

        $vars ="mch_id=".$config['mch_id']
            ."&out_trade_no=".$param['out_trade_no']
            ."&payment_time=".$param['payment_time']
            ."&total_fee=".$param['total_fee']
            ."&trade_no=".$param['trade_no']
            ."&key=".$config['key'];
        if (md5($vars) != $param['sign']) {
            return [
                'succ'     => false,
                'message'  => '加密验证失败'
            ];
        }

        return [
            'succ'  => true,
            'money' => ( $param['total_fee'] / $this->ratio ),
            'url'   => $config['success']
        ];
    }
}