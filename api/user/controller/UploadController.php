<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace api\user\controller;

use api\user\service\QiNiuService;
use cmf\controller\RestUserBaseController;
use think\Log;
use think\Validate;
use think\Db;

class UploadController extends RestUserBaseController
{
    /**
     * 上传base64视频
     */
    public function videoBase64()
    {
        $data = $this->request->post();
        $base64_image_content = $data['file'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $suffix = $result[2];

            if (!in_array(strtolower($suffix), ['mp4','avi','wmv','rm','rmvb','mkv'])) {
                $this->error("视频格式错误");
            }
            $userId = $this->getUserId();

            $idUrl = $this->getIdUrl($userId);
            $videoSrc = '/upload/video/' . $idUrl[0] . '/' . $idUrl[1] . '/' . $idUrl[2];
            $fileSrc = ROOT_PATH . 'public' . DS . 'upload' . DS . 'video' . DS . $idUrl[0] . DS . $idUrl[1] . DS . $idUrl[2];
            $fileName = md5(microtime(true)).'.'.$suffix;

            //解码
            $info=base64_decode(str_replace($result[1], '', $base64_image_content));
            $fileMd5 = md5($info);
            $fileSha1 = sha1($info);
            $findFile = Db::name("asset")->where('file_md5', $fileMd5)->where('file_sha1', $fileSha1)->find();
            if (!empty($findFile)) //已存在的文件直接返回文件地址和文件名
            {
                $this->success("已上传成功!", ['url' => $findFile['file_path'], 'filename' => $findFile['filename']]);
            }
            if(!is_dir($fileSrc)) mkdir($fileSrc, 0775, true);
            if (file_put_contents($fileSrc.DS.$fileName, $info)){
                $fileSize = strlen($info);
                if ($fileSize > 1024 * 1024 * 10) {
                    $this->error("视频文件不大于10M");
                }
                    $fileKey = $fileMd5 . md5($fileSha1);

                    Db::name('asset')->insert([
                        'user_id' => $userId,
                        'file_key' => $fileKey,
                        'filename' => $fileName,
                        'file_size' => $fileSize,
                        'file_path' => $fileSrc.'/'.$fileName,
                        'file_md5' => $fileMd5,
                        'file_sha1' => $fileSha1,
                        'create_time' => time(),
                        'suffix' => $suffix
                    ]);
                $this->success("上传成功!", ['url' => $fileSrc.'/'.$fileName, 'filename' => $fileName]);
            }else{
                $this->error("上传失败");
            }

        }else{
            $this->error("文件格式有误");
        }
    }
    /**
     * 上传base64图片
     */
    public function imgBase64() {
        $validate = new Validate([
            'act' => 'require',
            'file' => 'require',
        ]);
        $validate->message([
            'act.require' => '缺少类型',
            'file.require' => '缺少文件',
        ]);
        $data = $this->request->post();
        $base64_image_content = $data['file'];
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
            $suffix = $result[2];

            if (!in_array(strtolower($suffix),['jpg','jpeg','png'])) {
                $this->error("图片格式错误");
            }

            $userId = $this->userId;
            $act = $data['act'];
            $idUrl = $this->getIdUrl($userId);
            $imgSrc = '/upload/' . $act . '/' . $idUrl[0] . '/' . $idUrl[1];
            $fileSrc = ROOT_PATH . 'public' . DS . 'upload' . DS . $act . DS . $idUrl[0] . DS . $idUrl[1];
            $fileName = $checkFile = true;
            $t = "";
            switch ($act) {
                case "head": // 头像移动到框架应用根目录/public/upload/head/000/000/0001.jpg 目录下
                case "card": // 身份证照片移动到框架应用根目录/public/upload/card/000/000/0001.jpg 目录下
                    $fileName = $idUrl[2] . '.jpg';
                    $checkFile = false;//头像、手持身份证照片为同名替换文件，不需要记录和判断重复性
                    $t = "?t=".time();
                    break;
                case "photo":// 照片移动到框架应用根目录/public/upload/photo/000/000/0001/***。jpg[png] 目录下
                case "video":// 照片移动到框架应用根目录/public/upload/video/000/000/0001/***。jpg[png] 目录下
                    $fileName = md5(microtime(true)).'.'.$suffix;
                    $fileSrc .= DS . $idUrl[2];
                    $imgSrc .= '/' . $idUrl[2];
                    break;
                default:
                    $this->error("参数错误");
            }

            //解码
            $info=base64_decode(str_replace($result[1], '', $base64_image_content));
            $fileMd5 = md5($info);
            $fileSha1 = sha1($info);
            $findFile = Db::name("asset")->where('file_md5', $fileMd5)->where('file_sha1', $fileSha1)->find();
            if (!empty($findFile)) //已存在的文件直接返回文件地址和文件名
            {
                $this->success("已上传成功!", ['url' => $findFile['file_path'], 'filename' => $findFile['filename']]);
            }
            if(!is_dir($fileSrc)) mkdir($fileSrc, 0775, true);
            if (file_put_contents($fileSrc.DS.$fileName, $info)){
                $fileSize = strlen($info);
                if ($act == 'head') {
                    $imgUrl = $imgSrc . '/' . $fileName;
                    Db::name('user')->where(['id'=>$userId])->update(['avatar'=>$imgUrl]);
                }

                if ($checkFile) {//需要判断重复性的，讲文件信息入库
                    $fileKey = $fileMd5 . md5($fileSha1);

                    Db::name('asset')->insert([
                        'user_id' => $userId,
                        'file_key' => $fileKey,
                        'filename' => $fileName,
                        'file_size' => $fileSize,
                        'file_path' => $imgSrc.'/'.$fileName,
                        'file_md5' => $fileMd5,
                        'file_sha1' => $fileSha1,
                        'create_time' => time(),
                        'suffix' => $suffix
                    ]);
                }

                if ($act == 'head') {//修改头像后，上传极光IM
                    $param = [
                        'type'      => "image",
                        'url'       => $fileSrc . DS . $fileName
                    ];
                    $result = hook_one("upload_jpush", $param);
                    if ($result['http_code'] == 200) {
                        $pData = [
                            "name"  => 'QY_'.$userId,
                            "data"  => [
                                "avatar"    => $result['body']['media_id'],
                                "nickname"  => $this->user['user_nickname']
                            ]
                        ];
                        $re = hook_one("user_jpush_edit", $pData);
                    }
                }
                $this->success("上传成功!", ['url' => $imgSrc.'/'.$fileName, 'filename' => $fileName]);
            }else{
                $this->error("上传失败");
            }
        }else{
            $this->error("图片格式有误");
        }
    }

    /**
     * 上传头像/身份证/相册图片
     */
    public function img()
    {
        $validate = new Validate([
            'act' => 'require',
        ]);
        $validate->message([
            'act.require' => '缺少参数',
        ]);
        $data = $this->request->post();
        if (!$validate->check($data)) {
            $this->error($validate->getError());
        }

        $act = $data['act'];
        $userId = $this->getUserId();

        $idUrl = $this->getIdUrl($this->userId);
        $imgSrc = '/upload/' . $act . '/' . $idUrl[0] . '/' . $idUrl[1];
        $fileSrc = ROOT_PATH . 'public' . DS . 'upload' . DS . $act . DS . $idUrl[0] . DS . $idUrl[1];
        $fileName = $checkFile = true;
        $t = "";
        switch ($act) {
            case "head": // 头像移动到框架应用根目录/public/upload/head/000/000/0001.jpg 目录下
            case "card": // 身份证照片移动到框架应用根目录/public/upload/card/000/000/0001.jpg 目录下
                $fileName = $idUrl[2] . '.jpg';
                $checkFile = false;//头像、手持身份证照片为同名替换文件，不需要记录和判断重复性
                $t = "?t=".time();
                break;
            case "photo":// 照片移动到框架应用根目录/public/upload/photo/000/000/0001/***。jpg[png] 目录下
            case "video":// 照片移动到框架应用根目录/public/upload/video/000/000/0001/***。jpg[png] 目录下
                $fileSrc .= DS . $idUrl[2];
                $imgSrc .= '/' . $idUrl[2];
                break;
            default:
                $this->error("参数错误");
        }

        $file = $this->request->file('file');

        $info = $file->validate([
            'ext' => 'jpg,jpeg,png',
            'size' => 1024 * 1024 * 2//图片大小不超过2M
        ]);
        $fileSize = $info->getInfo('size');
        if ($fileSize == 0) {
            $this->error("文件错误");
        }
        if ($checkFile) {//需要判断文件重复上传
            $fileMd5 = $info->md5();
            $fileSha1 = $info->sha1();

            $findFile = Db::name("asset")->where('file_md5', $fileMd5)->where('file_sha1', $fileSha1)->find();
            if (!empty($findFile)) //已存在的文件直接返回文件地址和文件名
            {
                $this->success("已上传成功!", ['url' => $findFile['file_path'], 'filename' => $findFile['filename']]);
            }
        }

        $info = $info->move($fileSrc, $fileName);
        if ($info) {
            $saveName = $info->getSaveName();
            $saveName = str_replace(DS,'/',$saveName);
            $saveName = $imgSrc . '/' . $saveName;
            $originalName = $info->getInfo('name');//name,type,size

            if ($act == 'head') {
                $imgUrl = $imgSrc . '/' . $fileName;
                Db::name('user')->where(['id'=>$userId])->update(['avatar'=>$imgUrl]);
                $qiniu = new QiNiuService();
                $qiniu->cdn_refresh('',0);
            }

            if ($checkFile) {//需要判断重复性的，讲文件信息入库
                $suffix = $info->getExtension();
                $fileKey = $fileMd5 . md5($fileSha1);
                Db::name('asset')->insert([
                    'user_id' => $userId,
                    'file_key' => $fileKey,
                    'filename' => $originalName,
                    'file_size' => $fileSize,
                    'file_path' => $saveName,
                    'file_md5' => $fileMd5,
                    'file_sha1' => $fileSha1,
                    'create_time' => time(),
                    'suffix' => $suffix
                ]);
            }
            if ($act == 'head') {//修改头像后，上传极光IM
                $param = [
                    'type'      => "image",
                    'url'       => $fileSrc . DS . $fileName
                ];
                $result = hook_one("upload_jpush", $param);
                if ($result['http_code'] == 200) {
                    $pData = [
                        "name"  => 'QY_'.$userId,
                        "data"  => [
                            "avatar"    => $result['body']['media_id'],
                            "nickname"  => $this->user['user_nickname']
                        ]
                    ];
                    $re = hook_one("user_jpush_edit", $pData);
                }
            }
            $this->success("上传成功!", ['url' => $saveName.$t, 'filename' => $originalName]);
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }

    /**
     * 上传一张图片
     */
    public function one()
    {
        $file = $this->request->file('file');
        // 移动到框架应用根目录/public/upload/ 目录下
        $info = $file->validate([
            /*'size' => 15678,*/
            'ext' => 'jpg,png'
        ]);
        $fileMd5 = $info->md5();
        $fileSha1 = $info->sha1();

        $findFile = Db::name("asset")->where('file_md5', $fileMd5)->where('file_sha1', $fileSha1)->find();

        if (!empty($findFile)) {
            $this->success("上传成功!", ['url' => $findFile['file_path'], 'filename' => $findFile['filename']]);
        }
        $info = $info->move(ROOT_PATH . 'public' . DS . 'upload');
        if ($info) {
            $saveName = $info->getSaveName();
            $originalName = $info->getInfo('name');//name,type,size
            $fileSize = $info->getInfo('size');
            $suffix = $info->getExtension();

            $fileKey = $fileMd5 . md5($fileSha1);

            $userId = $this->getUserId();
            Db::name('asset')->insert([
                'user_id' => $userId,
                'file_key' => $fileKey,
                'filename' => $originalName,
                'file_size' => $fileSize,
                'file_path' => $saveName,
                'file_md5' => $fileMd5,
                'file_sha1' => $fileSha1,
                'create_time' => time(),
                'suffix' => $suffix
            ]);

            $this->success("上传成功!", ['url' => $saveName, 'filename' => $originalName]);
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }


    /**
     * 上传视频
     */
    public function video()
    {
        $userId = $this->getUserId();
        $idUrl = $this->getIdUrl($this->userId);
        $videoSrc = '/upload/video/' . $idUrl[0] . '/' . $idUrl[1] . '/' . $idUrl[2];
        $fileSrc = ROOT_PATH . 'public' . DS . 'upload' . DS . 'video' . DS . $idUrl[0] . DS . $idUrl[1] . DS . $idUrl[2];
        $fileName = true;
        $t = "";

        $file = $this->request->file('file');
        $info = $file->validate([
            'ext' => 'mp4,avi,wmv,rm,rmvb,mkv',
            'size' => 1024 * 1024 * 10//视频大小不超过10M
        ]);
        $fileMd5 = $info->md5();
        $fileSha1 = $info->sha1();

        $findFile = Db::name("asset")->where('file_md5', $fileMd5)->where('file_sha1', $fileSha1)->find();

        if (!empty($findFile)) //已存在的文件直接返回文件地址和文件名
        {
            $this->success("已上传成功!", ['url' => $findFile['file_path'], 'filename' => $findFile['filename']]);
        }

        $info = $info->move($fileSrc, $fileName);
        if ($info)
        {
            $saveName = $info->getSaveName();
            $saveName = str_replace(DS,'/',$saveName);
            $saveName = $videoSrc . '/' . $saveName;
            $originalName = $info->getInfo('name');//name,type,size

            $fileSize = $info->getInfo('size');
            $suffix = $info->getExtension();

            $fileKey = $fileMd5 . md5($fileSha1);

            Db::name('asset')->insert([
                'user_id' => $userId,
                'file_key' => $fileKey,
                'filename' => $originalName,
                'file_size' => $fileSize,
                'file_path' => $saveName,
                'file_md5' => $fileMd5,
                'file_sha1' => $fileSha1,
                'create_time' => time(),
                'suffix' => $suffix
            ]);
            $this->success("上传成功!", ['url' => $saveName.$t, 'filename' => $originalName]);
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }
}
