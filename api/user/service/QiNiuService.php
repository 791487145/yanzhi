<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: pl125 <xskjs888@163.com>
// +----------------------------------------------------------------------
namespace api\user\service;

use Qiniu\Auth;
use Qiniu\Cdn\CdnManager;

class QiNiuService
{
    private $accessKey = 'nhDhP4R-24sB2QZrBQ25smH1IpTu9fJy7yodm9l-';
    private $secretKey = 'i5PnhBL9UAeybhnGTK1ehRrsWGQPjg8yyjXXqPL-';
    public $auth;
    private $host_cdn = array(
        'http://img.miyueba.cn'
    );

    public function __construct()
    {
        $this->auth = new Auth($this->accessKey, $this->secretKey);
    }


    public function cdn_refresh($host,$cdn)
    {
        $urls = array(
            $this->host_cdn[$cdn].$host
        );

        $cdnManager = new CdnManager($this->auth);

        list($refreshResult, $refreshErr) = $cdnManager->refreshUrls($urls);
        if ($refreshErr != null) {
            var_dump($refreshErr);
        } else {
            echo "refresh request sent\n";
            print_r($refreshResult);
        }
    }
}
