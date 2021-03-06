<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2019/2/25
 * Time: 13:56
 * 城市小区控制器
 * 肖亚子
 */
namespace app\System\controller;

use app\common\AdminBaseController;
use think\Request;
use app\system\model\CommunityModel;

class CommunityController extends AdminBaseController {

    /**
     * @return string
     * 获取所有小区
     * 肖亚子
     */
    public function CommunityList(){
        $Page     = $this->get("page", 1);
        $Title    = $this->get("title", "");
        $Provence = $this->get("provence_id", 0);
        $City     = $this->get("city_id", 0);
        $Area     = $this->get("area_id", 0);
        $stauts     = $this->get("stauts", 0);
        $Condition = array();

        $Condition["del"] = 0;

        if ($Title){
            $Condition["rc.community_name|r.fullname"] = array("like","%$Title%");
        }
        if($Provence && $City && $Area){
            $Condition["r.id"] = array("eq",$Area);
            $Condition["r.parentid"] = array("eq",$City);
        }elseif ($Provence && $City && !$Area){
            $Condition["r.parentid"] = array("eq",$City);
        }

        if($stauts == 1){
            $Condition['rc.status'] = 1;
        }elseif($stauts == 2){
            $Condition['rc.status'] = 0;
        }
        $List  = CommunityModel::CommunityList($Condition,$Page,100);
        $Query = array("title"=>$Title,"provence"=>$Provence,"city"=>$City,"area"=>$Area);

        $this->assign("data",$List);
        $this->assign("query",$Query);
        return $this->display("list",true);
    }

    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 编辑小区信息
     * 肖亚子
     */
    public function CommunityEdit(){
        $Provence_id = 0;
        $City_id     = 0;

        if (Request::instance()->isPost()){
            $Id       = $this->post("community_id",0);
            $Name     = $this->post("community_name","");
            $Area     = $this->post("area_id", 0);
            $status     = $this->post("status", 0);

            parent::Tpl_Empty($Id,"编辑失败");
            parent::Tpl_Empty($Name,"请输入小区名");
            parent::Tpl_FullSpace($Name,"请输入小区名");
            parent::Tpl_Empty($Area,"请选择小区");

            $Condition["community_id"] = $Id;
            
            $Data["community_name"] = $Name;
            $Data["district_id"]    = $Area;
            $Data["status"]         = $status;

            $PreRevision = CommunityModel::CommunityFind($Condition);
            $UpData      = CommunityModel::CommunityUpdate($Condition,$Data);

            if($UpData){
                $this->log("修改小区信息：[小区ID:".$Id."]","region_community",$Condition,$PreRevision);
                $this->toSuccess('编辑成功', '', 2);
            }else{
                $this->toError('编辑失败');
            }
        }

        $Id = $this->get("id",0);

        if ($Id){
            $Data = CommunityModel::CommunityFind(array("community_id"=>$Id));

            if ($Data){
                $City        = CommunityModel::CommunityArea(array("id"=>$Data["district_id"]));
                $Provence    = CommunityModel::CommunityArea(array("id"=>$City["parentid"]));
                $Provence_id = $Provence["parentid"];
                $City_id     = $Provence["id"];
            }
        }
        $Query = array("provence"=>$Provence_id,"city"=>$City_id,"area"=>$Data["district_id"]);

        $this->assign("data",$Data);
        $this->assign("query",$Query);
        $this->assign('provence', $this->getProvenceList());
        $this->assign('city', $this->getCityList($Provence_id));
        $this->assign('area', $this->getAreaList($City_id));
        return $this->display("edit",false);
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除小区
     */
    public function CommunityDel(){
        $Id = $this->get("id",0);

        if (!$Id){
            $this->toSuccess('请选择要删除的小区', '', 2);
        }

        $Condition["community_id"] = $Id;

        $Up = CommunityModel::CommunityUpdate($Condition,array("del"=>1));

        if($Up){
            $this->log('删除小区:[小区ID:'.$Id."]");
            $this->toSuccess('删除成功', url("Community/CommunityList"), 1);
        }else{
            $this->toError('删除失败');
        }

    }
}