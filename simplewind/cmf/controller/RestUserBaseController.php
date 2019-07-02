<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +---------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace cmf\controller;

use think\Db;

class RestUserBaseController extends RestBaseController
{

    public function _initialize()
    {
        if (empty($this->user)) {
            $this->error(['code' => -1, 'msg' => '登录已失效!']);
        }
      
        //记录用户操作日志
        $re = $_REQUEST;
        $uri = $re['s'];
        unset($re['s']);
        Db::name("api_log")->insert([
            'user_id'           => $this->userId,
            'logtime'           => time(),
            'uri'               => $uri,
            'param'             => json_encode($re)
        ]);
    }

    /**
     * 数字转为字符串
     * @param $num
     * @param $length
     * @return string
     */
    public function num2str($num, $length)
    {
        $num_str = (string)$num;
        $num_strlength = count($num_str);
        if ($length > $num_strlength) {
            $num_str = str_pad($num_str, $length, "0", STR_PAD_LEFT);
        }
        return $num_str;
    }

    /**
     * 根据ID获取用户图片存储路径
     * @param $id
     * @return array
     */
    public function getIdUrl($id)
    {
        $userIdStr = $this->num2str($id, 10);
        return [
            substr($userIdStr, 0, 3),
            substr($userIdStr, 3, 3),
            substr($userIdStr, 6)
        ];
    }

    /**
     * 身份证号码校验
     * @param $idcard
     * @return bool
     */
    public function checkIdCard($idcard) {
        // 只能是18位
        if (strlen($idcard) != 18) {
            return false;
        }
        // 取出本体码
        $idcard_base = substr($idcard, 0, 17);
        // 取出校验码
        $verify_code = substr($idcard, 17, 1);
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        // 校验码对应值
        $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        // 根据前17位计算校验码
        $total = 0;
        for ($i = 0; $i < 17; $i++) {
            $total += substr($idcard_base, $i, 1) * $factor[$i];
        }
        // 取模
        $mod = $total % 11;
        // 比较校验码
        if ($verify_code == $verify_code_list[$mod]) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断文件是否存在
     * @param $url
     * @return bool
     */
    public function check_file_exists($url)
    {
        var_dump($url);
        $curl = curl_init($url);
        // 不取回数据
        curl_setopt($curl, CURLOPT_NOBODY, true);
        // 发送请求
        $result = curl_exec($curl);
        $found = false;
        // 如果请求没有发送失败
        if ($result !== false) {
            // 再检查http响应码是否为200
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($statusCode == 200) {
                $found = true;
            }
        }
        curl_close($curl);

        return $found;
    }

}