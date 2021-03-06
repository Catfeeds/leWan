<?php
namespace app\system\controller;

use app\common\AdminBaseController;
use app\system\model\BranchModel;
use think\Request;
use think\Db;
use think\Session;
use app\common\RegExpression;
use app\common\Md5Help;
use app\common\SysHelp;
use app\system\model\AdminModel;
use app\system\model\BannerModel;

/**
 * 分公司管理
 * Enter description here ...
 * @author Administrator
 *
 */
class BranchController extends AdminBaseController
{


    /**
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取分公司管理员列表
     * 肖亚子
     */
    public function index(){
        //设置添加信息按钮
        $Psize    = $this->get("page", 1);
        $Title    = $this->get("title", "");
        $Provence = $this->get("provence_id", 0);
        $City     = $this->get("city_id", 0);
        $Status   = $this->get("status", 0);

        $Condition = array();
        $Condition["a.del"] = 1;

        if ($Title){
            $Condition["a.username|a.sub_name"] = array("like","%$Title%");
        }
        if ($Provence){
            $Condition["c.province_code"] = $Provence;
        }
        if ($Provence){
            $Condition["c.city_code"] = $City;
        }
        if ($Status){
            $Condition["a.status"] = $Status == 1?0:1;
        }

        $List = BranchModel::BranchList($Condition,$Psize,50);

        $this->assign("data",$List);
        $this->assign('addbtn',  $this->returnAddbtn('添加分公司', 'system/branch/add', 1, '40%', '70%'));
        return $this->display('index', true);
    }


    /**
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 添加分公司管理员账号
     * 肖亚子
     */
    public function add(){
        if (Request::instance()->isGet()){
            $Id = $this->get("id",0);

            if ($Id){
                $Condition["a.id"]  = $Id;
                $Condition["c.del"] = 1;
                $AdminFind = BranchModel::BranchFind($Condition);
                $RegionCity = self::City($AdminFind);
            }

            $this->assign("regioncity",$RegionCity);
            $this->assign("data",$AdminFind);
            $this->assign('action',  url('system/branch/add'));
            $this->assign('provence', $this->getProvenceList(1));

            return $this->display('edit');
        }else{
            $CityData = array();
            $Pid = $this->get("pid",0);
            $BranchData = $this->Receive();
            $Data       = $BranchData[0];
            $BranchCity = $BranchData[1];

            $Time = time();
            unset($Data["id"]);
            $Data["pid"]         = $Pid;
            $Data["create_time"] = date("Y-m-d H:i:s",$Time);

            if ($Pid){
                $MainAdminFind = BranchModel::BranchFinds(array("id"=>$Pid));

                if ($Data["status"] == 1 && $MainAdminFind["status"] == 0){
                    $Data["status"] = 0;
                }
            }

            $Aid = BranchModel::BranchAdd($Data);

            if (!$Aid){
                $this->toError('添加分公司管理员失败',"",2);
            }

            foreach ($BranchCity as $Key => $Val){
                $CityData[] = array("sub_id"=>$Aid,"province_code"=>$Val[0],"city_code"=>$Val[1],"addtime"=>$Time);
            }

            $CityAdd  = BranchModel::BranchCityAdd($CityData);

            $this->log("添加分公司管理员:[ID:".$Aid."],经营城市:".json_encode($BranchCity));
            $this->toSuccess('添加分公司管理员成功',"",2);
        }
    }


    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 分公司管理员编辑
     * 肖亚子
     */
    public function edit(){
        if (Request::instance()->isPost()){
            $Exp        = "";
            $CityData   = array();
            $BranchData = $this->Receive();
            $Data       = $BranchData[0];
            $BranchCity = $BranchData[1];
            $Aid        = $Data["id"];
            $Time       = time();

            $AdminCondition["id"] = $Data["id"];
            $AdminFind = BranchModel::BranchFinds($AdminCondition);

            if ($Data["password"]){
                $Data["password"] = func_user_hash($Data["password"],$AdminFind["hash"]);
            }
            if ($AdminFind["pid"] > 0){
                $MainAdminFind = BranchModel::BranchFinds(array("id"=>$AdminFind["pid"]));

                if ($Data["status"] == 1 && $MainAdminFind["status"] == 0){
                    $Data["status"] = 0;
                }
            }

            foreach ($BranchCity as $Key => $Val){
                $Condition["sub_id"]        = $Aid;
                $Condition["province_code"] = $Val[0];
                $Condition["city_code"]     = $Val[1];

                $CityFind = BranchModel::BranchCityFind($Condition);

                if ($CityFind){
                    if ($CityFind["del"] == 2){
                        BranchModel::BramchCityUpdate(array("sub_id"=>$Aid),array("del"=>1));
                    }
                }else{
                    $CityData[] = array("sub_id"=>$Aid,"province_code"=>$Val[0],"city_code"=>$Val[1],"addtime"=>$Time);
                }

                $Exp .= " and fc.province_code != {$Val[0]} and fc.city_code != {$Val[1]}";
            }

           $CityConditionUp[] = array("exp","(fa.id = {$Aid} or fa.pid = {$Aid})".$Exp);

           $CityAdd  = BranchModel::BranchCityAdd($CityData);
           $CityUp   = BranchModel::BranchUpdateAll($CityConditionUp,array("fc.del" => 2));
           $BranchUp = BranchModel::BranchUpdate($AdminCondition,$Data);

           if ($Data["status"] == 0 && $AdminFind["pid"] == 0){
                $SubUpdate = BranchModel::BranchUpdate(array("pid"=>$Aid),array("status"=>0));

                if ($SubUpdate){
                    $this->log("分公司主管理员账号禁用,下面子账号统一禁用:[主管理员ID:".$Aid."]");
                }
           }
           if ($BranchUp){
               $this->log("分公司管理员更改:[管理员ID:".$Aid."]","fgs_admin_user",$AdminCondition,$AdminFind);

           }

           $this->log("分公司管理员管理城市:[管理员ID:".$Aid."],所管城市:".json_encode($BranchCity));
           $this->toSuccess('编辑成功',"",2);
        }

        $Id = $this->get("id",0);
        parent::Tpl_Empty($Id,"此管理员不存在");

        $Condition["a.id"]  = $Id;
        $Condition["c.del"] = 1;
        $AdminFind = BranchModel::BranchFind($Condition);

        if (!$AdminFind){
            $this->toError("分公司管理员不存在");
        }

        if ($AdminFind["pid"]){
            $Condition["a.id"] = $AdminFind["pid"];
            $AdminpFind = BranchModel::BranchFind($Condition);
            $RegionCity = self::City($AdminpFind);
            $ThisCity   = self::City($AdminFind);

            $Result = array_reduce($ThisCity, function ($Result, $value) {
                $Result[] = $value["id"];
                return $Result;
            });
        }else{
            $RegionCity = self::City($AdminFind);
        }

        $this->assign("regioncity",$RegionCity);
        $this->assign("result",$Result);
        $this->assign("obj",$AdminFind);
        $this->assign('action',  url('system/branch/edit'));
        $this->assign('provence', $this->getProvenceList(1));

        return $this->display('edit');

    }

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 分公司管理员页面切换管理员状态
     * 肖亚子
     */
    public function switching(){
        if (Request::instance()->isAjax()){
            $Id     = $this->get("id");
            $Value = $this->get("value");

            $AdminFind = BranchModel::BranchFinds(array("id"=>$Id));

            if ($AdminFind["pid"] > 0){
                $MainAdminFind = BranchModel::BranchFinds(array("id"=>$AdminFind["pid"]));
                if ($MainAdminFind["status"] == 0){
                    return $this->ajaxReturn('Fail', 0, []);
                }
            }

            $AdminUp = BranchModel::BranchUpdate(array("id"=>$Id),array("status"=>$Value));

            if ($AdminUp){
                $this->log("分公司管理员状态切换:[ID".$Id."]","fgs_admin_user",array("id"=>$Id),$AdminFind);
                if ($AdminFind["pid"] == 0){
                    if ($Value == 0){
                        BranchModel::BranchUpdate(array("pid"=>$Id),array("status"=>0));
                        $this->log("分公司管理员状态切换为禁用,所有子管理员都禁用:[ID:".$Id."]");
                        return $this->ajaxReturn('Success', 2, []);
                    }
                }
                return $this->ajaxReturn('Success', 1, []);
            }else{
                return $this->ajaxReturn('Fail', 0, []);
            }
        }

    }

    /**
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 软删除分公司管理员
     * 肖亚子
     */
    public function delete(){
        if (Request::instance()->isGet()){
            $Id    = Request::instance()->param('id', 0);

            $AdminCondition["id"] = $Id;
            $AdminFind = BranchModel::BranchFinds($AdminCondition);
            if(empty($AdminFind)){
                $this->toError("管理员不存在");
            }else{
                $AdminUp = BranchModel::BranchUpdate(array("id"=>$Id),array("del"=>2));

                if ($AdminUp == false){
                    $this->toError("管理员删除失败");
                }else{
                    if ($AdminFind["pid"] == 0){
                        BranchModel::BranchUpdate(array("pid"=>$Id),array("del"=>2));
                        $this->log("软删除分公司主管理员,所有子管理员都软删:[ID:".$Id."]");
                    }

                }
                $this->log("软删除分公司管理员:[ID:".$Id."]","fgs_admin_user",array("id"=>$Id),$AdminFind);
            }

            $this->toSuccess('删除成功');
        }
    }

    /**
     * @return array
     * 获取分公司管理员提交信息
     * 肖亚子
     */
    private  function Receive(){
        $Data = $this->post("admin/a");
        $Code = $this->post("cityids/a");

        parent::Tpl_Empty($Data["sub_name"],"分公司名称不能为空");
        parent::Tpl_FullSpace($Data["sub_name"],"分公司名称不能为空");
        parent::Tpl_NoSpaces($Data["sub_name"],"分公司名称不能有空格");
        parent::Tpl_StringLength($Data["sub_name"],"公司名称2-50位",1,2,50);

        parent::Tpl_Empty($Data["username"],"管理员账号不能为空");
        parent::Tpl_FullSpace($Data["username"],"管理员账号不能为空");
        parent::Tpl_NoSpaces($Data["username"],"管理员账号不能为空");
        parent::Tpl_StringLength($Data["username"],"管理员账号2-20位",3,2,20);

        if ($Data["id"]){
            if ($Data["password"]){
                parent::Tpl_BackstagePwd($Data["password"],1);
            }
        }else{
            parent::Tpl_BackstagePwd($Data["password"],1);
            $Data["hash"]        = Func_Random(32);
            $Data["password"]    = func_user_hash($Data["password"],$Data["hash"]);
            $Data["create_time"] = time();
        }

        parent::Tpl_Empty($Code,"请选择分公司经营城市");

        $Area = array();

        foreach ($Code as $Key => $Val){
            $RegionCode = explode("_",$Val);
            $Area[$Key][] = $RegionCode[0];
            $Area[$Key][] = $RegionCode[1];
        }

        return array($Data,$Area);
    }

    /**
     * @param $Data  转换数据
     * @return array
     * 管理员经营城市组合
     */
    private function City($Data){
        $RegionCity = array();
        if ($Data["city"]){
            $Regionname = explode(",",$Data["regionname"]);
            $province   = explode(",",$Data["province"]);
            $City       = explode(",",$Data["city"]);

            foreach ($City as $Key => $Val){
                $RegionCity[$Key]["id"]    = $Val;
                $RegionCity[$Key]["pcode"] = $province[$Key];
                $RegionCity[$Key]["name"]  = $Regionname[$Key];
            }
        }

        return $RegionCity;
    }
}
