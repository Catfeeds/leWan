<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2019/1/21
 * Time: 17:50
 * 取消预约申请控制器
 * 肖亚子
 */
namespace  app\system\controller;

use app\api\model\JpushModel;
use app\api\model\OpenTmModel;
use app\common\AdminBaseController;
use app\system\model\ReservationcancelModel;

class ReservationcancelController extends AdminBaseController{

    /**
     * @return string
     * 取消预约列表
     * 肖亚子
     */
    public function CancelList(){
        $Condition = array();
        $Psize     = $this->get("page",1);//当前分页页数默认第一页
        $Title     = $this->get("title");//搜索栏数据
        $Status    = $this->get("status",0);//审核状态默认待审核
        $StartTime = strtotime($this->get("starttime"));//提现开始时间
        $EndTime   = strtotime($this->get("endtime"));//提现结束时间

        if($Title){
            $Condition["o.order_no|o.order_fullname|o.order_mobile|c.consume_code|m.merchant_alias|f.merchant_alias|u.nickname|u.username"] = array("like","%$Title%");
        }

        if ($Status){
            $Condition["rc.status"] = $Status;
        }

        $Condition = self::TimeContrast($StartTime,$EndTime,"rc.addtime",$Condition);

        $List = ReservationcancelModel::UserCancelList($Condition,$Psize,50);

        $Transference = self::Transference();

        if ($List){
            foreach ($List["list"] as $Key => $Val){
                $List["list"][$Key]["statuscss"]  = $Transference[$Val["rcstatus"]]["css"];
                $List["list"][$Key]["statusname"] = $Transference[$Val["rcstatus"]]["name"];
                if ($Val["rcstatus"] == 1){
                    if ($Val["status"] == 1){
                        if ($Val["reservation_status"] == 1 || $Val["reservation_status"] == 3){
                            $List["list"][$Key]["status"] = 2;
                        }else{
                            $List["list"][$Key]["status"] = 1;
                        }
                    }else{
                        $List["list"][$Key]["status"] = 1;
                    }
                }else{
                    $List["list"][$Key]["status"] = 1;
                }
            }
        }
        $Query     = array("title" => $Title,"status" =>$Status);
        $Query     = self::Time($StartTime,"starttime",$Query);
        $Query     = self::Time($EndTime,"endtime",$Query);

        $this->assign("data",$List);
        $this->assign("query",$Query);
        return $this->display("list",true);
    }

    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 取消预约编辑
     * 肖亚子
     */
    public function CancelRevise(){

        if (Request()->isGet()){
            $Id  = $this->get("id");
            $RId = $this->get("rid");

            $this->assign("id",$Id);
            $this->assign("rid",$RId);
            return $this->display("cancel",false);
        }else{
            $Id     = $this->post("id");
            $RId    = $this->get("rid");
            $Status = $this->post("status");
            $Remark = $this->post("remark");

            if ($Status == 1){
                parent::Tpl_Empty($Remark,"请填写驳回原因");
                parent::Tpl_FullSpace($Remark,"请填写驳回原因");
                parent::Tpl_StringLength($Remark,"失败原因10-40字",3,10,40);
                $Condition["id"] = $Id;

                $CancelData["status"]  = 3;
                $CancelData["bremark"] = $Remark;

                $Data = ReservationcancelModel::UserCancelUpdate($Condition,$CancelData);

                if ($Data === false){
                    $this->toError("审核失败,请重新操作");
                }

            }else{
                $Cash  =  ReservationcancelModel::TableName();
                $Cash->startTrans();//开启事务

                $Condition["id"]      = $Id;
                $CancelData["status"] = 2;
                $ReservationCondition["reservation_id"] = $RId;
                $ReservationData["reservation_status"]  = 4;

                $Data          = ReservationcancelModel::UserCancelUpdate($Condition,$CancelData);
                $ReservationUp = ReservationcancelModel::UserReservationUpdate($ReservationCondition,$ReservationData);

                if ($Data === false){
                    $Cash->rollback();//失败回滚exit;
                    $this->toError("审核失败,请重新操作");
                }

                if ($ReservationUp === false){
                    $Cash->rollback();//失败回滚exit;
                    $this->toError("审核失败,请重新操作");
                }

                $Cash->commit();//成功提交事务
            }

            self::WeChatUserMsg($Id,$Status,$Remark);

            $Name = $Status == 1?"申请驳回":"申请通过";
            $this->log("用户取消预约：[ID:".$Id."审核状态:".$Name."]");
            $this->toSuccess("用户取消预约审核成功", '', 2);
        }

    }

    /**
     * @param $Id      取消预约id
     * @param $Status  审核状态
     * @param $Remark  驳回原因
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 用户取消预约,审核完毕给用户发送微信推广消息
     * 肖亚子
     */
    private  function WeChatUserMsg($Id,$Status,$Remark){

        $Condition["rc.id"]        = $Id;
        $Condition["u.reg_type"]   = 1;
        $Condition["u.status"]     = 1;
        $Condition["uc.subscribe"] = 1;
        $Condition["uc.platform"]  = "wechat";

        $Data        =  ReservationcancelModel::UserCancelOrder($Condition);
        $AccessToken = Db::name('access_token')->value("access_token");

        if ($Data){
            if ($Status == 1){
                $Remark = "您的取消预约申请被驳回,驳回原因:".$Remark;
            }else{
                $Remark = "您的取消预约申请被通过,您可以到预约中心重新预约啦.";
            }
            if($Data['deviceid']){ //极光推送
                $jpush['title'] ="您的取消预约,最新结果";
                $jpush['alert'] = $Remark;
                $option['type'] = JpushModel:: JPUSH_MSG_ORDER;
                $option['order_id'] = $Data['order_id'];
                $jpush['platform'] =  'all';
                JpushModel::sendMsgSpecial($Data['deviceid'],$jpush,$option);
            }


            $Open = New OpenTmModel();
            $MsgData["title"]    = "您的取消预约,最新结果";
            $MsgData["keyword1"] = $Data["order_no"];
            $MsgData["keyword2"] = $Data["merchant_name"];
            $MsgData["keyword3"] = date("Y年m月d日 H:i",$Data["order_addtime"]);
            $MsgData["remark"]   = $Remark;

            $Open->sendTplmsg8($Data["openid"],$MsgData,$AccessToken,"");
        }
    }
    /**
     * @return array
     * 预约状态转中文
     * 肖亚子
     */
    private function Transference(){

        $Status  = array("1" => array("css" => "layui-bg-red","name" => "申请中"),
            "2" => array("css" => "layui-bg-green","name" => "完成"),
            "3" => array("css" => "layui-bg-red","name" => "驳回"),
            "4" => array("css" => "layui-bg-black","name" => "取消预约"));

        return $Status;
    }

    /**
     * @param $StartTime  开始时间
     * @param $EndTime    结束时间
     * @param $Key        字段
     * @param $Condition  返回组合
     * @return mixed
     */
    public  function TimeContrast($StartTime,$EndTime,$Key,$Condition){

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
    public function Time($Time,$Key,$Query){
        if(!empty($Time)){
            $Query[$Key] = date("Y-m-d H:i:s",$Time);
        }

        return $Query;
    }
}