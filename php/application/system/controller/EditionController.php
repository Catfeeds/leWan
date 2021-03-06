<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2019/1/28
 * Time: 11:47
 * 版本管理控制器
 * 肖亚子
 */
namespace  app\system\controller;

use app\common\AdminBaseController;
use Think\Db;

class EditionController extends AdminBaseController{

    /**
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取全部版本数据
     * 肖亚子
     */
    public function EditionList(){

        $List = Db::name("sys_edition")->order("id desc")->select();


        $this->assign("list",$List);
        return $this->display("list",true);
    }

    /**
     * @return string
     * 添加版本型号
     * 肖亚子
     */
    public function EditionEdit(){
        if (request()->isPost()){
            $Type          = $this->post("type","");
            $Status        = $this->post("status","");
            $VersionNumber = $this->post("versionnumber","");
            $Num           = $this->post("num","");
            $Url           = $this->post("url","");
            $Explain       = $this->post("explain","");

            parent::Tpl_Empty($VersionNumber,"请输入版本型号");
            parent::Tpl_FullSpace($VersionNumber,"请输入正确版本型号");

            if($Type == 2){
                parent::Tpl_Integer($Num,"请输入正确的安卓版本号");
                parent::Tpl_Empty($Url,"请输入更新安装包链接");
                parent::Tpl_FullSpace($Url,"请输入正确更新安装包链接");
            }

            parent::Tpl_Empty($Explain,"请输入更新说明");
            parent::Tpl_FullSpace($Explain,"请输入正确更新说明");
            parent::Tpl_StringLength($Explain,"更新说明不能少于5字",1,5);

            $FindNum = Db::name("sys_edition")->order("addtime desc")->value("num");

            if ($Type == 2 && $FindNum === true && $FindNum < $Num){
                $this->toError("安卓版本号不能小于上一个版本号");
            }

            $Data["versionnumber"] = $VersionNumber;
            $Data["num"]           = $Num;
            $Data["url"]           = $Url;
            $Data["explain"]       = $Explain;
            $Data["status"]        = $Status;
            $Data["type"]          = $Type;
            $Data["addtime"]       = time();

            $Add = Db::name("sys_edition")->insert($Data);

            if ($Add){
                $this->log("添加新版本");
                $this->toSuccess('添加成功', '', 2);
            }else{
                $this->toError("添加失败");
            }
        }

        return $this->display("edit",false);
    }
}