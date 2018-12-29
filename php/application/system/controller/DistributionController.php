<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/10
 * Time: 17:05
 */

namespace app\system\controller;


use app\common\AdminBaseController;
use app\common\model\Levelconst;
use app\common\model\ProcedureModel;
use app\system\model\PaginationModel;
use think\Db;
use app\system\model\DistributionModel;
use think\Request;

/**
 * 后台导入第三方平台给的电子码
 * Class FinanceController
 * @package app\system\controller
 */
class DistributionController extends AdminBaseController
{
    /**
     * @return string
     * 获取分销电子码列表
     * 肖亚子
     */
    public function index(){
        $Page  = $this->get("page", 1);
        $Title = $this->get("title", '');

        $Condition = array();
        $Condition["p.distributiontag"] = array("eq",1);

        if($Title){
            $Condition["p.product_name|m.merchant_alias"] = array("like","%{$Title}%");
            $this->assign("title", $Title);
        }

        $Data = DistributionModel::ProductCodeList($Condition,$Page);

        $this->assign('data', $Data);
        return $this->display('index', true);
    }


    /**
     * 待发码订单
     * 只查询商品表distributiontag=1的商品的 已付款未砝码订单=>where o.order_status>1 and o.distributionsendcode = 0 and p.distributiontag=1
     * 列表页面支持多选，点击按钮，打开弹窗输入短信内容，发送短信，更新订单distributionsendcode=1
     */
    public function order(){
       if (Request::instance()->isGet()){
           $Page = $this->get("page",1);
           $Title = $this->get("title", '');

           $Condition = array();

           $Condition["o.order_status"] = array("eq",2);
           $Condition["o.distributionsendcode"] = array("neq",1);
           $Condition["p.distributiontag"] = array("eq",1);

           $Data = DistributionModel::OrderDistributionList($Condition,$Page,20);

           $this->assign('data', $Data);
           return $this->display('orderlist', true);
       }
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 订单确认发码
     * 肖亚子
     */
    public function OrderHairCode(){
        if (Request::instance()->isPost()){
            $Order_Id = $this->post("order_id");
            $Message  = $this->post("message");

            parent::Tpl_Empty($Message,"请输入推送内容");
            parent::Tpl_FullSpace($Message,"请输入推送内容");

            $Condition["o.order_id"]             = array("eq",$Order_Id);
            $Condition["o.order_status"]         = array("eq",2);
            $Condition["o.distributionsendcode"] = array("neq",1);
            $Condition["p.distributiontag"]      = array("eq",1);

            $Order = DistributionModel::OrderDistributionFind($Condition);

            if (!$Order){
                $this->toError("订单已发码或者未找该订单");
            }

            $CodeList = DistributionModel::ProductDistributionCodeList($Order["product_id"]);

            if (count($CodeList) == 0){
                $this->toError("消费码已经用尽,请补充消费码");
            }

            if (count($CodeList) < $Order["num"]){
                $this->toError("消费码不足用以发码,请补充消费码");
            }

            $CodeList = array_chunk($CodeList,$Order["num"])[0];

            $Data["order_id"] = $Order_Id;
            $Data["mobile"]   = $Order["order_mobile"];
            $Code  = "";
            $Cash  =  DistributionModel::TableName();
            $Cash->startTrans();//开启事务

            foreach ($CodeList as $Key => $Val){
                $CodeFind = DistributionModel::ProductDistributionCodeFind($Val["id"]);

                if (!$CodeFind){
                    $Cash->rollback();//失败回滚exit;
                    $this->toError("请勿频换操作");
                    break;
                }

                $CodeUp = DistributionModel::ProductDistributionCodeUp($Val["id"],$Data);

                if ($CodeUp === false){
                    $Cash->rollback();//失败回滚exit;
                    $this->toError("订单发码失败,请重新发码");
                    break;
                }
                $Code .="【".$Val["consome_code"]."】";
            }

            $OrderUp = DistributionModel::OrderUpdate($Order_Id);

            if ($OrderUp === false){
                $Cash->rollback();//失败回滚exit;
                $this->toError("订单发码失败,请重新发码");
            }

            $Message = "【乐玩联盟】尊敬的客户，您有".$Order["num"]."份电子消费码".$Code.$Message;

            sendSmsCdxx($Order["order_mobile"],$Message,true);

            $Cash->commit();//成功提交事务
            $this->log("用户订单发码：[ID:".$Order_Id."]");
            $this->toSuccess("订单发码成功");
        }
    }


}