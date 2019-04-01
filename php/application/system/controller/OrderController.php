<?php

/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/22
 * Time: 10:26
 * 肖亚子
 * 订单控制器
 */

namespace app\system\controller;

use app\common\AdminBaseController;
use app\common\model\AccountFinanceModel;
use app\common\model\AccountRecordModel;
use app\common\model\Currency;
use app\common\model\CurrencyAction;
use app\common\model\Paymodel;
use app\system\model\ExcelModel;
use think\Request;
use think\Db;
use think\Session;
use app\system\model\OrderModel;
use app\system\model\FinanceModel;

class OrderController extends AdminBaseController {

    /**
     * @return string
     * 获取订单列表数据
     * 肖亚子
     */
    public function OrderList() {
        //获取参数
        $Condition = array();
        $Page      = $this->get('page', 1);//分页默认第一页
        $Status    = $this->get('status', 0);
        $excel     = $this->get('excel', 0);
        $Title     = $this->get('title', '');
        $StartTime = strtotime($this->get("starttime"));
        $EndTime   = strtotime($this->get("endtime"));
        $dboss_id  = $this->get('dboss_id', 0);
        $Isexpress = $this->get('isexpress', 0);
        $Reservation = $this->get('reservation', 0);
        $Distributiontag = $this->get('distributiontag', 0);
        $Payment   = $this->get('payment', 0);

        $Condition = $this->TimeContrast($StartTime,$EndTime,"o.order_addtime",$Condition);
        if ($Title){
            $Condition["o.order_no|u.nickname|u.mobile|m.merchant_name|o.order_fullname|o.order_mobile"] = array("like","%$Title%");
        }
        if ($dboss_id){
            $Condition["m.dboss_id"] = $dboss_id;
        }
        if ($Isexpress){
            $Condition["o.order_isexpress"] = $Isexpress;
        }
        if ($Reservation){
            $Condition["o.order_reservation"] = $Reservation;
        }
        if ($Distributiontag){
            if ($Distributiontag == 1){
                $Condition["pr.distributiontag"] = array("neq",0);
            }else{
                $Condition["pr.distributiontag"] = 0;
            }
        }

        if ($Payment){
            $Condition["o.order_payment"] = $Payment;
        }

        if ($Status){
            if ($Status==8){
                $Condition["o.order_status"] = 0;
            }else{
                $Condition["o.order_status"] = $Status;
            }
        }

        if($excel==1){
            $excelList = Db::name('order o')
                    ->field('o.order_no,o.order_fullname,o.order_mobile,o.order_isexpress,o.order_reservation,o.order_status,o.order_leave,p.product_name,p.product_property,p.num,p.price,pr.distributiontag,ua.ssq,ua.address')
                    ->Join("order_product p","p.order_id = o.order_id","left")
                    ->Join("product pr","pr.product_id = p.product_id","left")
                    ->Join("user u","u.user_id = o.user_id","left")
                    ->Join("user_address ua","ua.address_id = o.address_id","left")
                    ->Join("merchant m","m.merchant_id = o.merchant_id","left")
                    ->where($Condition)
                    ->order('o.order_id desc')
                    ->select();
            if (!empty($excelList)){
                foreach ($excelList as &$val){
                    if(!empty($val)){
                        $val['order_isexpress'] = $val['order_isexpress']==1?'到店':'快递';
                        $val['distributiontag'] = $val['distributiontag']==1?'是':'否';
                        if($val['order_reservation'] ==1){
                            $val['order_reservation'] = '预约制';
                        }elseif ($val['order_reservation'] ==2){
                            $val['order_reservation'] = '免预约';
                        }else{
                            $val['order_reservation'] = '电话预约';
                        }
                        switch ($val['order_status']){
                            case 1:$val['order_status']='未支付';break;
                            case 2:$val['order_status']='已支付';break;
                            case 3:$val['order_status']='待收货';break;
                            case 4:$val['order_status']='已完成';break;
                            case 5:$val['order_status']='取消订单';break;
                            case 6:$val['order_status']='申请换货';break;
                            case 7:$val['order_status']='申请换货';break;
                            default:$val['order_status']='支付过期';break;
                        }
                    }
                }
                $Column    =  array('订单号','联系人','联系电话','体验方式','预约方式','订单状态','用户留言','商品名','购买套餐','数量','单价','是否是分销其他平台','省市区','详细地址');

                $em   = new ExcelModel();
                $Date = date("Y年m月d日H时i分");
                $em->export($Column,$excelList,"","订单数据",$Date."订单数据");
            }
        }else{
            $DbossList = OrderModel::MerchantDboss();
            $OrderList = OrderModel::OrderList($Condition,$Page,50);
            $Count     = OrderModel::OrderCount($Condition);
            $List      = $OrderList[0];
            $Payfee    = $OrderList[1];
            $List      = self::OrderConvert($List);

            $Query = array("title" => $Title,"dboss_id"=>$dboss_id,"isexpress" => $Isexpress,"reservation"=>$Reservation,
                "distributiontag"=>$Distributiontag,"payment"=>$Payment);
            $Query = self::Time($StartTime,"starttime",$Query);
            $Query = self::Time($EndTime,"endtime",$Query);
            $this->assign('count', $Count);
            $this->assign("query",$Query);
            $this->assign('status', $Status);
            $this->assign('payfee', $Payfee);
            $this->assign('data', $List);
            $this->assign('dbosslist', $DbossList);
            // $this->assign('query_str', http_build_query($Query));
            return $this->display('index', true);
        }


    }

    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 获取订单详情信息
     * 肖亚子
     */
    public function OrderData(){
        $Condition = array();

        if (Request()->isGet()){
            //获取订单相关信息
            $OrderId = $this->get("order_id");
            $Status  = $this->get("status");

            $DataFind    = OrderModel::OrderFind($OrderId);
            $Data        = $DataFind[0];//订单信息
            $Goods       = $DataFind[1];//订单商品信息
            $Calendar    = $DataFind[2];//到店免预约日历数据
            $Reservation = $DataFind[3];//到店预约制商品或免预约商品
            $Delivery    = $DataFind[4];//快递预约制商品,预约发货信息
            $OrderCode   = $DataFind[5];//电子码
            $OrderMarkup = $DataFind[6];//电子码预约加价信息

            $Transference = self::Transference();

            $Data["typecss"]    = $Transference[0][$Data["order_isexpress"]]["css"];
            $Data["typename"]   = $Transference[0][$Data["order_isexpress"]]["name"];
            $Data["recss"]      = $Transference[1][$Data["order_reservation"]]["css"];
            $Data["rename"]     = $Transference[1][$Data["order_reservation"]]["name"];
            $Data["paycss"]     = $Transference[2][$Data["order_payment"]]["css"];
            $Data["payname"]    = $Transference[2][$Data["order_payment"]]["name"];

            if ($Data["order_isexpress"] == 1){
                if ($Data["order_status"] == 2){
                    $Data["statuscss"]  = $Transference[4][0]["css"];
                    $Data["statusname"] = $Transference[4][0]["name"];
                }else{
                    $Data["statuscss"]  = $Transference[3][$Data["order_status"]]["css"];
                    $Data["statusname"] = $Transference[3][$Data["order_status"]]["name"];
                }
            }elseif ($Data["order_isexpress"] == 2 && $Data["order_reservation"] == 1){
                if ($Data["order_status"] < 2){
                    $Data["statuscss"]  = $Transference[3][$Data["order_status"]]["css"];
                    $Data["statusname"] = $Transference[3][$Data["order_status"]]["name"];
                }else{
                    if (!$Delivery){
                        $Data["statuscss"]  = $Transference[4][1]["css"];
                        $Data["statusname"] = $Transference[4][1]["name"];
                    }else{
                        $Data["statuscss"]  = $Transference[3][$Data["order_status"]]["css"];
                        $Data["statusname"] = $Transference[3][$Data["order_status"]]["name"];
                    }
                }
            }else{
                $Data["statuscss"]  = $Transference[3][$Data["order_status"]]["css"];
                $Data["statusname"] = $Transference[3][$Data["order_status"]]["name"];
            }

            if ($Data["order_refundstatus"] > 0){
                $Data["refundstatuscss"]  = $Transference[5][$Data["order_refundstatus"]]["css"];
                $Data["refundstatusname"]  = $Transference[5][$Data["order_refundstatus"]]["name"];
            }

            if ($Data["order_isexpress"] == 2 and $Data["order_reservation"] == 2){
                if ($Data["order_plainday"]){
                    $Data["plaindaycss"]  = $Transference[6][0]["css"];
                    $Data["plaindayname"]  = $Transference[6][0]["name"];
                }else{
                    $Data["plaindaycss"]  = $Transference[6][1]["css"];
                    $Data["plaindayname"]  = $Transference[6][1]["name"];
                }
            }
            if($Goods["pricecalendar"]){
                $Goods["pricecalendar"] = json_decode($Goods["pricecalendar"],true);
            }

            if($OrderMarkup){
                foreach ($OrderMarkup as $Key=>$Val){
                    $OrderMarkup[$Key]["paycss"]  = $Transference[2][$Val["reservation_payment"]]["css"];
                    $OrderMarkup[$Key]["payname"] = $Transference[2][$Val["reservation_payment"]]["name"];
                }
            }
            if($Data['order_isexpress'] == 1 && $Data['order_reservation'] == 1){
                $http =  $_SERVER['REQUEST_SCHEME']?$_SERVER['REQUEST_SCHEME']:'http';
                $nativeurl= $http.'://'.$_SERVER['SERVER_NAME'].'/wechat_html/page/smsAppointment/smsVerify.html';
                foreach ($OrderCode as &$val){
                    if(!empty($val)){
                        $hash = Db::name('order_consume_code')->where(['consume_code'=>$val['consume_code']])->value('hash');
                        if($hash){
                            $val['url'] = $nativeurl."?code={$val['consume_code']}&hash={$hash}";
                        }else{
                            $val['url'] = $nativeurl."?code={$val['consume_code']}&mobile={$Data['order_mobile']}";
                        }
                    }
                }
            }

            $this->assign("status",$Status);
            $this->assign("data",$Data);
            $this->assign("goods",$Goods);
            $this->assign("calendar",$Calendar);
            $this->assign("reservation",$Reservation);
            $this->assign("ordercode",$OrderCode);
            $this->assign("ordermarkup",$OrderMarkup);
            $this->assign("delivery",$Delivery);

            return $this->display("view",true);
        }else{
            //修改订单消费码状态
            $OrderId = $this->post("order_id");
            $UserId  = $this->post("user_id");
            $Statuss = $this->post("statuss");
            $Status  = $this->post("status/a");

            foreach ($Status as $Key=>$Val){
                $Condition["order_id"]        = $OrderId;
                $Condition["user_id"]         = $UserId;
                $Condition["consume_code_id"] = $Key;

                $Data["status"] = $Val;
                $Data["uptime"] = time();

                $PreRevision = OrderModel::OrderConsumeCodeFind($Condition);
                $CodeUp      = OrderModel::OrderConsumeCodeUp($Condition,$Data);

                if ($CodeUp){
                    $Action = "管理员：".Session::get('admin.nickname')."修改订单消费码状态为：";
                    $TypeName = $Val == 1?"恢复":"冻结";
                    $CodeLog["user_id"]         = $UserId;
                    $CodeLog["consume_code_id"] = $Key;
                    $CodeLog["action"]          = $Action . $TypeName;
                    $CodeLog["admin_id"]        = Session::get('admin.id');
                    $CodeLog["addtime"]         = time();

                    $CodeLogAdd = OrderModel::OrderCodeLogAdd($CodeLog);

                    $this->log("修改订单消费码状态:[订单ID:".$OrderId."消费码ID".$Key."]","order_consume_code",$Condition,$PreRevision);
                    $this->log("添加消费码修改日志:[ID:".$CodeLogAdd."]");
                }
            }

            $this->toSuccess("更新成功", url("Order/OrderData",array("order_id"=>$OrderId,"status"=>$Statuss)), 1);
        }

    }

    /**
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 订单进行退款
     * 肖亚子
     */
    public function OrderRefund(){
        if (Request()->isGet()){
            $OrderId = $this->get("id");

            $Order = OrderModel::OrderRefundFind($OrderId);

            $this->assign("order",$Order);
            return $this->display("refund");
        }else{
            $OrderId = $this->post("id");
            $Status  = $this->post("status");//1退款并退佣金 2退佣金不退款 3退款不退佣金 4指定退款金额
            $Num     = $this->post("num");
            $Money   = $this->post("money");

            parent::Tpl_Empty($Num,"请输入退款份数");
            parent::Tpl_Integer($Num,"请输入退款份数,只能是正整数数字");

            if ($Status == 4){
                parent::Tpl_Empty($Money,"请输入指定退款金额");
                parent::Tpl_FullSpace($Money,"请输入指定退款金额");
                parent::Tpl_Money($Money,"请输入正确的退款金额");
            }

            $OrderFind = OrderModel::OrderRefundFind($OrderId);

            if (!$OrderFind){
                $this->toError("订单不存在,请重新发起退款");
            }
            if ($Num > $OrderFind["num"]){
                $this->toError("退款份数不能大于购买份数,购买份数为:{$OrderFind["num"]}份");
            }
            if ($Status == 4 && $OrderFind["order_totalfee"] < $Money){
                $this->toError("退款金额不能大于支付总金额");
            }

//            if($OrderFind["order_status"] == 4){
//                $this->toError("订单已完成,不能进行退款");
//            }
            if(!in_array($OrderFind["order_status"],array(2,3,4))){
                $this->toError("订单不能进行退款");
            }

            if($OrderFind["order_isexpress"] == 2){//快递商品判断
//                if ($OrderFind["order_status"] == 3){
//                    $this->toError("订单已发货,不能进行退款");
//                }
            }else{
                if ($Status != 4){
                    $CodeList = OrderModel::OrderRefundCode($OrderId);

//                    foreach ($CodeList as $Key => $Val){
//                        if ($Val["status"] == 2 || $Val["reservation_status"] == 2){
//                            $this->toError("订单电子码已有使用,不能进行退款");
//                            break;
//                        }
//                    }
                }
            }

            if ($Status != 4){
                $OrderFind["order_totalfee"] = $Num * $OrderFind["price"];
            }

          //  print_r($Num * $OrderFind["price"]);exit;
            $Month   = date("Ym",$OrderFind["order_paytime"]);
            $Time    = strtotime(date("Y-m-d",time()));
            $Paytime = strtotime(date("Y-m-d",$OrderFind["order_paytime"]));

            if ($Time == $Paytime){
                $Type = 1;
            }else{
                $Type = 2;
            }

            $Cash = OrderModel::TableName();
            $Cash->startTrans();//开启事务

            if (($Status == 1 || $Status == 2) && $OrderFind["order_payment"] != 5){
                if ($OrderFind["commis_first"] > 0){
                    self::OrderRefundCommission($OrderFind,$OrderFind["userid_first"],$OrderFind["commis_first"],$Cash,$Type,0);
                }
                if ($OrderFind["commis_second"] > 0){
                    self::OrderRefundCommission($OrderFind,$OrderFind["userid_second"],$OrderFind["commis_second"],$Cash,$Type,0);
                }
                if ($OrderFind["commis_operations"] > 0){
                    self::OrderRefundCommission($OrderFind,$OrderFind["userid_operations"],$OrderFind["commis_operations"],$Cash,$Type,0);
                }
                if ($OrderFind["commis_operations_child"] > 0){
                    self::OrderRefundCommission($OrderFind,$OrderFind["userid_operations_child"],$OrderFind["commis_operations_child"],$Cash,$Type,1);
                }
                if ($OrderFind["commis_playerhost_child"] > 0){
                    self::OrderRefundCommission($OrderFind,$OrderFind["userid_playerhost_child"],$OrderFind["commis_playerhost_child"],$Cash,$Type,1);
                }
                if($OrderFind["product_returnall"] == 1){ //新人免单退款
                    self::OrderRefundCommission($OrderFind,$OrderFind["user_id"],$OrderFind["totalmoney"],$Cash,$Type,0);
                }
            }

            if ($Status != 2 && $OrderFind["order_payment"] != 5){
                $PayRefund = new Paymodel();

                $Order["transaction_id"] = $OrderFind["transaction_id"];
                $Order["totalfee"]       = $OrderFind["order_totalfee"];

                if ($Status == 4){
                    $Order["refundfee"] = $Money;
                }else {
                    $Order["refundfee"] = $OrderFind["order_totalfee"];
                }

                $WeChat = $PayRefund->wxRefund($Order);

                if ($WeChat["result_code"] != "SUCCESS" ){
                    $Cash->rollback();//失败回滚exit;
                    $this->toError("订单微信退款失败,".json_encode($WeChat,JSON_UNESCAPED_UNICODE ));
                }
            }

            $OrderCondition["order_id"]      = $OrderId;
            $OrderData["order_status"]       = 6;
            $OrderData["order_refundstatus"] = 3;

            $OrderPreRevision = OrderModel::OrderRefundFinds($OrderCondition);
            $OrderUp          = OrderModel::OrderUpDate($OrderCondition,$OrderData);

            if ($OrderUp === false){
                $Cash->rollback();//失败回滚exit;
                $this->toError("订单退款修订单状态失败");
            }else{
                $this->log("订单退款,修改订单信息:[订单ID:".$OrderId."]","order",$OrderCondition,$OrderPreRevision);
            }

            $RefundData["order_id"]       = $OrderId;
            $RefundData["user_id"]        = $OrderFind["user_id"];
            $RefundData["refund_num"]     = $Num;
            $RefundData["refund_reason"]  = "平台进行退款";
            $RefundData["refund_status"]  = 3;
            $RefundData["refund_type"]    = $Status;
            $RefundData["refund_uptime"]  = time();
            $RefundData["refund_addtime"] = time();

            $RefundAdd = OrderModel::OrderRefundAdd($RefundData);

            if ($RefundAdd === false){
                $Cash->rollback();//失败回滚exit;
                $this->toError("订单退款添加退款申请失败");
            }else{
                $this->log("订单后台退款,添加退款申请信息:[订单ID:".$OrderId."]");
            }

            if ($Status == 4){
                $OrderFind["order_totalfee"] = $Money;
            }

            $RecordData["order_id"]      = $OrderId;
            $RecordData["user_id"]       = $OrderFind["user_id"];
            $RecordData["refund_no"]     = $OrderFind["transaction_id"];
            $RecordData["refund_amount"] = $OrderFind["order_totalfee"];
            $RecordData["refund_time"]   = time();
            $RecordData["remark"]        = "用户订单退款日志";

            $RecordAdd = OrderModel::OrderRefundRecordAdd($RecordData);

            if ($RecordAdd === false){
                $Cash->rollback();//失败回滚exit;
                $this->toError("订单退款添加退款记录失败");
            }else{
                $this->log("订单后台退款,添加退款日志:[订单ID:".$OrderId."]");
            }

            if (($Status == 1 || $Status == 3) && $OrderFind["order_payment"] != 5){

                if ($OrderFind["order_isexpress"] == 1 && $OrderFind["order_reservation"] == 1){
                    foreach ($CodeList as $Key => $Val){
                        if ($Val["reservation_addprice"] > 0 && $Val["reservation_status"] == 1){
                            $Order["transaction_id"] = $Val["transaction_id"];
                            $Order["totalfee"]       = $Val["reservation_addprice"];

                            $WeChat = $PayRefund->wxRefund($Order);

                            if ($WeChat["result_code"] != "SUCCESS" ){
                                $Cash->rollback();//失败回滚exit;
                                $this->toError("订单微信退款失败,".json_encode($WeChat,JSON_UNESCAPED_UNICODE ));
                                break;
                            }else{
                                $RecordData["user_id"]       = $Val["user_id"];
                                $RecordData["refund_no"]     = $Val["transaction_id"];
                                $RecordData["refund_amount"] = $Val["reservation_addprice"];
                                $RecordData["remark"]        = "用户订单预约加价退款日志";

                                $RecordAdd = OrderModel::OrderRefundRecordAdd($RecordData);

                                if ($RecordAdd === false){
                                    $Cash->rollback();//失败回滚exit;
                                    $this->toError("订单退款添加退款记录失败");
                                    break;
                                }else{
                                    $this->log("订单退款,消费码加价退款:[订单ID:".$OrderId.",消费码ID:".$Val["consume_code_id"]."]");
                                }
                            }
                        }
                    }
                }
            }

            if ($OrderFind["order_isexpress"] == 1 && $OrderFind["order_payment"] != 5){
                foreach ($CodeList as $Key => $Val){
                    $Condition["order_id"]        = $OrderId;
                    $Condition["user_id"]         = $Val["user_id"];
                    $Condition["consume_code_id"] = $Val["consume_code_id"];

                    $Data["status"] = 3;
                    $Data["uptime"] = time();

                    $CodePreRevision = OrderModel::OrderConsumeCodeFind($Condition);
                    $CodeUp          = OrderModel::OrderConsumeCodeUp($Condition,$Data);

                    if ($CodeUp){
                        $Action = "管理员：".Session::get('admin.nickname')."订单退款,电子码修改为过期";
                        $CodeLog["user_id"]         = $Val["user_id"];
                        $CodeLog["consume_code_id"] = $Val["consume_code_id"];
                        $CodeLog["action"]          = $Action;
                        $CodeLog["admin_id"]        = Session::get('admin.id');
                        $CodeLog["addtime"]         = time();
                        OrderModel::OrderCodeLogAdd($CodeLog);

                        $OrderMsg = "订单退款,消费码更改信息:[订单ID:".$OrderId.",消费码ID:".$Val["consume_code_id"]."]";
                        $this->log($OrderMsg,"order_consume_code",$Condition,$CodePreRevision);
                        $this->log("订单退款,修改消费码信息,添加消费码日志:[订单ID:".$OrderId.",消费码ID:".$Val["consume_code_id"]."]");
                    }
                }
            }

            if ($OrderFind["order_payment"] != 5){
                $RefundRecord = FinanceModel::refundDecodeData($Status==4?$Money:$OrderFind["order_totalfee"]);

                if (!$RefundRecord){
                    $Cash->rollback();//失败回滚exit;
                    $this->toError("订单退款统计失败");
                }else{
                    $this->log("订单退款,修改平台财务统计:[订单ID:".$OrderId."]");
                }
            }

            $Cash->commit();//成功提交事务
            $this->log("订单退款成功：[ID:".$OrderId."]");

            $this->toSuccess('退款成功', '', 2);
        }

    }

    /**
     * @param $OrderFind  订单信息
     * @param $Uid        用户id
     * @param $Money      扣除金额
     * @param $Cash       事务
     * @param int $Type   状态 1扣除佣金 2扣除现金
     * @param int $High   大于0代表扣除佣金
     */
    private function OrderRefundCommission($OrderFind,$Uid,$Money,$Cash,$Type = 1,$High = 0){

        $Arm   = new AccountRecordModel();
        $Admin = $Arm->getRecordAttach(session('admin.id'),session('admin.jname'),$OrderFind["order_no"]);
        $Month = date("Ym",$OrderFind["order_paytime"]);

        if ($Type == 1 || $High > 0){
            $CurrencyAction = CurrencyAction::CommissionDecodeBack;
            $Commission     = Currency::Commission;
            $Msg            = "订单退款扣除佣金失败";
            $CommissionMsg  = "订单退款:添加预估佣金扣除记录";
        }else{
            $CurrencyAction = CurrencyAction::CashDeducAdmin;
            $Commission     = Currency::Cash;
            $Msg            = "订单退款扣除现金失败";
            $CommissionMsg  = "订单退款:添加现金扣除记录";
        }

        if ($OrderFind["product_returnall"] == 1){
            $Dduct1 = $Arm->add($Uid, $OrderFind["order_id"],Currency::Commission,CurrencyAction::CommissionDecodeBack,-$Money,$Admin,"平台退款扣除");

            if ($Dduct1 == false){
                $Dduct2 = $Arm->add($Uid, $OrderFind["order_id"],Currency::Cash,CurrencyAction::CashDeducAdmin,-$Money,$Admin,"平台退款扣除");
                $Cash->rollback();//失败回滚exit;
                $this->toError("订单退款扣除现金失败");
            }
        }else{
            if ($Type == 1 || $High > 0){//预估佣金扣除
                $Dduct = $Arm->add($Uid, $OrderFind["order_id"],$Commission,$CurrencyAction,-$Money,$Admin,"平台退款扣除");
            }else{//现金扣除
                $Dduct = $Arm->add($Uid, $OrderFind["order_id"],$Commission,$CurrencyAction,-$Money,$Admin,"平台退款扣除");
            }
            if (!$Dduct){
                $Cash->rollback();//失败回滚exit;
                $this->toError("{$Msg}");
            }else{
                $this->log($CommissionMsg);
            }
        }

//        if ($Status > 0){
        $Condition["user_id"]  = $Uid;
        $Condition["order_id"] = $OrderFind["order_id"];
        $ComPreRevision = OrderModel::OrderRefundCommissionFind($Month,$Condition);
        $CommUp         = OrderModel::OrderRefundCommission($Month,$Condition);

        if ($CommUp === false){
            $Cash->rollback();//失败回滚exit;
            $this->toError("订单退款修改用户交易记录失败");
        }else{
            $this->log("订单退款,交易记录更改:[订单ID:".$OrderFind["order_id"]."]","account_commission".$Month,$Condition,$ComPreRevision);
        }
//        }
        $Record = FinanceModel::recordDecodeData($Commission,$Money);

        if(!$Record){
            $Cash->rollback();//失败回滚exit;
            $this->toError("订单退款统计后台退款失败");
        }else{
            $this->log("订单退款,统计后台退款信息:[订单ID:".$OrderFind["order_id"]."]");
        }

    }

    /**
     * @return array
     * 订单状态转中文
     * 肖亚子
     */
    private function Transference(){
        $GoodsType   = array("1" => array("css" => "layui-bg-red", "name" => "到店商品"),
            "2" => array("css" => "layui-bg-green", "name" => "快递商品"),);
        $Reservation = array("0" => array("css" => "layui-bg-gray", "name" => "免预约"),
            "1" => array("css" => "layui-bg-blue", "name" => "预约制"),
            "2" => array("css" => "layui-bg-gray", "name" => "免预约"),
            "3" => array("css" => "layui-btn-radius", "name" => "电话预约"));
        $PayType     = array("1" => array("css" => "layui-bg-green", "name" => "微信公众号支付"),
            "2" => array("css" => "layui-bg-blue", "name" => "支付宝APP支付"),
            "3" => array("css" => "layui-bg-orange", "name" => "银行卡支付"),
            "4" => array("css" => "layui-bg-green", "name" => "微信APP支付"),
            "5" => array("css" => "layui-bg-cyan", "name" => "现金支付[后台下单]"),
        );
        $OrderStatus = array("1" => array("css" => "layui-bg-gray","name" => "待付款"),
            "2" => array("css" => "layui-bg-black","name" => "待发货"),
            "3" => array("css" => "layui-bg-blue","name" => "待收货"),
            "4" => array("css" => "layui-bg-green","name" => "已完成"),
            "5" => array("css" => "layui-bg-blue","name" => "取消订单"),
            "6" => array("css" => "layui-bg-red","name" => "申请退款"),
            "7" => array("css" => "layui-bg-orange","name" => "申请换货"),
            "0" => array("css" => "layui-bg-black","name" => "订单过期"));
        $OrderBespoke = array("0"=>array("css" => "layui-bg-blue","name" => "待使用"),
            "1"=>array("css" => "layui-bg-gray","name" => "待预约发货")
        );
        $OrderRefund = array("1"=>array("css" => "layui-bg-blue","name" => "退款申请中"),
            "2"=>array("css" => "layui-bg-black","name" => "退款驳回"),
            "3"=>array("css" => "layui-bg-green","name" => "退款通过")
        );
        $Delivery = array("0"=>array("css" => "layui-bg-blue","name" => "用户指定发货"),
            "1"=>array("css" => "layui-bg-green","name" => "正常发货")
        );

        return array($GoodsType,$Reservation,$PayType,$OrderStatus,$OrderBespoke,$OrderRefund,$Delivery);
    }

    /**
     * @param $List    订单数据
     * @return mixed
     * 订单数据标识进行中文转换
     * 肖亚子
     */
    private function OrderConvert($List){
        $Transference = self::Transference();

        foreach($List["list"] as $Key=>$Val){
            $List["list"][$Key]["typecss"]    = $Transference[0][$Val["order_isexpress"]]["css"];
            $List["list"][$Key]["typename"]   = $Transference[0][$Val["order_isexpress"]]["name"];
            $List["list"][$Key]["recss"]      = $Transference[1][$Val["order_reservation"]]["css"];
            $List["list"][$Key]["rename"]     = $Transference[1][$Val["order_reservation"]]["name"];
            $List["list"][$Key]["paycss"]     = $Transference[2][$Val["order_payment"]]["css"];
            $List["list"][$Key]["payname"]    = $Transference[2][$Val["order_payment"]]["name"];

            if ($Val["order_isexpress"] == 1){
                if ($Val["order_status"] == 2){
                    $List["list"][$Key]["statuscss"]  = $Transference[4][0]["css"];
                    $List["list"][$Key]["statusname"] = $Transference[4][0]["name"];
                }else{
                    $List["list"][$Key]["statuscss"]  = $Transference[3][$Val["order_status"]]["css"];
                    $List["list"][$Key]["statusname"] = $Transference[3][$Val["order_status"]]["name"];
                }
            }elseif ($Val["order_isexpress"] == 2 && $Val["order_reservation"] == 1){

                if ($Val["order_status"] < 2){
                    $List["list"][$Key]["statuscss"]  = $Transference[3][$Val["order_status"]]["css"];
                    $List["list"][$Key]["statusname"] = $Transference[3][$Val["order_status"]]["name"];
                }else{
                    if (!$Val["address_id"]){
                        $List["list"][$Key]["statuscss"]  = $Transference[4][1]["css"];
                        $List["list"][$Key]["statusname"] = $Transference[4][1]["name"];
                    }else{
                        $List["list"][$Key]["statuscss"]  = $Transference[3][$Val["order_status"]]["css"];
                        $List["list"][$Key]["statusname"] = $Transference[3][$Val["order_status"]]["name"];
                    }
                }
            }else{
                $List["list"][$Key]["statuscss"]  = $Transference[3][$Val["order_status"]]["css"];
                $List["list"][$Key]["statusname"] = $Transference[3][$Val["order_status"]]["name"];
            }

            if ($Val["order_refundstatus"] > 0){
                $List["list"][$Key]["refundstatuscss"]  = $Transference[5][$Val["order_refundstatus"]]["css"];
                $List["list"][$Key]["refundstatusname"]  = $Transference[5][$Val["order_refundstatus"]]["name"];
            }

            if ($Val["order_isexpress"] == 2 and $Val["order_reservation"] == 2){
                if ($Val["order_plainday"]){
                    $List["list"][$Key]["plaindaycss"]  = $Transference[6][0]["css"];
                    $List["list"][$Key]["plaindayname"]  = $Transference[6][0]["name"];
                }else{
                    $List["list"][$Key]["plaindaycss"]  = $Transference[6][1]["css"];
                    $List["list"][$Key]["plaindayname"]  = $Transference[6][1]["name"];
                }
            }
        }

        return $List;
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
     * 修改
     * Enter description here ...
     */
//    public function view() {
//        if (Request::instance()->isGet()) {
//            $item = Db::name('order o')
//                ->field('o.*, r.username, r.mobile, r.remark, m.title, r.num, r.price, u.nickname')
//                ->where('o.id', Request::instance()->param('id', 0))
//                ->join('order_room r', 'r.order_id = o.id', 'left')
//                ->join('room m', 'm.id = r.room_id', 'left')
//                ->join('member u', 'o.user_id = u.id', 'left')
//                ->order('o.id desc')
//                ->find();
//
//            $service = Db::name('order_service')->where(['order_id'=>$item['id']])->select();
//            $item['services'] = $service;
//
//            $this->assign('obj', $item);
//            return $this->display('view', true);
//        }
//    }

    /**
     * 删除账号
     * Enter description here ...
     */
//    public function delete() {
//        $id = Request::instance()->param('id', 0);
//        $idstr = Request::instance()->post('idstr', '');
//        if ($id > 0) {
//            $obj = Db::name('order')->where('id', $id)->find();
//            $this->log('删除订单：' . $obj['id']);
//            $res = Db::name('order')->where('id='.$id)->update(['del'=>1]);
//        } else {
//            //批量删除
//            $idarray = explode(',', $idstr);
//            foreach ($idarray as $k => $v) {
//                if (!(empty($v))) {
//                    $obj = Db::name('order')->where('id', $v)->find();
//                    $this->log('删除订单：' . $obj['id']);
//                    $res = Db::name('order')->where('id='.$v)->update(['del'=>1]);
//                }
//            }
//        }
//        $this->toSuccess('删除成功');
//    }


//    public function ruzhu() {
//        $id = Request::instance()->param('id', 0);
//        if ($id > 0) {
//            $obj = Db::name('order')->where('id', $id)->find();
//            $this->log('客人入住：' . $obj['id']);
//            $res = Db::name('order')->where('id='.$id)->update(['status'=>2]);
//        }
//        $this->toSuccess('入住成功');
//    }

    /**
     * 后台操作-》单产品批量下单
     */
    public function createOrder(){
        $product_id = input('product_id');
        if (Request()->isGet()){
            $prices = Db::name('product_price')->where(['product_id'=>$product_id])->select();
            $this->assign('prices',$prices);
            $this->assign('product_id',$product_id);
            return $this->display('product:_create_order' );
        }else{
            $price = input('price',0);
            $buynum = input('buynum');
            $user_id = input('user_id');//默认下单用户
            if($user_id<1){
                $user_id= 15439;
            }
            $price_id = input('price_id');
            $product = $this->verfiyProduct($product_id, $price_id,$buynum);
            if(isset($product['code'] ) && $product['code'] == 400){
                return $product;
            }else{

                $data['buynum'] = $buynum;
//                if($user_id==15439){
//                    $data['concat'] = '后台下单';
//                    $data['mobile'] = '';
//                }else{
                $orderUser = Db::name('user')->where(array('user_id'=>$user_id))->field('nickname,mobile')->find();
                $data['concat']= $orderUser['nickname'];
                $data['mobile'] = $orderUser['mobile'];
//                }
                $data['product_id'] = $product_id;
                $data['price_id'] = $price_id;
                $data['price'] = $price;
                $data['remark'] = $this->post('remark', '通过后台管理员下单');
                $data['user_id'] = $user_id;
                $data['time'] = time();
                GLog('后台单品下单','数据'.json_encode($data));
                $data['key'] = getSelfSignStr($data);
                $host =  $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']: $_SERVER['SERVER_NAME'];
                $url =  $host.'/api/Order/createOrder';
                curlPost($url,$data); //curl创建订单
                $this->log("商品进行后台批量下单:[商品ID:".$product_id.",规格ID:".$price_id."]");
                $this->toSuccess("创建成功",'', 2);
            }
        }

    }

    /**
     * 后台操作-》Excel表导入批量下单
     */
    public function createOrderByExcel(){
        if (!empty ($_FILES ['excel'] ['name'])) {
            $tmp_file = $_FILES ['excel'] ['tmp_name'];
            $file_types = explode(".", $_FILES ['excel'] ['name']);
            $file_type = $file_types [count($file_types) - 1];
            /*判别是不是.xls文件，判别是不是excel文件*/
            if (strtolower($file_type) != "xlsx") {
                $this->error('不支持的Excel文件，请重新上传');
            }
            vendor('phpexcel.PHPExcel');
            vendor('phpexcel.PHPExcel.IOFactory');

            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            $obj_PHPExcel = $objReader->load($tmp_file, $encode = 'utf-8');//加载文件内容,编码utf-8
            $excel_array = $obj_PHPExcel->getsheet(0)->toArray();//转换为数组格式
            array_shift($excel_array);//删除第一个数组(标题);
            $datas = [];
            foreach ($excel_array as $k => $v) {
                $datas[$k]['product_id'] = $v[0];
                $datas[$k]['mobile'] = $v[1];
                $datas[$k]['buynum'] = $v[2];
                $datas[$k]['price'] = $v[3];
            }

            $data['concat'] = '用户';
            $data['remark'] = $this->post('remark', '通过后台管理员下单');
            $data['user_id'] =  15439;//下单用户
            $data['time'] = time();
            $data['key'] = getSelfSignStr($data);
            $data['list'] = json_encode($datas);
            $host =  $_SERVER['HTTP_HOST']?$_SERVER['HTTP_HOST']: $_SERVER['SERVER_NAME'];
            $url =  $host.'/api/Order/createBatchOrder';
            curlPost($url,$data); //curl创建订单
            $this->success('创建成功,',url('system/order/orderlist'));

        }
    }

    private function verfiyProduct($product_id, $price_id, $buynum){
        $product = Db::name('product p')
            ->field('c.*, p.product_name, p.product_status, p.product_del, p.product_reviewstatus,
            p.price_type, p.product_returnall, p.product_reservation, p.product_isexpress, 
            p.product_timelimit, p.product_numlimit, p.product_numlimit_num, p.product_starttime, p.product_endtime, 
            p.product_startusetime, p.product_endusetime, p.merchant_id, p.sold_out')
            ->join('product_price c', 'c.product_id = p.product_id', 'left')
            ->join('merchant m', 'm.merchant_id = p.merchant_id', 'left')
            ->where(['p.product_id'=>$product_id, 'c.price_id'=>$price_id, 'm.merchant_status'=>2, 'c.price_status'=>1])
            ->find();
        if(!$product){
            return array('code'=>400,'商品不存在');
        }
//        if($product['sold_out'] == 1){
//            return array('code'=>400,'商品已售罄');
//        }
        if($product['product_isexpress'] == 2 && $product['product_reservation'] == 2){
            return array('code'=>400,'快递类产品不支持后台下单');
        }
        if($product['product_del'] == 1 || $product['product_status'] == 0){
            return array('code'=>400,'商品售罄已下架');
        }
        if($product['product_reviewstatus'] != 2){
            return array('code'=>400,'商品未审核通过');
        }
//        if($product['product_buynum'] >= $product['product_totalnum']){
//            return array('code'=>400,'商品已售罄');
//        }
        if($product['product_buynum']+$buynum > $product['product_totalnum']){
            return array('code'=>400,'商品库存不足');
        }
        if($product['price_type'] == 2){
            return array('code'=>400,'商品价格类型异常');
        }
        $product['product_sku'] = $product['product_totalnum']-$product['product_buynum']; //剩余库存
        return $product;
    }

    /**
     * 导出商品销售数据
     */
    public function downOrderInfo(){
        $pType     = input('p_type',1);
        $productId = input('product_id');
        $starttime = input('starttime');
        $endtime   = input('endtime');
        # $where['o.order_status'] =array('in','2,3');// array('in','2,3,4');//已支付的
        if($productId){
            $where['p.product_id'] = $productId;
        }
        $tit = '全部销售数据';
        if($pType==2){ //已核销数据
            $where['o.order_status'] = 4;
            $tit = '已核销数据';
        }elseif($pType==3){ //待使用数据
            $where['o.order_status'] = array('in','2,3');
            $tit = '待使用数据';
        }else{ //全部销售数据
            $where['o.order_status'] = array('in','2,3,4');
        }
        $where = $this->TimeContrast(strtotime($starttime),strtotime($endtime),"o.order_addtime",$where);
        $field = "o.order_fullname,o.order_mobile,o.order_idcard,from_unixtime(o.order_plainday,'%Y-%m-%d') ,p.num,p.price,p.product_property,p.product_name,from_unixtime(o.order_addtime,'%Y-%m-%d %H:%i:%s')";
        $list = Db::name('order o')->join('order_product p','o.order_id=p.order_id','left')
            ->field($field)->where($where)->select();
        if(!empty($list)){
            $this->log("导出商品后台下单数据:[商品ID:".$productId."]");

            $Column    =  array("联系人","联系电话","身份证","预约（游玩）日期","购买数量","单价","规格（套餐）","产品名","下单时间");
            $em        = new ExcelModel();
            $Date      = date("Y年m月d日H时i分");
            $em->export($Column,$list,"","下单数据",$Date.$tit);
        }else{
            $this->error('暂无数据');
        }
    }


    public function resync(){
        $order_id = $this->post('orderid', 0);
        $order = Db::name('order o')
            ->field('o.*,p2.distributiontag, p.product_name, p.product_returnall, p.product_id, p.price_id, p.op_id, p.commission, p.num, p.product_startusetime, p.product_endusetime, m.merchant_contactmobile,m.dboss_id')
            ->join('jay_order_product p', 'p.order_id = o.order_id', 'left')
            ->join('jay_product p2', 'p2.product_id = p.product_id', 'left')
            ->join('jay_merchant m', 'm.merchant_id = o.merchant_id', 'left')
            ->where(['o.order_id'=>$order_id])->find();

        if($order){
            if($order['distributionsendcode'] == 1){
                return array('code'=>400,'已发码');
            }
            Db::name('order')->where(['order_id'=>$order['order_id']])->update(['oredr_remark'=>'']);
            if ($order["dboss_id"]==2){ //123门票(欢乐谷)网对接
                $this->createOrderToHappyValley($order);
            }elseif($order["dboss_id"]==3){ //多元通（国色天香）网对接
                $this->createOrderToHappyCloud($order);
            }else{
                return array('code'=>400,'不支持下单');
            }
            return array('code'=>200,'操作成功');
        }else{
            return array('code'=>400,'暂无订单');
        }
    }

    /**
     * 123门票网对接
     * @param $order
     * @return bool
     */
    private function createOrderToHappyValley($order){
        GLog('123门票网分销star:',1);
        $price = Db::name('product_price')->field('pnumber,gnumber')->where(['price_id'=>$order['price_id']])->find();
        if($price){
            $data['partner_order_number'] = $order['order_id'];//此订单在接入方系统中的唯一ID，用来防止重复下单
            $data['arrival_date'] = date('Y-m-d', $order['order_plainday']);//游玩日期
            $data['idnumber'] = $order['order_idcard'];//身份证号码
            $data['send_sms'] = $this->post('send_sms',true);//是否由123发送短信，默认是: true, 只对123自营项目有效，对123分销第三方产品的无效
            $data['name'] = $order['order_fullname'];//订票人姓名
            $data['tel'] = $order['order_mobile'];//联系电话
            $data['product_number'] = $price['pnumber'];//商品编号
            $line_items[0]['variant_number'] = $price['gnumber'];//门票编码
            $line_items[0]['quantity'] = $order['num'];
            $data['line_items'] = json_encode($line_items,true);//门票编码和数量
            \app\tapi\model\OrderModel::createOrderToHappyValley($data,$order['order_id']);
        }else{
            GLog('123门票网分销商品码规格码错误！！',2);
        }
    }

    /**
     * 123门票网对接
     * @param $order
     * @return bool
     */
    private function createOrderToHappyCloud($order){
        GLog('多元通网分销star:',1);
        $price = Db::name('product_price')->field('pnumber')->where(['price_id'=>$order['price_id']])->find();
        if(!empty($price)){
            $ticketOrder["order_sn"] = $order['order_no'];
            $ticketOrder["price"] =  $order['order_totalfee'];
            $ticketOrder["name"] =   $order['order_fullname'];
            $ticketOrder["phone"] =  $order['order_mobile'];
            $ticketOrder["idcard"] =  $order['order_idcard'];//身份证号码
            $ticketOrder["pay"] =  "vm";
            $ticketOrder["nums"] =  $order['num'];
            if($order['order_plainday'] != ''){
                $ticketOrder["appointment"] =  "true";
                $ticketOrder["appointment_date"] =  date('Y-m-d', $order['order_plainday']);//游玩日期
            }else{
                $ticketOrder["appointment"] =  "false";
                $ticketOrder["appointment_date"] = '0000-00-00';
            }
            $ticketOrder["goodsCode"] =  $price['pnumber'];//商品编号
            $ticketOrder["remark"] = $order['num'];
            $data["transactionName"] = "GET_ORDER";
            $data["header"]["application"] = "getorder";
            $data["identityInfo"]["corpcode"] = \app\tapi\model\OrderModel::corpcode;
            $data["identityInfo"]["dealername"] = \app\tapi\model\OrderModel::cloud_appid;
            $data["Orders"]["main_sn"] = $order['order_id'];
            $data["Orders"]["orders_price"] =$order['order_totalfee'];
            $data["Orders"]["ticketOrders"]["ticketOrder"] = $ticketOrder;
            \app\tapi\model\OrderModel::createOrderToHappyCloud($data,$order['order_id']);
        }else{
            GLog('123门票网分销商品码规格码错误！！',2);
        }
    }

    /**
     * 导出订单号对应的电子码
     */
    public function downOrderConsumeCode(){
        $order_id = $this->get('order_id', 0);
        $ordercode = Db::name("order_consume_code c")
            ->field("c.consume_code,c.status,c.hash,o.order_fullname,o.order_mobile")
            ->join('order o','o.order_id=c.order_id','left')
            ->where(array("c.order_id"=>$order_id))
            ->select();
        if(!empty($ordercode)){
            $http =  $_SERVER['REQUEST_SCHEME']?$_SERVER['REQUEST_SCHEME']:'http';
            $nativeurl= $http.'://'.$_SERVER['SERVER_NAME'].'/wechat_html/page/smsAppointment/smsVerify.html';
            $list = [];
            $order_fullname='';
            foreach ($ordercode as $val){
                if(!empty($val)){
                    $v['consume_code'] = $val['consume_code'];
                    if($val['hash']){
                        $v['url'] = createShortUrl( $nativeurl."?code={$val['consume_code']}&hash={$val['hash']}");
                    }else{
                        $v['url'] = createShortUrl( $nativeurl."?code={$val['consume_code']}&mobile={$val['order_mobile']}");
                    }

                    #'1未使用 2已使用；3已过期(如果预约了，没去消费自动过期 4冻结
                    if ($val['status']==2){
                        $v['status'] = '已使用';
                    }elseif ($val['status']==3){
                        $v['status'] = '已过期';
                    }elseif ($val['status']==4){
                        $v['status'] = '冻结';
                    }else{
                        $v['status'] = '未使用';
                    }
                    $list[]=$v;
                    $order_fullname = $val['order_fullname'];
                }
            }
            $Column    =  array('电子码','链接','状态');
            $em   = new ExcelModel();
            $Date = date("Y年m月d日H时i分");
            $em->export($Column,$list,"",$order_fullname,$order_fullname.$Date);
        }else{
            $this->error('暂无数据');
        }
    }
}
