<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/25
 * Time: 14:11
 * 申请换货控制器
 * 肖亚子
 */
namespace app\system\controller;
use app\common\AdminBaseController;
use think\Request;
use think\Db;
use think\Session;
use app\system\model\OrderbarterModel;

class OrderbarterController extends AdminBaseController{

    /**
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取订单换货数据
     * 肖亚子
     */
    public function BarterList(){

        $Condition = array();
        $Page      = $this->get('page', 1);//分页默认第一页
        $Status    = $this->get('status', 0);
        $Title     = $this->get('title', '');
        $Type      = $this->get('type', 0);
        $StartTime = strtotime($this->get("starttime"));
        $EndTime   = strtotime($this->get("endtime"));

        if ($Status){
            $Condition["b.barter_status"] = $Status;
        }
        if ($Title){
            $Condition["o.order_no|u.nickname|u.mobile|m.merchant_name"] = array("like","%$Title%");
        }
        if ($Type){
            $Condition["b.barter_status"] = $Type;
        }
        $Condition = self::ContrastTime($StartTime,$EndTime,"b.barter_addtime",$Condition);

        $List = OrderbarterModel::OrderBarterList($Condition,$Page,50);

        $Transference = self::Transference()[0];

        foreach ($List["list"] as $Key=>$Val){
            $List["list"][$Key]["statuscss"]  = $Transference[$Val["barter_status"]]["css"];
            $List["list"][$Key]["statusname"] = $Transference[$Val["barter_status"]]["name"];
        }

        $Count["whole"]    = OrderbarterModel::BarterCount($Condition,null,0,$Status);
        $Count["apply"]    = OrderbarterModel::BarterCount($Condition,array("b.barter_status"=>1),1,$Status);
        $Count["reject"]   = OrderbarterModel::BarterCount($Condition,array("b.barter_status"=>2),2,$Status);
        $Count["adopt"]    = OrderbarterModel::BarterCount($Condition,array("b.barter_status"=>3),3,$Status);
        $Count["sendback"] = OrderbarterModel::BarterCount($Condition,array("b.barter_status"=>4),4,$Status);
        $Count["collect"]  = OrderbarterModel::BarterCount($Condition,array("b.barter_status"=>5),5,$Status);
        $Count["deliver"]  = OrderbarterModel::BarterCount($Condition,array("b.barter_status"=>6),6,$Status);
        $Count["complete"] = OrderbarterModel::BarterCount($Condition,array("b.barter_status"=>7),7,$Status);

        $Query = array("title" => $Title,"type"=>$Type);
        $Query = self::Time($StartTime,"starttime",$Query);
        $Query = self::Time($EndTime,"endtime",$Query);

        $this->assign("query",$Query);
        $this->assign("status", $Status);
        $this->assign("data",$List);
        $this->assign("count",$Count);
        return $this->display("list",true);
    }

    /**
     * @return array
     * 换货状态转中文
     * 肖亚子
     */
    private function Transference(){
        $BarterStatus = array("1" => array("css" => "layui-bg-gray","name" => "申请中"),
                            "2" => array("css" => "","name" => "驳回"),
                            "3" => array("css" => "layui-bg-black","name" => "待寄回"),
                            "4" => array("css" => "layui-bg-green","name" => "已寄回"),
                            "5" => array("css" => "layui-bg-green","name" => "商家收货"),
                            "6" => array("css" => "layui-bg-blue","name" => "商家已发货"),
                            "7" => array("css" => "layui-bg-green","name" => "收货完成"),);

        return array($BarterStatus);
    }

    /**
     * @param $StartTime  开始时间
     * @param $EndTime    结束时间
     * @param $Key        字段
     * @param $Condition  返回组合
     * @return mixed
     */
    private  function ContrastTime($StartTime,$EndTime,$Key,$Condition){

        if (!empty($StartTime) && empty($EndTime)) {
            parent::Tpl_NotGtTime($StartTime,"开始时间不能大于当前时间"); //开始时间不为空和当前时间对比
            $Condition[$Key] = array(array('egt', $StartTime));
        } else if (empty($StartTime) && !empty($EndTime)) {
            parent::Tpl_NotGtTime($EndTime,"结束时间不能大于当前时间"); //结束时间不为空和当前时间对比
            $Condition[$Key] = array(array('lt', $EndTime));
        } else if (!empty($StartTime) && !empty($EndTime)) {
            parent::Tpl_TimeContrast1($StartTime,$EndTime); //开始和结束时间都不为空进行判断
            $Condition[$Key] = array(array('egt', $StartTime), array('elt', $EndTime));
        }

        return $Condition;
    }

    /**
     * @param $Time  转换时间
     * @param $Key   返回字段
     * @param $Query 组合数组
     * @return mixed
     */
    private function Time($Time,$Key,$Query){
        if(!empty($Time)){
            $Query[$Key] = date("Y-m-d H:i:s",$Time);
        }

        return $Query;
    }

}