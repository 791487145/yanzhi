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

namespace app\broadcast\controller;

use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class AnchorController
 * @package app\broadcast\controller
 *
 * @adminMenuRoot(
 *     'name'   =>'直播管理',
 *     'action' =>'default',
 *     'parent' =>'',
 *     'display'=> true,
 *     'order'  => 10,
 *     'icon'   =>'group',
 *     'remark' =>'直播管理'
 * )
 */
class AnchorController extends AdminBaseController
{
    /**
     * 后台主播管理列表
     * @adminMenu(
     *     'name'   => '主播管理',
     *     'parent' => 'default1',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '主播管理',
     *     'param'  => ''
     * )
     */
    public function index() {
        $where = ['u.user_type'=>2];

        $gid = input('param.gid', 0, 'intval');
        $this->assign('gid', $gid);
        if ( $gid > 0 ) {
            $where['a.guide_id'] = intval($gid);
        }
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $keywordComplex['u.user_nickname|u.mobile|u.truename']    = ['like', "%$keyword%"];
        }

        $list = Db::name('live_anchor')
            ->alias("a")
            ->join('__USER__ u', 'a.user_id =u.id')
            ->where($where)
            ->whereOr($keywordComplex)
            ->order("a.add_time desc,a.id desc")
            ->paginate(10);
//        var_dump(Db::name('live_anchor')->getLastSql());

        // 获取分页显示
        $page = $list->render();

        $guide = Db::name('guide')->order('code asc')->select();

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('guide', $guide);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 后台主播详情
     * @adminMenu(
     *     'name'   => '主播详情',
     *     'parent' => 'anchor',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '主播详情',
     *     'param'  => ''
     * )
     */
    public function info()
    {
        $id = input('param.id', 0, 'intval');
        $next = input('param.next', 0, 'intval');
        if ($next == 1){
            $tmp = Db::name("live_anchor")->where(["user_id" => $id])->find();
            $anchor = Db::name("live_anchor")->where(['add_time'=>['ELT',$tmp['add_time']],'id'=>['LT',$tmp['id']]])->order("add_time DESC,id DESC")->find();
            if (!$anchor){
                $this->error("已经是最后一个了",cmf_get_root() . '/broadcast/anchor/index');
            }
            $id = $anchor['user_id'];
        } else{
            $anchor = Db::name("live_anchor")->where(["user_id" => $id])->find();
            if (!$anchor){
                $this->error("该用户未申请");
            }
        }

        $user = Db::name("user")->where(["id" => $id, "user_type" => 2])->find();
        if (!$user){
            $this->error("没有该用户");
        }
        $photo = Db::name('user_photo')->where(["user_id" => $id])->select();
        $guideId = $anchor['guide_id'];
        if ($guideId > 0){
            $guide = Db::name('guide')->where(['id'=>$guideId])->find();
            $this->assign('guide', $guide);
        }
        $exam = explode("|",$anchor['more']);

        $this->assign('user', $user);
        $this->assign('anchor', $anchor);
        $this->assign('photo', $photo);
        $this->assign('exam', $exam);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 主播审核
     * @adminMenu(
     *     'name'   => '主播审核',
     *     'parent' => 'anchor',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '主播审核',
     *     'param'  => ''
     * )
     */
    public function exam(){
        $id = input('param.id', 0, 'intval');
        $status = input('param.status', 0, 'intval');
        $explain = input('param.explain');
        if ($status == 0){
            $this->error("请选择审核结果");
        }
        if ($status == 2 && $explain == ""){
            $this->error("请填写未通过原因");
        }
        $info = Db::name('live_anchor')->where(['id'=>$id])->find();
        if (!$info){
            $this->error("您的操作有误");
        }
        $info['status'] = $status;
        $info['audit_time'] = time();
        $info['more'] .= $info['audit_time'] . "," . $status;
        if ($explain != ""){
            $info['more'] .= "," . $explain;
        }
        $info['more'] .= "|";
        Db::name('live_anchor')->where(['id'=>$id])->update($info);
        $this->success("审核成功");
    }

    public function liverooms() {
        $where = ['r.live_type'=>1];

        $liveState = input('param.live_state', 1, 'intval');
        $this->assign('live_state', $liveState);
        if ( $liveState >= 0 ) {
            $where['r.live_state'] = $liveState;
        }

        $gid = input('param.gid', 0, 'intval');
        $this->assign('gid', $gid);
        if ( $gid > 0 ) {
            $where['a.guide_id'] = intval($gid);
        }

        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $keywordComplex['u.user_nickname|u.mobile|u.truename']    = ['like', "%$keyword%"];
        }

        $list = Db::name('live_room')
            ->field("r.*,u.*,a.*,g.name gname")
            ->alias("r")
            ->join('yz_live_anchor a', 'a.user_id =r.user_id')
            ->join('yz_guide g', 'a.guide_id =g.id',"left")
            ->join('__USER__ u', 'r.user_id =u.id')
            ->where($where)
            ->whereOr($keywordComplex)
            ->order("r.live_start desc")
            ->paginate(10);
//        var_dump(Db::name('live_anchor')->getLastSql());

        // 获取分页显示
        $page = $list->render();

        $guide = Db::name('guide')->order('code asc')->select();

        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign('guide', $guide);
        // 渲染模板输出
        return $this->fetch();
    }
}
