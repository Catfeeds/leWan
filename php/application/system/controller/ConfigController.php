<?php

namespace app\system\controller;

use app\common\AdminBaseController;
use think\Request;
use think\Db;
use think\Log;
use think\Config;
use think\Cache;
use think\Session;
use app\common\RegExpression;
use app\common\Md5Help;
use app\common\SysHelp;

/**
 * 系统入口
 * Enter description here ...
 * @author Administrator
 *
 */
class ConfigController extends AdminBaseController {

    /**
     * 首页
     * Enter description here ...
     */
    public function index() {
        if(isset($_POST['tab'])){
            $tab = intval($_POST['tab']);
            $data = Request::instance()->post();
            foreach ($data as $k=>$v){
                $sw['field'] = $k;
                $sw['tab'] = $tab;
                $new['value'] = $v;
                if(isset($_POST[$k.'_alt'])){
                    $new['alt'] = $_POST[$k.'_alt'];
                }
                $PreRevision = Db::name("sys_config")->where($sw)->find();
                $Up          = Db::name('sys_config')->where($sw)->update($new);

                if ($Up){
                    $this->log("修改服务号配置信息：[标识:".$k."]","sys_config",$sw,$PreRevision);
                }

            }
            $this->log("修改服务号配置");
            $this->success('更新成功， 请清空缓存', url('config/index', array('tab'=>$tab)));
        }
        $tab = intval($this->get('tab', 1));
        $this->assign('tabnow', $tab);
        
        //设置添加信息按钮
        $config = Db::name('sys_config')->order('sort asc')->where(['status'=>1])->select();
        foreach ($config as $k=>$v){
            if($v['type'] == 'radio'){
                $config[$k]['options'] = json_decode($v['alt']);
            }
        }
        $this->assign('config', $config);

        //fuck($config);
        return $this->display('index/config', true);
    }


    /**
     * 佣金设置
     */
    public function commission(){
        if(Request::instance()->isPost()){
            $_POST['pyj_switch'] = intval($_POST['pyj_switch']);
            foreach ($_POST as $k=>$v){

                $Condition["key"] = $k;

                $PreRevision = Db::name("parameter")->where($Condition)->find();
                $Data        = Db::name('parameter')->where(['key'=>$k])->update(['value'=>$v]);

                if ($Data){
                    $this->log("修改佣金信息：[佣金Key:".$k."]","parameter",$Condition,$PreRevision);
                }
            }

            $this->log("修改佣金信息");
            $this->success('更新成功');
        }
        $config = Db::name('parameter')->column('key,value');
        $this->assign('obj',$config);
        return $this->display('index/commission', true);
    }
}
