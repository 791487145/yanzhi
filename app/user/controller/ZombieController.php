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
use think\Db;

/**
 * Class AdminIndexController
 * @package app\user\controller
 */
class ZombieController extends AdminBaseController
{

    /**
     * 僵尸主播列表
     */
    public function index()
    {
        $where   = ['user_type'=>2,'is_zombie'=>1];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['id'] = intval($request['uid']);
        }
        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];

            $keywordComplex['user_login|user_nickname|mobile']    = ['like', "%$keyword%"];
        }
        $usersQuery = Db::name('user');

        $list = $usersQuery->whereOr($keywordComplex)->where($where)->order("create_time DESC")->paginate(10);
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $list);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 测试。读取小说内容
     */
    public function test()
    {
        $p = $this->request->get('p', 1, 'intval');

        $url = "https://m.ddshuoshuo.com/news/".$p.".html";

        $data = file_get_contents($url);

        $start = stripos($data, '<div class="content">');
        $data = substr($data,$start+21);
        $end = stripos($data, '</div>');
        $data = substr($data,0,$end);

        $title_start =stripos($data, '<h3>');
        $title = substr($data,$title_start+4);
        $title_end =stripos($title, '</h3>');
        $title =substr($title,0,$title_end);
        $title = trim($title);
        $title = trim($title,"?");
        if($title == '')
        {
            $title = $p;
        }
        $name = str_replace([' 	      ','*'], ' ', $title);
        var_dump($title);

        $data = str_replace(array('<p>', '</p>', '<h3>', '</h3>'), '', $data);
        $data = str_replace('                    ', '', $data);

//         . 'public' . DS . 'upload' . DS . $act . DS . $idUrl[0] . DS . $idUrl[1];
        $source = iconv("UTF-8","GBK//IGNORE",ROOT_PATH."xiaoshuo".DS.$name.".txt");
        $re = file_put_contents($source,$data);
        if ($re>0)
        {
            echo "<script>window.location.href='/user/Virtual/test/?p=".($p+1)."';</script>";
        }
    }
}
