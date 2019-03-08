<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/19
 * Time: 17:00
 */

namespace app\system\controller;

use app\common\model\Currency;
use app\common\model\CurrencyAction;
use think\Request;
use think\Db;
use app\common\AdminBaseController;
use app\system\model\UserwithdrawModel;
use app\common\model\AccountRecordModel;
use app\system\model\ExcelModel;
use app\system\model\UserbankModel;

class UserwithdrawController extends AdminBaseController{


    public function CashList(){
        $Condition = array();//定义查询条件默认空

        $type    = $this->get("type",3);//提现类型
        $Status    = $this->get("status",0);//审核状态默认待审核
        $Psize     = $this->get("page",1);//当前分页页数默认第一页
        $Title     = $this->get("title");//搜索栏数据
        $StartTime = strtotime($this->get("starttime"));//提现开始时间
        $EndTime   = strtotime($this->get("endtime"));//提现结束时间
        $Excel       = $this->get('excel');//导出excel

        $Condition["w.withdraw_status"] = array("eq",$Status);

        if ($Title){
            $Condition["u.mobile|u.nickname"] = array("like","%$Title%");
        }
        if($type ==1){
            $withdraw_type = 1;
        }elseif($type ==2){
            $withdraw_type = 3;
        }else{
            $withdraw_type = 2;
        }
        $Condition["w.withdraw_type"] = $withdraw_type;
        $map["withdraw_type"] = $withdraw_type;

        $Condition = self::TimeContrast($StartTime,$EndTime,"w.withdraw_addtime",$Condition);

        if ($Excel){
            $ExcelList = self::UserBankExcel($Condition);

            if ($ExcelList){
                $Column    =  array("币种","日期","明细标志","顺序号","付款账号开户行","付款账号/卡号","付款账号名称/卡名称","收款账号开户行", "收款账号省份","收款账号地市",
                    "收款账号地区码","收款账号","收款账号名称","金额","汇款用途", "备注信息","汇款方式","收款账户短信通知手机号码","自定义序号");

                $em   = new ExcelModel();
                $Date = date("Y年m月d日H时i分");
                $em->export($Column,$ExcelList,"","支出明细",$Date."支出明细");
            }
        }

        $DataList = UserwithdrawModel::UserCashList($Condition,$Psize,50);
        $CountList = UserwithdrawModel::UserCashCount($map);

        if ($CountList){
            foreach ($CountList as $Key => $Val){
                switch ($Val["withdraw_status"]){
                    case 0:$Number["stay"]    = $Val["count"];break;
                    case 1:$Number["rebut"]   = $Val["count"];break;
                    case 2:$Number["adopt"]   = $Val["count"];break;
                    case 3:$Number["queue"]   = $Val["count"];break;
                    case 6:$Number["success"] = $Val["count"];break;
                    case 7:$Number["fail"]    = $Val["count"];break;
                }

            }
        }

        $Query     = array("title" => $Title);
        $Query     = self::Time($StartTime,"starttime",$Query);
        $Query     = self::Time($EndTime,"endtime",$Query);

        $this->assign("status",$Status);
        $this->assign("query",$Query);
        $this->assign("data",$DataList);
        $this->assign("number",$Number);
        $this->assign("type",$type);

        return $this->display('list', true);
    }

    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 申请提现详情查看/提现修改
     * 肖亚子
     */
    public function CashData(){
        $Condition = array();

        if(Request()->isGet()){
            $Id     = $this->get("id");
            $Status = $this->get("status",0);

            self::createToken();
            $Condition["w.withdraw_id"] = array("eq",$Id);
            $Condition["w.withdraw_type"] = array("eq",2);
            $UserBank = UserwithdrawModel::UserCashBank($Condition);

            $this->assign("id",$Id);
            $this->assign("status",$Status);
            $this->assign("data",$UserBank);
            return $this->display('cashdata', false);
        }else{
            $Id     = $this->post("id");
            $Status = $this->post("status");
            $Remark = $this->post("remark");
            $Token  = $this->post("token");

            parent::Tpl_NoSpaces($Status,"请选择提现流程");

            if ($Status == 1 || $Status == 7){
                parent::Tpl_NoSpaces($Remark,"请填写失败原因");
                parent::Tpl_StringLength($Remark,"失败原因10-40字",3,10,40);

                $Data["withdraw_reason"] = $Remark;
            }

            if(!self::CheckToken($Token)){
                $this->toSuccess("请勿重复操作", '', 2);
            }

            $FCondition["withdraw_id"] = array("eq",$Id);
            $FCondition["withdraw_status"] = array("eq",$Status);

            $Find = UserwithdrawModel::UserCashFind($FCondition);
            if ($Find){
                $this->toSuccess("请勿重复操作", '', 2);
            }

            if ($Status == 6){
                $Data["withdraw_code"] = "success";
            }elseif ($Status == 1 || $Status == 7){
                $Data["withdraw_code"] = "fail";
            }

//            if($Find['withdraw_type']==1 || $Find['withdraw_type']==3){
//                //微信支付宝提现 ，审核通过直接进入提现队列
//                if($Status==2){
//                    $Status = 3;$Change = "进入提现队列";
//                }
//            }

            $Condition["withdraw_id"] = array("eq",$Id);
            $Data["withdraw_status"]  = $Status;
            $Data["withdraw_uptime"]  = time();

            $Cash  =  UserwithdrawModel::TableName();
            $Cash->startTrans();//开启事务

            $CachUp = UserwithdrawModel::UserCashUpdate($Condition,$Data);

            switch ($Status){
                case 1:$Change = "提现驳回";break;
                case 2:$Change = "审核通过";break;
                case 3:$Change = "进入提现队列";break;
                case 6:$Change = "提现成功";break;
                case 7:$Change = "提现失败";break;
            }

            if ($CachUp) {

                 if ($Status == 1 || $Status == 7){

                    $DataFind = UserwithdrawModel::UserCashFind($Condition);
                    $Uid      = $DataFind["user_id"];
                    $Money    = $DataFind["withdraw_amount"];

                     $CurrencyAction =CurrencyAction::CashWithdrawFaillBack;
                     //进行用户提现退回资金操作数据
                     $Acc  = new AccountRecordModel();
                     $AcUp = $Acc->add($Uid, 0, Currency::Cash,$CurrencyAction,$Money,$Acc->getRecordAttach(0, 0, 0), '用户提现失败退回');

                    if($AcUp){
                        $Cash->commit();//成功提交事务
                        $this->log("用户提现失败：[ID:".$Id."提现流程更改为".$Change."退回金额".$DataFind["withdraw_amount"]."]");
                    }else{
                        $Cash->rollback();//失败回滚exit;
                        $this->toError("用户提现失败");
                    }
                 }elseif ($Status == 6){
                     $DataFind =  UserwithdrawModel::UserCashFind($Condition);
                     //增加提现统计
                     $res = Db::name('account_finance')->where(['user_id'=>$DataFind["user_id"], 'finance_tag'=>0])->update(['finance_withdraw'=>['exp','finance_withdraw+'.$DataFind["withdraw_amount"]]]);
                     $today = Db::name('account_finance')->where(['user_id'=>$DataFind["user_id"], 'finance_tag'=>date('Ymd')])->find();
                     if($today){
                         Db::name('account_finance')->where(['user_id'=>$DataFind["user_id"], 'finance_tag'=>date('Ymd')])->update(['finance_withdraw'=>['exp','finance_withdraw+'.$DataFind["withdraw_amount"]], 'finance_uptime'=>time()]);
                     }else{
                         Db::name('account_finance')->where(['user_id'=>$DataFind["user_id"], 'finance_tag'=>date('Ymd')])->insert(['finance_withdraw'=>$DataFind["withdraw_amount"],'user_id'=>$DataFind["user_id"], 'finance_tag'=>date('Ymd'), 'finance_uptime'=>time()]);
                     }
                     $month = Db::name('account_finance')->where(['user_id'=>$DataFind["user_id"], 'finance_tag'=>date('Ym')])->find();
                     if($month){
                         Db::name('account_finance')->where(['user_id'=>$DataFind["user_id"], 'finance_tag'=>date('Ym')])->update(['finance_withdraw'=>['exp','finance_withdraw+'.$DataFind["withdraw_amount"]], 'finance_uptime'=>time()]);
                     }else{
                         Db::name('account_finance')->where(['user_id'=>$DataFind["user_id"], 'finance_tag'=>date('Ym')])->insert(['finance_withdraw'=>$DataFind["withdraw_amount"],'user_id'=>$DataFind["user_id"], 'finance_tag'=>date('Ym'),'finance_uptime'=>time()]);
                     }
                     if($res !== false){
                         $Cash->commit();//成功提交事务
                         $this->log("用户提现成功：[ID:".$Id."提现流程更改为".$Change."支出总额加".$DataFind["withdraw_amount"]."]");
                     }else{
                         $Cash->rollback();//失败回滚exit;
                         $this->toError("用户提现失败");
                     }
                 }

                $Cash->commit();//成功提交事务
                $this->log("用户提现：[ID:".$Id."提现流程更改为".$Change."]");
                $this->toSuccess("用户提现流程更改成功", '', 2);
            }else{
                $Cash->rollback();//失败回滚exit;
                $this->toError("用户提现失败");
            }

        }
    }

    /**
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 提现批量通过
     */
    public function CashAdopt(){
        $Id     = input("post.idstr");
        $Id     = explode(",",$Id);
        $Status = input("get.status");
        $type = input("get.type");

        switch ($Status){
            case 0:$Status = 2;$Change = "审核通过";break;
            case 2:$Status = 3;$Change = "进入提现队列";break;
            case 3:$Status = 6;$Change = "提现成功";break;
        }
//
//        if($type==1 || $type==2){
//            //微信支付宝提现 ，审核通过直接进入提现队列
//            if($Status==2){
//                $Status = 3;$Change = "进入提现队列";
//            }
//        }

        foreach ($Id as $Key => $Val){
            if ($Val){
                $Data["withdraw_uptime"]  = time();
                $Data["withdraw_status"]  = $Status;

                if ($Status == 6){
                    $Data["withdraw_code"]  = "success";
                }

                UserwithdrawModel::UserCashUpdate(array("withdraw_id"=>array("eq",$Val)),$Data);
                $this->log("用户提现：[ID:".$Val."提现流程更改为".$Change."]");
            }
        }

        $this->toSuccess('批量通过成功');
    }

    /**
     * @param $Condition 查询条件
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 银行体现,提现银行卡信息查询
     */
    private function UserBankExcel($Condition){
        $Condition["withdraw_type"]   = array("eq",2);
        $Condition["withdraw_status"] = array("eq",3);

        $ExcelList = UserwithdrawModel::UserBankListExcel($Condition);

        $Cash = array();
        $Date = date("Ymd",time());
        if ($ExcelList){
            $Empty["currency"]  = "";
            $Empty["date"]      = "";
            $Empty["detailed"]  = "";
            $Empty["key"]       = "";
            $Empty["payer"]     = "";
            $Empty["payment_number"] = "";
            $Empty["payment_name"] = "";
            $Empty["bank_name"] = "";
            $Empty["province"] = "";
            $Empty["city"] = "";
            $Empty["area"] = "";
            $Empty["account_number"] = "";
            $Empty["account_name"] = "";
            $Empty["withdraw_realamount"] = "";
            $Empty["purpose"] = "";
            $Empty["remarks"] = "";
            $Empty["status"]  = "";
            $Empty["account_tel"] = "";
            $Empty["k"] = "";
            $Cash[] = $Empty;

            foreach ($ExcelList as $Key => $Val){
                $Data["currency"]            = "RMB";
                $Data["date"]                = $Date;
                $Data["detailed"]            = "";
                $Data["key"]                 = $Key;
                $Data["payer"]               = "工商银行";
                $Data["payment_number"]      = "4402072209100031756";
                $Data["payment_name"]        = "成都车书企业管理有限公司";
                $Data["bank_name"]           = $Val["bank_name"];
                $Data["province"]            = UserbankModel::UserBankArea($Val["province"]);
                $Data["city"]                = UserbankModel::UserBankArea($Val["city"]);

                if($Val["bank_id"] == 2){
                    $Area = mb_substr($Val["account_number"] , 6 , 4);
                    $Data["area"]            = $Area;
                }else{
                    $Data["area"]            = "0000";
                }

                $Data["account_number"]      = $Val["account_number"];
                $Data["account_name"]        = $Val["account_name"];
                $Data["withdraw_realamount"] = $Val["withdraw_realamount"];
                $Data["purpose"]             = "其它";
                $Data["remarks"]             = "";
                $Data["status"]              = 0;
                $Data["account_tel"]         = $Val["account_tel"];
                $Data["k"]                   = 1;

                $Cash[] = $Data;
            }
        }

        return $Cash;
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

    /**
     * 生成防止重复提交token
     */
    function createToken() {
        $Code = chr(mt_rand(0xB0, 0xF7)) . chr(mt_rand(0xA1, 0xFE)) .       chr(mt_rand(0xB0, 0xF7)) . chr(mt_rand(0xA1, 0xFE)) . chr(mt_rand(0xB0, 0xF7)) . chr(mt_rand(0xA1, 0xFE));
        session('TOKEN', self::Authcode($Code));
    }

    /**
     * @param $str    加密串
     * @return string
     * token加密
     */
    function Authcode($Str) {
        $Key = "YOURKEY";
        $Str = substr(md5($Str), 8, 10);
        return md5($Key . $Str);
    }

    /**
     * @param $Token
     * @return bool
     * token验证判断
     */
    function CheckToken($Token) {
        if ($Token == session('TOKEN')) {
            session('TOKEN', NULL);
            return TRUE;
        } else {
            return FALSE;
        }
    }

}