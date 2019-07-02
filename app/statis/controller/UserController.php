<?php
// +----------------------------------------------------------------------
// | 虎虎科技
// +----------------------------------------------------------------------
// | 报表管理
// +----------------------------------------------------------------------
// | Author: 李雪
// +----------------------------------------------------------------------
namespace app\statis\controller;

use think\Db;
use cmf\controller\AdminBaseController;

class UserController extends AdminBaseController
{
    private $days = 10;//报表天数
    public function down()
    {
        $param = $this->request->param();
        $this->assign('param', $param);
        $arrDevice = ['不限','ios','android'];
        $this->assign('arr_device', $arrDevice);

        $db = Db::name('statis_down');
        if (!empty($param['channel']) && $param['channel'] != '')
        {
            $db->whereIn(['channel_id'],$param['channel']);
        }

        $start = isset($param['start_time']) ? $param['start_time'] : '';
        $end = isset($param['end_time']) ? $param['end_time'] : '';
        if ($start == '' && $end == '') {
            $start = date("Y-m-d",strtotime("-".$this->days." day"));
            $end = date("Y-m-d");
        } elseif ($start == '') {
            $start = date("Y-m-d",(strtotime($end) - 86400 * $this->days));
        } elseif ($end == '') {
            $end = date("Y-m-d",(strtotime($start) + 86400 * $this->days));
        } elseif (strtotime($end) - strtotime($start) > 86400 * $this->days) {
            $this->error("最多查询".$this->days."天数据",url('statis/user/register'));
        }

        $list = $db
            ->where(['date'=>['>=',$start]])
            ->where(['date'=>['<=',$end]])
            ->order('date', 'DESC')->select();

        $dateList = $dateHash = $downList = $dipList = $insList = $iipList = $regList = $ripList = $channelList = $channelIds = $hashChannel = [];

        for ($i = 0; $i < $this->days; $i++)
        {
            $d = date("Y-m-d",(strtotime($start) + 86400 * $i));
            $dateList[$i] = $d;//[序号=>日期]
            $dateHash[$d] = $i;//[日期=>序号]
            $downList[$i] = 0;//[序号=>下载数]
            $dipList[$i] = 0;//[序号=>下载IP数]
            $insList[$i] = 0;//[序号=>安装数]
            $iipList[$i] = 0;//[序号=>安装IP数]
            $regList[$i] = 0;//[序号=>注册数]
            $ripList[$i] = 0;//[序号=>注册IP数]
        }

        foreach ($list as $value)
        {
            $i = $dateHash[$value['date']];
            if (empty($param['divece']) || $param['divece'] == 0)
            {
                $downList[$i] += $value['count_down_ios'] + $value['count_down_android'];
                $dipList[$i] += $value['ip_down_ios'] + $value['ip_down_android'];
                $insList[$i] += $value['count_ins_ios'] + $value['count_ins_android'];
                $iipList[$i] += $value['ip_ins_ios'] + $value['ip_ins_android'];
                $regList[$i] += $value['count_reg_ios'] + $value['count_reg_android'];
                $ripList[$i] += $value['ip_reg_ios'] + $value['ip_reg_android'];

                $channelTmp = [
                    'count_down'    => $value['count_down_ios'] + $value['count_down_android'],
                    'ip_down'       => $value['ip_down_ios'] + $value['ip_down_android'],
                    'count_ins'     => $value['count_ins_ios'] + $value['count_ins_android'],
                    'ip_ins'        => $value['ip_ins_ios'] + $value['ip_ins_android'],
                    'count_reg'     => $value['count_reg_ios'] + $value['count_reg_android'],
                    'ip_reg'        => $value['ip_reg_ios'] + $value['ip_reg_android'],
                ];
            }
            else
            {
                $downList[$i] += $value['count_down_'.$arrDevice[$param['divece']]];
                $dipList[$i] += $value['ip_down_'.$arrDevice[$param['divece']]];
                $insList[$i] += $value['count_ins_'.$arrDevice[$param['divece']]];
                $iipList[$i] += $value['ip_ins_'.$arrDevice[$param['divece']]];
                $regList[$i] += $value['count_reg_'.$arrDevice[$param['divece']]];
                $ripList[$i] += $value['ip_reg_'.$arrDevice[$param['divece']]];

                $channelTmp = [
                    'count_down'    => $value['count_down_'.$arrDevice[$param['divece']]],
                    'ip_down'       => $value['ip_down_'.$arrDevice[$param['divece']]],
                    'count_ins'     => $value['count_ins_'.$arrDevice[$param['divece']]],
                    'ip_ins'        => $value['ip_ins_'.$arrDevice[$param['divece']]],
                    'count_reg'     => $value['count_reg_'.$arrDevice[$param['divece']]],
                    'ip_reg'        => $value['ip_reg_'.$arrDevice[$param['divece']]],
                ];
            }
            if (isset($channelList[$value['channel_id']]))
            {
                $channelList[$value['channel_id']]['count_down'] += $channelTmp['count_down'];
                $channelList[$value['channel_id']]['ip_down'] += $channelTmp['ip_down'];
                $channelList[$value['channel_id']]['count_ins'] += $channelTmp['count_ins'];
                $channelList[$value['channel_id']]['ip_ins'] += $channelTmp['ip_ins'];
                $channelList[$value['channel_id']]['count_reg'] += $channelTmp['count_reg'];
                $channelList[$value['channel_id']]['ip_reg'] += $channelTmp['ip_reg'];
            }
            else
            {
                $channelList[$value['channel_id']] = $channelTmp;
            }

            $channelIds[] = $value['channel_id'];
        }

        $arrChannel= Db::name('channel')->whereIn('user_id',$channelIds)->select();
        $hashChannel[0] = "无渠道";
        foreach ($arrChannel as $value)
        {
            $hashChannel[$value['user_id']] = $value['name'];
        }

        $this->assign('date_list', json_encode($dateList));
        $this->assign('down_list', json_encode($downList));
        $this->assign('dip_list', json_encode($dipList));
        $this->assign('ins_list', json_encode($insList));
        $this->assign('iip_list', json_encode($iipList));
        $this->assign('reg_list', json_encode($regList));
        $this->assign('rip_list', json_encode($ripList));
        $this->assign('channel_list', $channelList);
        $this->assign('hash_channel', $hashChannel);

        return $this->fetch();
    }

    /**
     * 注册用户统计报表
     */
    public function register()
    {
        $param = $this->request->param();
        $this->assign('param', $param);

        $db = Db::name('statis_register');
        if (!empty($param['channel']) && $param['channel'] != '')
        {
            $db->whereIn(['channel_id'],$param['channel']);
        }

        $start = isset($param['start_time']) ? $param['start_time'] : '';
        $end = isset($param['end_time']) ? $param['end_time'] : '';
        if ($start == '' && $end == '') {
            $start = date("Y-m-d",strtotime("-".$this->days." day"));
            $end = date("Y-m-d");
        } elseif ($start == '') {
            $start = date("Y-m-d",(strtotime($end) - 86400 * $this->days));
        } elseif ($end == '') {
            $end = date("Y-m-d",(strtotime($start) + 86400 * $this->days));
        } elseif (strtotime($end) - strtotime($start) > 86400 * $this->days) {
            $this->error("最多查询".$this->days."天数据",url('statis/user/register'));
        }

        $list = $db
            ->where(['date'=>['>=',$start]])
            ->where(['date'=>['<=',$end]])
            ->order('date', 'DESC')->select();

        $dateList = $dateHash = $userList = $ipList = $channelList = $channelIds = $hashChannel = [];
        for ($i = 0; $i < $this->days; $i++)
        {
            $d = date("Y-m-d",(strtotime($start) + 86400 * $i));
            $dateList[$i] = $d;
            $dateHash[$d] = $i;
            $userList[$i] = 0;
            $ipList[$i] = 0;
        }
        foreach ($list as $value)
        {
            $i = $dateHash[$value['date']];
            $userList[$i] += $value['regnum_user'];
            $ipList[$i] += $value['regnum_ip'];
            $channelList[$value['channel_id']][$i] = $value;
            $channelIds[] = $value['channel_id'];
        }

        $arrChannel= Db::name('channel')->whereIn('user_id',$channelIds)->select();
        $hashChannel[0] = "无渠道";
        foreach ($arrChannel as $value)
        {
            $hashChannel[$value['user_id']] = $value['name'];
        }

        $this->assign('date_list', json_encode($dateList));
        $this->assign('user_list', json_encode($userList));
        $this->assign('ip_list', json_encode($ipList));
        $this->assign('date', $dateList);
        $this->assign('channel_list', $channelList);
        $this->assign('hash_channel', $hashChannel);

        return $this->fetch();
    }
}