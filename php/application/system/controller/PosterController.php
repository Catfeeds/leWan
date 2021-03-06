<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2019/2/15
 * Time: 9:50
 * 用户邀请好友海报控制器
 * 肖亚子
 */

namespace app\system\controller;

use app\common\AdminBaseController;
use think\Request;
use app\system\model\PosterModel;

class PosterController extends AdminBaseController{
    /**
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取邀请海报数据
     * 肖亚子
     */
    public function PosterList(){
        $List = PosterModel::PosterAll();

        $this->assign("data",$List);
        return $this->display('list', true);
    }

    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * (添加/修改)邀请海报信息
     * 肖亚子
     */
    public function PosterAppend(){

        if (Request::instance()->isPost()){
            $Id            = $this->post("id");
            $Poster        = $this->post("poster/a","");
            $Poster["pic"] = $this->post("pic", "");
            $Poster["addtime"] = time();

            $Condition["id"] = $Id;

            if ($Id){
                $PreRevision = PosterModel::PosterFind($Condition);
                $Data = PosterModel::PosterEdit($Condition,$Poster);
            }else{
                $Data = PosterModel::PosterAdd($Poster);
            }

            if ($Data === false){
                $this->toError('编辑失败');
            }else{
                if ($Id){
                    $this->log("修改海报信息：[海报ID:".$Id."]","sys_poster",$Condition,$PreRevision);
                }else{
                    $this->log("添加邀请海报");
                }

                $this->toSuccess('编辑海报成功', '', 2);
            }
        }

        $Id = $this->get("id");

        if ($Id){
            $Data = PosterModel::PosterFind(array("id"=>$Id));

            $this->assign("obj" ,$Data);
        }

        return $this->display('edit', false);
    }
}