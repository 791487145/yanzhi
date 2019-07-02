<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use cmf\controller\RestBaseController;
use think\Validate;
use think\View;

class VerificationCodeController extends RestBaseController
{
    /**
     * 发送手机验证码
     */
    public function send()
    {
        $validate = new Validate([
            'mobile' => 'require',
        ]);

        $validate->message([
            'mobile.require' => '请输入手机号码!',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $accountType = '';

        if (preg_match('/(^(13\d|15[^4\D]|17[13678]|18\d)\d{8}|170[^346\D]\d{7})$/', $data['mobile'])) {
            $accountType = 'mobile';
        } else {
            $this->error("请输入正确的手机号码!");
        }

        //TODO 限制 每个ip 的发送次数
        $code = cmf_get_verification_code($data['mobile']);
        if (empty($code)) {
            $this->error("验证码发送过多,请明天再试!");
        }


        $param  = ['mobile' => $data['mobile'], 'code' => $code];
        $result = hook_one("send_mobile_verification_code", $param);

        if ($result !== false && !empty($result['error'])) {
            $this->error($result['message']);
        }

        if ($result === false) {
            $this->error('未安装验证码发送插件,请联系管理员!');
        }

        cmf_verification_code_log($data['mobile'], $code);

        if (!empty($result['message'])) {
            $this->success($result['message']);
        } else {
            $this->success('验证码已经发送成功!');
        }
    }

}
