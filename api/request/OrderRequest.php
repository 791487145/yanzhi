<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------
namespace api\request;

use api\common\model\CommonModel;

use think\Db;
use think\Exception;
use think\Request;
use think\Validate;

class OrderRequest extends Validate
{
    protected $rule =   [
        'type'  => 'require|number',
        'mode'   => 'require|integer|elt:2',
        'money' => 'require|integer|egt:1',
    ];

    protected $message  =   [
        'type.require' => '请选择充值类型',
        'type.number'     => '充值类型参数类型错误',
        'mode.require'   => '请选择支付方式',
        'mode.integer'  => '支付方式参数错误',
        'mode.elt'  => '支付方式参数值错误',
        'money.require'        => '请填写充值金额',
        'money.integer'        => '充值金额参数类型错误',
        'money.egt'        => '充值金额参数大小错误',
    ];

    protected $scene = [
        'edit'  =>  ['type','mode','money'],
    ];
}
