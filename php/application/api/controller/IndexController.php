<?php

namespace app\api\controller;

use app\api\controller\ApiBaseController;
use app\api\model\CommissionModel;
use app\api\model\HelpModel;
use app\api\model\UserUpgradeModel;
use think\Db;


class IndexController extends ApiBaseController {

    /**
     * 单页面富文本内容获取
     */
    public function page(){
        $pageid = $this->post('pageid', 0);
        $page = Db::name('page')->find($pageid);
        $this->returnApiData('获取成功', 200, $page);
    }


    /**
     * 提现规则说明
     */
    public function withdrawRule(){
        $type = $this->post('type', 1);//1微信提现；2支付宝；3银行卡
        $config = Db::name('parameter')->column('value', 'key');
        $data['tixian_min'] = $config['tixian_min'];
        $data['tixian_bei'] = $config['tixian_bei'];
        if($type == 1){
            $data['tixian_shuoming'] = $config['tixian_wxsm'];
        }elseif($type == 2){
            $data['tixian_shuoming'] = $config['tixian_zfbsm'];
        }elseif($type == 3){
            $data['tixian_shuoming'] = $config['tixian_yhksm'];
        }
        $this->returnApiData('获取成功', 200, $data);
    }

    public function SwitchInterface(){
        $Data = Db::name("sys_config")->field("value")->where(array("field"=>"iosview"))->find();

        $this->returnApiData('获取成功', 200, $Data);
    }

}