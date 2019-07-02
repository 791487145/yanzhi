<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------

namespace api\portal\controller;

use cmf\controller\RestBaseController;
use api\portal\model\PortalPostModel;

class PageController extends RestBaseController
{
    protected $postModel;

    public function __construct(PortalPostModel $postModel)
    {
        parent::__construct();
        $this->postModel = $postModel;
    }

    /**
     * 页面列表
     */
    public function index()
    {
        //slide为空或不存在抛出异常
        $id = $this->request->param('id', 0, 'intval');
        if (empty($id)) {
            $this->error('缺少ID参数');
        }
        $params['where']['post_type'] = 2;
        $data                         = $this->postModel->getDatas(['id' => $id]);
        if (empty($data)){
            $this->error("页面不存在");
        }

        $re = [
            'title'     => $data['post_title'],
            'content'   => base64_encode($data['post_content']),
        ];
        $this->success('请求成功!', $re);
    }

    /**
     * 获取页面
     * @param int $id
     */
    public function read($id)
    {
        $params                       = $this->request->get();
        $params['where']['post_type'] = 2;
        $params['id']                 = $id;
        $data                         = $this->postModel->getDatas($params);
        $this->success('请求成功!', $data);
    }
}
