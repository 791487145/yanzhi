<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2018 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Validate;
use think\Db;

/**
 * Class VirtualController
 * @package app\user\controller
 */
class VirtualController extends AdminBaseController
{
    //推荐类型
    private $recomType = ['1'=>'附近人','2'=>'寻找结婚对象','3'=>'找个人看电影','4'=>'约个饭，见个面','5'=>'优质女生'];
    /**
     * 虚拟用户列表
     */
    public function index()
    {
        $where   = ['user_type'=>2,'is_virtual'=>1];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['u.id'] = intval($request['uid']);
        }
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];

            $keywordComplex['u.user_login|u.user_nickname|u.mobile']    = ['like', "%$keyword%"];
        }

        $list = Db::name('user')
            ->field("u.id,u.user_nickname,u.avatar,u.create_time,u.user_status,a.video_url,a.recommend,u.recom_type,a.connect_per,a.video_recom")
            ->alias('u')
            ->join('yz_live_anchor a','u.id=a.user_id')
            ->whereOr($keywordComplex)
            ->where($where)
            ->order("u.create_time DESC")
            ->paginate(10);
//        获取最后一次执行的sql
//        $sql = Db::name('user')->getLastSql();
//        var_dump($sql);

        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('recomType', $this->recomType);
        // 渲染模板输出
        return $this->fetch();
    }
    /**
     * 添加虚拟用户页面
     */
    public function add()
    {
        $this->assign('recomType', $this->recomType);

        $p = Db::name('citys')->group('province')->order('gd')->column('province');
        $this->assign('province', $p);

        // 渲染模板输出
        return $this->fetch();
    }
    /**
     * 添加虚拟用户提交
     */
    public function addPost()
    {
        $validate = new Validate([
            'u.user_nickname' => 'require',
            'u.birthday' => 'require',
            'u.signature' => 'require',
            'u.avatar' => 'require',
            'm.figure' => 'require',
            'm.job' => 'require',
            'm.topic' => 'require',
            'm.character' => 'require',
            'c.level' => 'require',
            'c.single_coin' => 'require',
        ]);

        $validate->message([
            'u.user_nickname.require' => '请填写主播昵称',
            'u.birthday.require' => '请填写主播生日',
            'u.signature.require' => '请填写个性签名',
            'u.avatar.require' => '请上传头像',
            'm.figure.require' => '请填写身材',
            'm.job.require' => '请填写职业',
            'm.topic.require' => '请填写擅长话题',
            'm.character.require' => '请填写性格',
            'c.level.require' => '请填写主播等级',
            'c.single_coin.require' => '请填写私播单价',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $now = time();
        //用户表数据
        $user = $data['u'];
        if ($user["province"] == '0' || $user["city"] == '0')
        {
            $this->error("请选择所在城市");
        }
        $user['birthday']       = strtotime($user['birthday']);
        $user['is_virtual']     = 1;//虚拟用户标识
        $user['user_type']      = 2;
        $user['create_time']    = $now;
        $user['user_status']    = 1;
        $user['more']           = json_encode($data['m']);
        $user['recom_type']     = empty($data['r'])?'':json_encode($data['r']);
        if (strpos($user['avatar'],'http://') === false)
        {
            $user['avatar']     = '/upload/'.$user['avatar'];
        }
        //用户数据入库
        $userId = Db::name("user")->insertGetId($user);

        if ($userId == 0)
        {
            $this->error("用户添加失败");
        }
        $reMsg = "用户添加成功，";
        //===========将用户ID写入极光IM start
        $imData = [
            'name'=>'QY_'.$userId,
            'pass'=>'QY_'.$userId.'HUHU'
        ];
        hook_one("user_jpush_reg", $imData);
        //===========将用户ID写入极光IM end

        //主播表数据
        $anchor = $data['c'];
        $anchor['user_id']      = $userId;
        $anchor['photo']        = $user['avatar'];
        $anchor['add_time']     = $now;
        $anchor['audit_time']   = $now;
        $anchor['status']       = 1;
        //主播数据入库
        $reAnchor = Db::name("live_anchor")->insert($anchor);
        if ($reAnchor)
        {
            $this->success($reMsg."主播添加成功");
        }
        $this->success($reMsg."主播添加失败");
    }
    /**
     * 编辑虚拟用户页面
     */
    public function edit()
    {
        $id = input('param.id', 0, 'intval');
        $user = Db::name('user')->where('id',$id)->find();
        $this->assign('user', $user);
        $more = json_decode($user['more'],true);
        if (empty($more['vip_type']))$more['vip_type'] = 2;
        $this->assign('more', $more);
        $recom = json_decode($user['recom_type'],true);
        $this->assign('recom', $recom);

        $photo = Db::name("user_photo")->where('user_id',$id)->select();
        foreach ($photo as $k => $v)
        {
            if (substr($v['url'],0,8) == '/upload/')
            {
                $v['tmp_url'] = substr($v['url'],8);
            }
            else
            {
                $v['tmp_url'] = $v['url'];
            }
            $photo[$k] = $v;
        }
        $this->assign('photo', $photo);

        $anchor = Db::name("live_anchor")->where('user_id',$id)->find();
        $this->assign('anchor', $anchor);

        $this->assign('recomType', $this->recomType);

        $p = Db::name('citys')->group('province')->order('gd')->column('province');
        $this->assign('province', $p);

        $c = Db::name('citys')->where('province',$user['province'])->group('city')->order('gd')->column('city');
        $this->assign('city', $c);

        return $this->fetch();
    }
    /**
     * 编辑虚拟用户提交
     * @throws \think\Exception
     */
    public function editPost()
    {
        $validate = new Validate([
            'u.user_nickname' => 'require',
            'u.birthday' => 'require',
            'u.signature' => 'require',
            'u.avatar' => 'require',
            'm.figure' => 'require',
            'm.job' => 'require',
            'm.topic' => 'require',
            'm.character' => 'require',
            'c.level' => 'require',
            'c.single_coin' => 'require',
        ]);

        $validate->message([
            'u.user_nickname.require' => '请填写主播昵称',
            'u.birthday.require' => '请填写主播生日',
            'u.signature.require' => '请填写个性签名',
            'u.avatar.require' => '请上传头像',
            'm.figure.require' => '请填写身材',
            'm.job.require' => '请填写职业',
            'm.topic.require' => '请填写擅长话题',
            'm.character.require' => '请填写性格',
            'c.level.require' => '请填写主播等级',
            'c.single_coin.require' => '请填写私播单价',
        ]);

        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError(),'/user/virtual/edit/id/'.$data['u']['id']);
        }

        $reMsg = "";

        //用户表数据
        $user = $data['u'];
        if ($user['province'] == '0' || $user['city'] == '0')
        {
            $this->error("请选择所在城市",'/user/virtual/edit/id/'.$user['id']);
        }
        $user['birthday']       = strtotime($user['birthday']);
        $user['more']           = json_encode($data['m']);
        $user['recom_type']     = empty($data['r'])?'':json_encode($data['r']);
        $userData = Db::name('user')->where('id',$user['id'])->find();
        $userEdit = [];
        if ($user['user_nickname'] != $userData['user_nickname'])$userEdit['user_nickname'] = $user['user_nickname'];
        if ($user['sex'] != $userData['sex'])$userEdit['sex'] = $user['sex'];
        if ($user['birthday'] != $userData['birthday'])$userEdit['birthday'] = $user['birthday'];
        if ($user['signature'] != $userData['signature'])$userEdit['signature'] = $user['signature'];
        if ($user['more'] != $userData['more'])$userEdit['more'] = $user['more'];
        if ($user['recom_type'] != $userData['recom_type'])$userEdit['recom_type'] = $user['recom_type'];
        if ($user['province'] != $userData['province'])$userEdit['province'] = $user['province'];
        if ($user['city'] != $userData['city'])$userEdit['city'] = $user['city'];
        if ($user['avatar'] != $userData['avatar']){
            $userEdit['avatar'] = $user['avatar'];
            if (strpos($userEdit['avatar'],'http://') === false)
            {
                $userEdit['avatar']     = '/upload/'.$userEdit['avatar'];
            }
        }
        if (count($userEdit) > 0)//需要更新用户信息
        {
            $userEdit['id'] = $user['id'];
            $reUser = Db::name('user')->update($userEdit);
            if ($reUser)
            {
                $reMsg .= "用户信息更新成功";
            }
            else
            {
                $reMsg .= "用户信息更新失败";
            }
        } else{
            $reMsg .= "用户信息没有更新";
        }
        //主播表数据
        $anchor = $data['c'];
        //主播数据入库
        $reAnchor = Db::name("live_anchor")->where('user_id',$user['id'])->update($anchor);
        if ($reAnchor)
        {
            $this->success($reMsg.",主播信息更新成功");
        }
        $this->success($reMsg.",主播信息更新失败");
    }
    /**
     * 变更推荐状态
     * @throws \think\Exception
     */
    public function recommend()
    {
        $id         = input('id', 0, 'intval');
        $recommend  = input('recommend', 0, 'intval');
        $str = $recommend == 1 ? '推荐' : '取消推荐';

        $re = Db::name("live_anchor")->where('user_id',$id)->update(['recommend'=>$recommend,'recom_time'=>time()]);
        if ($re)
        {
            $this->success($str."成功");
        }else{
            $this->error($str."失败");
        }
    }
    /**
     * 设置直播在线状态
     */
    public function setOnline() {
        $id         = input('id', 0, 'intval');
        $online  = input('online', 0, 'intval');
        $str = $online == 1 ? '1V1上线' : '1V1下线';

        $re = Db::name("live_anchor")->where('user_id',$id)->update(['video_recom'=>$online]);
        $now = time();
        $roomDB = Db::name("live_room")->where('user_id',$id)->where('live_type',6)->where('live_state',1);
        if ($online == 1){
            //上线时，判断是否存在直播间
            $check = $roomDB->find();
            if ($check){
                $editData = [
                    'id'             => $check['id'],
                    'live_state'    => 2,
                    'live_end'      => $now
                ];
                Db::name("live_room")->update($editData);
            }
            $user = Db::name("user")->where('id',$id)->find();
            //直播间信息入库
            $saveData = [
                'user_id'       => $user['id'],
                'live_type'     => 6,
                'live_title'    => $user['user_nickname'],
                'live_create'   => $now,
                'live_start'    => $now,//暂时创建即开播，后续改为创建后，主播点击开播
                'live_end'      => 0,
                'live_state'    => 1,
                'live_code'     => ""
            ];
            $re = Db::name('live_room')->insert($saveData);
        }else
        {
            $editData = [
                'live_state'    => 2,
                'live_end'      => $now
            ];
            $roomDB->update($editData);
        }
        if ($re)
        {
            $this->success($str."成功");
        }else{
            $this->error($str."失败");
        }
    }

    //话术类型
    private $messageType = ['请选择','消息','照片','视频','打招呼','关注','来访'];//,'视频通话'
    /**
     * 话术管理
     */
    public function message()
    {
        $id = input('id', 0, 'intval');

        $list = Db::name("live_message")->where('user_id',$id)->select();

        $this->assign('uid', $id);
        $this->assign('type', $this->messageType);
        $this->assign('list', $list);
        return $this->fetch();
    }
    /**
     * 话术保存提交
     * @return mixed
     */
    public function messageSave()
    {
        $data = $this->request->post();
        switch ($data['type'])
        {
            case 1:
                if ($data['message'] == '')
                    $this->error("请填写消息内容");
                break;
            case 2:
            case 3:
                $file = $this->request->file('file');
                if ($data['id'] == 0 && !$file)
                    $this->error("请上传文件");
                //上传文件到环信
                if (!empty($file))
                {
                    $result = hook_one("fiels_easemob", $file);
                    $data['message'] = json_encode($result);
                } else {
                    unset($data['message']);
                }
                unset($data['file']);
                break;
            case 4:
            case 5:
            case 6:
            case 7:
                $data['message'] = $this->messageType[$data['type']];
                break;
            default:
                $this->error("请选择话术类型");
        }
        $data['second'] = (int)$data['second'];

        if ($data['id'] == 0)
        {
            unset($data['id']);
            $re = Db::name('live_message')->insert($data);
        }
        else
        {
            $re = Db::name('live_message')->update($data);
        }

        if ($re)
        {
            $this->success("保存成功");
        }
        $this->error("保存失败");
    }
    /**
     * 话术删除提交
     */
    public function messageDel()
    {
        $id = input('id', 0, 'intval');
        if (Db::name('live_message')->where('id',$id)->delete())
        {
            $this->success("删除成功");
        }
        $this->error("删除失败");
    }

    public function getCitys()
    {
        $p = $this->request->get('p');
        $c = Db::name('citys')->group('city')->order('gd')->where('province',$p)->column('city');
        $this->success("succ",null,json_encode($c));
    }

    /**
     * 评论管理
     * @return mixed
     */
    public function comment()
    {
        $id = input('id', 0, 'intval');

        $list = Db::name("live_comment")->alias('c')
            ->field('u.user_nickname nickname,avatar,c.*')
            ->join('__USER__ u', 'c.user_id =u.id')
            ->where('anchor_id',$id)
            ->select();

        $this->assign('uid', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }
    public function commentSave()
    {
        $data = $this->request->post();
        $data['add_time'] = strtotime($data['add_time']);
        if ($data['id'] == 0)
        {
            unset($data['id']);
            $re = Db::name('live_comment')->insert($data);
        }
        else
        {
            $re = Db::name('live_comment')->update($data);
        }

        if ($re)
        {
            $this->success("保存成功");
        }
        $this->error("保存失败");
    }
    public function commentDel()
    {
        $id = input('id', 0, 'intval');
        if (Db::name('live_comment')->where('id',$id)->delete())
        {
            $this->success("删除成功");
        }
        $this->error("删除失败");
    }

    /**
     * 随机视频聊天接通率
     * @throws \think\Exception
     */
    public function autoConnect(){

        $id = input('id', 0, 'intval');

        $apply = rand(10,1000);
        $accept = rand(($apply * 0.9),$apply);//80-100%接通率
        $per = round(($accept * 100 / $apply),0);
        $data = [
            'connect_apply'     => $apply,
            'connect_accept'    => $accept,
            'connect_per'       => $per,
        ];
        $re = Db::name("live_anchor")->where('user_id',$id)->update($data);
        if ($re) $this->success("更新成功");
        $this->error("更新失败");
    }

    /**
     * 相册管理
     * @return mixed
     */
    public function photo()
    {
        $id = input('id', 0, 'intval');

        $list = Db::name("user_photo")->where('user_id',$id)->select();

        $this->assign('uid', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }
    public function photoSave()
    {
        $data = $this->request->post();

        if ($data['url'] != '' &&  strpos($data['url'],'http://') === false && strpos($data['url'],'/upload') === false){
            $data['url'] = '/upload/' .$data['url'];
        }
        $data['add_time'] = strtotime($data['add_time']);
        if ($data['id'] == '0')
        {
            unset($data['id']);
            $re = Db::name('user_photo')->insert($data);
        }
        else
        {
            $re = Db::name('user_photo')->update($data);
        }

        if ($re)
        {
            $this->success("保存成功");
        }
        $this->error("保存失败");
    }
    public function photoDel()
    {
        $id = input('id', 0, 'intval');
        if (Db::name('user_photo')->where('id',$id)->delete())
        {
            $this->success("删除成功");
        }
        $this->error("删除失败");
    }

    /**
     * 视频管理
     * @return mixed
     */
    public function video()
    {
        $id = input('id', 0, 'intval');

        $list = Db::name("user_video")->where('user_id',$id)->select();

        $this->assign('uid', $id);
        $this->assign('list', $list);
        return $this->fetch();
    }
    public function videoSave()
    {
        $data = $this->request->post();
        $data['add_time'] = strtotime($data['add_time']);
        if ( $data['url'] != '' && strpos( $data['url'] , 'http://' ) === false && strpos( $data['url'] , '/upload' ) === false ) {
            $data['url'] = '/upload/' .$data['url'];
        }
        if ( $data['send_order'] != $data['last_order'] ) {//排序有变化
            if ($data['send_order'] == 0) {//取消排序，将原有序号后续排序提前
                Db::name('user_video')->where(['send_order'=>['>=',$data['last_order']]])->setDec('send_order');
            } else {//设置排序
                if ( $data['last_order'] == 0 ){//原来没有排序，将新序号后面的增加
                    Db::name('user_video')->where([ 'send_order'=> ['>=',$data['send_order']] ])->setInc('send_order');
                } elseif ( $data['send_order'] > $data['last_order'] ) {//序号向后移动,原序号和新序号之间的向前移动
                    $where = [
                        'send_order'=> ['>',$data['last_order']],
                        'send_order'=> ['<=',$data['send_order']]
                    ];
                    Db::name('user_video')->where($where)->setDec('send_order');
                } else {//序号向前移动,原序号和新序号之间的向后移动
                    $where = [
                        'send_order'=> ['<',$data['last_order']],
                        'send_order'=> ['>=',$data['send_order']]
                    ];
                    Db::name('user_video')->where($where)->setInc('send_order');
                }
            }
        }
        unset( $data['last_order'] );
        if ( $data['id'] == '0' ) {
            unset( $data['id'] );
            $re = Db::name('user_video')->insert($data);
        } else {
            $re = Db::name('user_video')->update($data);
        }

        if ( $re ) {
            $this->success("保存成功");
        }
        $this->error("保存失败");
    }
    public function videoDel()
    {
        $id = input('id', 0, 'intval');
        $order = input('order', 0, 'intval');
        if ($order > 0) {//有序号时，后面的需要向前移动
            Db::name('user_video')->where(['send_order'=> ['>',$order]])->setDec('send_order');
        }
        if (Db::name('user_video')->where('id',$id)->delete()) {
            $this->success("删除成功");
        }
        $this->error("删除失败");
    }
}
