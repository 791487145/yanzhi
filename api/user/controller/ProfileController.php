<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use cmf\controller\RestUserBaseController;
use FontLib\Table\Type\post;
use think\Db;
use think\Validate;

class ProfileController extends RestUserBaseController
{
    /**
     * 用户绑定手机号
     * @throws \think\Exception
     */
    public function bindingMobile()
    {
        $validate = new Validate([
            'mobile'    => 'require|unique:user,mobile',
            'code'      => 'require'
        ]);

        $validate->message([
            'mobile.require'    => '请输入您的手机号!',
            'mobile.unique'     => '手机号已被绑定！',
            'code.require'      => '请输入数字验证码!'
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

//        if (!preg_match('/(^(13\d|15[^4\D]|17[13678]|18\d)\d{8}|170[^346\D]\d{7})$/', $data['mobile'])) {
//            $this->error("请输入正确的手机格式!");
//        }

        $userId = $this->getUserId();
        $mobile = Db::name("user")->where('id', $userId)->value('mobile');

        if (!empty($mobile)) {
            $this->error("您已经绑定手机!");
        }

        $errMsg = cmf_check_verification_code($data['mobile'], $data['code']);
        if (!empty($errMsg)) {
            $this->error($errMsg);
        }

        Db::name("user")->where('id', $userId)->update(['mobile' => $data['mobile']]);

        $this->success("绑定成功!");
    }

    /**
     * 用户绑定微信
     */
    public function bindingWeixin()
    {

        $validate = new Validate([
            'openid'    => 'require|unique:user,wx_openid',
        ]);

        $validate->message([
            'openid.require'    => '微信信息获取失败!',
            'openid.unique'     => '微信已被绑定！',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $userId = $this->getUserId();
        $openid = Db::name("user")->where('id', $userId)->value('wx_openid');

        if (!empty($openid)) {
            $this->error("您已经绑定微信!");
        }
        Db::name("user")->where('id', $userId)->update(['wx_openid' => $data['openid']]);

        $this->success("绑定成功!");
    }

    /**
     * 用户绑定QQ
     */
    public function bindingQq()
    {

        $validate = new Validate([
            'openid'    => 'require|unique:user,qq_openid',
        ]);

        $validate->message([
            'openid.require'    => 'QQ信息获取失败!',
            'openid.unique'     => 'QQ已被绑定！',
        ]);

        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $userId = $this->getUserId();
        $openid = Db::name("user")->where('id', $userId)->value('qq_openid');

        if (!empty($openid)) {
            $this->error("您已经绑定QQ!");
        }
        Db::name("user")->where('id', $userId)->update(['qq_openid' => $data['openid']]);

        $this->success("绑定成功!");
    }

    /**
     * 获取用户信息
     */
    public function getInfo() {
        $more = json_decode($this->user['more'],true);
        $reData = [
            'id'            => $this->userId,
            'uid'            => (string)$this->userId,
            'nickname'      => $this->user['user_nickname'],
            'sex'            => $this->user['sex'],
            'birthday'      => $this->user['birthday'],
            'signature'     => $this->user['signature'],
            'balance'       => $this->user['balance'],
            'mobile_bind'  => ($this->user['mobile'] == '' ? 0 : 1),
            'wx_bind'       => ($this->user['wx_openid'] == '' ? 0 : 1),
            'qq_bind'       => ($this->user['qq_openid'] == '' ? 0 : 1),
            'vip'           => ($this->user['vip'] > time() ? 1 : 0),
            'figure'        => empty($more['figure'])?'':$more['figure'],
            'job'           => empty($more['job'])?'':$more['job'],
            'topic'         => empty($more['topic'])?'':$more['topic'],
            'character'     => empty($more['character'])?'':$more['character'],
            'height'        => empty($more['height'])?'':$more['height'],
            'weight'        => empty($more['weight'])?'':$more['weight'],
            'domicile'      => empty($more['domicile'])?'':$more['domicile'],
            'real_user'     => $this->user['true_status'],
            'pay_set'       => ($this->user['pay_type'] == '' ? 0 : 1),

            'experience'    => '',//未知数据???
            'consumption'    => '',//未知数据???
            'votestotal'    => '',//未知数据???
            'province'    => '',//未知数据???
            'city'    => '',//未知数据???
            'isrecommend'    => '',//未知数据???
            'coin'    => $this->user['balance'],//未知数据???疑似平台币余额
            'votes'    => '',//未知数据???
            'userType'    => '',//未知数据???
            'sign'    => '',//未知数据???
        ];

        $cdnSettings    = cmf_get_option('cdn_settings');
        if ($this->user['avatar'] != '' &&  strpos($this->user['avatar'],'http://') === false){
            $reData['avatar'] = 'http://'.$cdnSettings['cdn_static_url'] .$this->user['avatar'];
        }
        else
        {
            $reData['avatar'] = $this->user['avatar'];
        }
        
        //获取主播信息
        $anchor = Db::name("live_anchor")->where('user_id',$this->userId)->find();
        if ($anchor) {
            $reData['is_anchor']    = $anchor['status'] == 1 ? 1 : 0;
            $reData['anchor_level'] = $anchor['level'];
            $reData['private_coin'] = $anchor['single_coin'];
            $reData['video_online'] = $anchor['video_state'];
        } else {
            $reData['is_anchor']    = 0;
            $reData['anchor_level'] = 0;
            $reData['private_coin'] = 0;
            $reData['video_online'] = 0;
        }
      
        //获取关注数
        $follow = Db::name('user_follow')->whereOr(['user_id|fans_id'=>$this->userId])->where('status',1)->select();
        $countFoll = $countFans = 0;
        foreach ($follow as $value) {
            if ($value['user_id'] == $this->userId) {
                $countFans++;
            }
            if ($value['fans_id'] == $this->userId) {
                $countFoll++;
            }
        }
        $reData['fans_num'] = $countFans;
        $reData['follow_num'] = $countFoll;

        //获取相册照片、视频数
        $countPhoto = Db::name('user_photo')->where('user_id',$this->userId)->count('id');
        $reData['photo_num'] = $countPhoto;
        $countVideo = Db::name('user_video')->where('user_id',$this->userId)->count('id');
        $reData['video_num'] = $countVideo;

        $this->success("获取成功",$reData);
    }

    /**
     * 修改用户信息
     */
    public function editInfo()
    {
        $validate = new Validate([
            'name'      => 'require',
            'value'     => 'require'
        ]);
        $validate->message([
            'name.require'   => '参数错误',
            'value.require'  => '请输入数字验证码!'
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $tableName = "user";
        $where = ['id'=>$this->userId];
        switch ($data['name'])
        {
            case "nickname":
                $field = "user_nickname";
                break;
            case "birthday":
            case "signature":
                $field = $data['name'];
                break;
            case "figure":
            case "job":
            case "topic":
            case "character":
            case "height":
            case "weight":
            case "domicile":
                $more = json_decode($this->user['more'],true);
                $more[$data['name']] = $data['value'];
                $field = "more";
                $data['value'] = json_encode($more);
                break;
            case "private_coin":
                $tableName = "live_anchor";
                $where = ['user_id'=>$this->userId];
                $field = "single_coin";
                if (intval($data['value']) != $data['value'])//私播价格应为整数
                {
                    $this->error("私播价格错误");
                }
                if ($data['value'] <= 0)//私播价格应大于0
                {
                    $this->error("私播价格不能小于0");
                }
                break;
            case "video_online":
                $tableName = "live_anchor";
                $where = ['user_id'=>$this->userId];
                $field = "video_state";
                $data['value'] = intval($data['value']);
                if ($data['value'] != 1)//视频聊天在线状态：0否，1是
                {
                    $data['value'] = 0;
                }
                break;
            default:
                $this->error("您的操作有误");
        }
        $db = Db::name($tableName)->where($where);

        $upData = $db->update([$field=>$data['value']]);
        if ($upData !== false) {
            $this->success('修改成功！');
        } else {
            $this->error('修改失败！');
        }
    }

    /**
     * 保存相册照片
     */
    public function savePhoto()
    {
        $url = $this->request->post('url');
        $vip = $this->request->post('vip', 0, 'intval');
        $money = $this->request->post('coin', 0, 'intval');

        //判断图片是否存在
        $check = Db::name("asset")->where('file_path', $url)->where('user_id', $this->userId)->find();

        if (!$check)
        {
            $this->error("图片不存在");
        }
        //判断相册图片是否存在
        $data = Db::name("user_photo")->where('url', $url)->where('user_id', $this->userId)->find();
        if (!$data)
        {
            $re = Db::name("user_photo")->insert([
                'user_id'       => $this->userId,
                'url'           => $url,
                'is_vip'        => $vip,
                'money'         => $money,
                'add_time'      => time()
            ]);
        }
        else
        {
            if ($data['is_vip'] == $vip && $data['money'] == $money)
            {
                $this->success("已保存成功");
            }
            else
            {
                $re = Db::name("user_photo")->where(['id'=> $data['id']])->update([
                    'is_vip'        => $vip,
                    'money'         => $money
                ]);
            }
        }
        if ($re)
        {
            $this->success("保存成功");
        }
        else
        {
            $this->error("保存失败");
        }
    }
    /**
     * 删除相册照片
     */
    public function delPhoto()
    {
        $url = trim($this->request->post('url'));
        //判断相册图片是否存在
        $data = Db::name("user_photo")->where('url', $url)->where('user_id', $this->userId)->find();

        if (!$data)
        {
            $this->error("相册图片不存在");
        }
        $re = Db::name("user_photo")->where('id',$data['id'])->delete();
        if ($re)
        {
            $res = true;
            if (strpos($url,'upload') !== false)
            {
                $file = '..'.$url;
            }
            else
            {
                $file = '../upload/' . $url;
            }

            if (file_exists($file)) {
                $res = unlink($file);
            }
            if ($res) {
                Db::name('asset')->where('file_path', $url)->delete();
            }
            $this->success('删除成功');
        }
        else
        {
            $this->error("删除失败");
        }
    }
    
    /**
     * 保存视频
     */
    public function saveVideo()
    {
        $url = $this->request->post('url');
        $vip = $this->request->post('vip', 0, 'intval');
        $money = $this->request->post('coin', 0, 'intval');

        //判断视频是否存在
        $check = Db::name("asset")->where('file_path', $url)->where('user_id', $this->userId)->find();
        if (!$check) {
            $this->error("视频不存在");
        }
        //判断相册图片是否存在
        $data = Db::name("user_video")->where('url', $url)->where('user_id', $this->userId)->find();
        if (!$data) {
            $re = Db::name("user_video")->insert([
                'user_id'       => $this->userId,
                'url'           => $url,
                'is_vip'        => $vip,
                'money'         => $money,
                'add_time'      => time()
            ]);
        } else {
            if ($data['is_vip'] == $vip && $data['money'] == $money) {
                $this->success("已保存成功");
            } else {
                $re = Db::name("user_video")->where(['id'=> $data['id']])->update([
                    'is_vip'        => $vip,
                    'money'         => $money
                ]);
            }
        }
        if ($re) {
            $this->success("保存成功");
        } else {
            $this->error("保存失败");
        }
    }
    /**
     * 删除视频
     */
    public function delVideo()
    {
        $url = trim($this->request->post('url'));
        //判断视频是否存在
        $data = Db::name("user_video")->where('url', $url)->where('user_id', $this->userId)->find();

        if (!$data) {
            $this->error("视频不存在");
        }
        $re = Db::name("user_video")->where('id',$data['id'])->delete();
        if ($re) {
            $res = true;
            if (strpos($url,'upload') !== false) {
                $file = '..'.$url;
            } else {
                $file = '../upload/' . $url;
            }

            if (file_exists($file)) {
                $res = unlink($file);
            }
            if ($res) {
                Db::name('asset')->where('file_path', $url)->delete();
            }
            $this->success('删除成功');
        } else {
            $this->error("删除失败");
        }
    }

    /**
     * 实名认证
     */
    public function trueName()
    {
        //判断是否上传身份证照片
        //$idUrl = $this->getIdUrl($this->userId);
        //$cardPic = ROOT_PATH . 'public' . DS . 'upload' . DS . 'card' . DS . $idUrl[0] . DS . $idUrl[1] . DS . $idUrl[2] . '.jpg';
        //if(!file_exists($cardPic)){
        //    $this->error("请上传手持身份证照片");
        //}
      
        $validate = new Validate([
            'name'      => 'require',
            'idnum'     => 'require'
        ]);
        $validate->message([
            'name.require'   => '请填写真实姓名',
            'value.require'  => '请填写有效身份证号码'
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if (!$this->validation_filter_id_card($data['idnum']))
        {
            $this->error("请正确填写身份证号码");
        }
        $updateData = [
            'truename'		=> $data['name'],
            'idnum'			=> $data['idnum'],
            'true_status'	=> 1
        ];

        $re = Db::name('user')->where(['id'=> $this->userId])->update($updateData);
        if (!$re)
        {
            $this->error("认证失败");
        }
        $this->success("认证成功");
    }

    /**
     * 提现信息设置
     */
    public function extractionInfo()
    {
        if ($this->user['truename'] == '')
        {
            $this->error("请先进行实名认证");
        }
        $validate = new Validate([
            'type'      => 'require',
            'account'     => 'require'
        ]);
        $validate->message([
            'type.require'   => '请填写提现方式',
            'account.require'  => '请填写提现账号信息'
        ]);
        $data = $this->request->param();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }
        if (!in_array($data['type'],['wxpay','alipay','unipay']))
        {
            $this->error("提现方式错误");
        }

        $sqlData = [
            'pay_type'      => $data['type'],
            'pay_account'   => $data['account']
        ];

        $re = Db::name('user')->where(['id'=> $this->userId])->update($sqlData);
        if (!$re)
        {
            $this->error("设置失败");
        }
        $this->success("设置成功");
    }

    /**
     * 提交反馈
     */
    public function feedback()
    {
        $info =$this->request->post('info');
        $re = Db::name('user_report')->insert([
            'user_id'   => 0,
            'user_type'   => 0,
            'reportor'   => $this->userId,
            'addtime'   => time(),
            'status'   => 0,
            'info'   => $info,
        ]);

        if ($re)
        {
            $this->success("提交成功");
        }
        else
        {
            $this->error("提交失败");
        }
    }

    /**
     * 搜索用户
     */
    public function select()
    {
        $page    = $this->request->post('page', 1, 'intval');
        $keyword = trim($this->request->post('keyword'));
        if ($keyword == "")
        {
            $this->error("请填写搜索关键词");
        }
        $pageSize = 10;
        $start = ( $page - 1 ) * $pageSize;

        $list = Db::name("user")
            ->field("id user_id,user_nickname nickname,avatar,signature,vip")
            ->where(['id|user_nickname|signature'=> ['like', "%$keyword%"]])
            ->where('user_status',1)
            ->where(['id'=>['<>',$this->userId]])
            ->order('id desc')
            ->limit($start,$pageSize)
            ->select();
        if (count($list) == 0)
        {
            $this->error("没有满足条件的用户");
        }
        $follow = Db::name("user_follow")->field("user_id")->where(['fans_id'=> $this->userId,'status'=>1])->select();
        $hash = [];
        foreach ($follow as $v)
        {
            $hash[] = $v['user_id'];
        }

        $reData = [];
        $cdnSettings    = cmf_get_option('cdn_settings');
        foreach ($list as $value)
        {
            if ($value['avatar'] != '' &&  strpos($value['avatar'],'http://') === false){
                $value['avatar'] = 'http://'.$cdnSettings['cdn_static_url'] .$value['avatar'];
            }
            if (in_array($value['user_id'],$hash))
            {
                $value['follow'] = 1;
            }
            else
            {
                $value['follow'] = 0;
            }
            if ($value['vip'] >= time())
            {
                $value['vip'] = 1;
            }
            else
            {
                $value['vip'] = 0;
            }
            $reData[] = $value;
        }
        $this->success("搜索成功",$reData);
    }


    /**
     * 判断身份证号码是否合法
     * @param $id_card
     * @return bool
     */
    private function validation_filter_id_card($id_card){
        if(strlen($id_card)==18){
            return $this->idcard_checksum18($id_card);
        }elseif((strlen($id_card)==15)){
            $id_card=$this->idcard_15to18($id_card);
            return $this->idcard_checksum18($id_card);
        }else{
            return false;
        }
    }
    /**
     * 计算身份证校验码，根据国家标准GB 11643-1999
     * @param $idcard_base
     * @return bool|mixed
     */
    private function idcard_verify_number($idcard_base){
        if(strlen($idcard_base)!=17){
            return false;
        }
        //加权因子
        $factor=array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
        //校验码对应值
        $verify_number_list=array('1','0','X','9','8','7','6','5','4','3','2');
        $checksum=0;
        for($i=0;$i<strlen($idcard_base);$i++){
            $checksum += substr($idcard_base,$i,1) * $factor[$i];
        }
        $mod=$checksum % 11;
        $verify_number=$verify_number_list[$mod];
        return $verify_number;
    }
    /**
     * 将15位身份证升级到18位
     * @param $idcard
     * @return bool|string
     */
    private function idcard_15to18($idcard){
        if(strlen($idcard)!=15){
            return false;
        }else{
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if(array_search(substr($idcard,12,3),array('996','997','998','999')) !== false){
                $idcard=substr($idcard,0,6).'18'.substr($idcard,6,9);
            }else{
                $idcard=substr($idcard,0,6).'19'.substr($idcard,6,9);
            }
        }
        $idcard=$this->idcard_verify_number($idcard);
        return $idcard;
    }
    /**
     * 18位身份证校验码有效性检查
     * @param $idcard
     * @return bool
     */
    private function idcard_checksum18($idcard){
        if(strlen($idcard)!=18){
            return false;
        }
        $idcard_base=substr($idcard,0,17);
        if($this->idcard_verify_number($idcard_base)!=strtoupper(substr($idcard,17,1))){
            return false;
        }else{
            return true;
        }
    }
}