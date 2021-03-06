<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/6
 * Time: 17:47
 * 收入api接口
 * 肖亚子
 */
namespace app\api\controller;
use app\api\model\OrderModel;
use app\api\model\UserauthModel;
use app\api\model\UserModel;
use app\api\model\AccountcashModel;
use think\Db;
use Think\Exception;
use app\common\model\CurrencyAction;


class AccountcashController extends ApiBaseController{

    /**
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 获取用户收入明细
     * 肖亚子
     */
    public function UserAccountCashList(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");
            $Time  = input("post.time","","htmlspecialchars,strip_tags");//推荐码
            $Page  = intval(input("post.page","1"));

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Punfu->UserLevelPower($Token);//用户权限验证

            $Uid = UserModel::UserFindUid($Token);

            if ($Time){
                $Month = date("Ym",$Time);
            }else{
                $Month = date("Ym",time());
            }

            $Condition["user_id"]       = array("eq",$Uid);
//            $Condition[]                   = array("exp","ca.record_action = 802 or ca.record_action = 803");
            $Condition["record_status"] = array("eq",2);

            $Data = AccountcashModel::AccountcashList($Condition,$Month,$Page,20);

            $Cu = new CurrencyAction();

            if ($Data){
                foreach ($Data as $Key => $Val){

                    $Order = OrderModel::OrderFind(array("o.order_id"=> $Val["order_id"]),"o.order_status,o.order_refundstatus");

                    if ($Val["action"] == $Cu::CashDeducAdmin && $Order["order_status"] == 6 && $Order["order_refundstatus"] == 3){
                        $Data[$Key]["action"] = "订单退款扣现金";
                    }else{
                        $Data[$Key]["action"] = CurrencyAction::getLabel($Val["action"]);
                    }
                    unset($Data[$Key]["order_id"]);

                }
            }

            $this->returnApiData("获取成功", 200,$Data);

        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }
    }

    /**
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 获取用户佣金明细
     * 肖亚子
     */
    public function UserAccountCommissionhList(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");
            $Time  = input("post.time","","htmlspecialchars,strip_tags");//日期
            $Page  = intval(input("post.page","1"));

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Punfu->UserLevelPower($Token);//用户权限验证
            $user = getUserByToken();
            $Uid = $user['user_id'];

            if ($Time){
                $Month = date("Ym",$Time);
            }else{
                $Month = date("Ym",time());
            }

            $Condition["user_id"]       = array("eq",$Uid);
            $Condition["record_action"] = array("not in", '651,652');
            //$Condition["record_status"] = array("eq",2);

            $Data = AccountcashModel::AaccountCommissionList($Condition,$Month,$Page,20);

            if ($Data){
                $Cu        = new CurrencyAction();
                $temporder = [];
                foreach ($Data as $Key => $Val){
                    $Data[$Key]["type"] = 1;
                    if (in_array($Val["action"],array(601,602,603))){
                        $Val["action"] = CurrencyAction::getLabel($Val["action"]);
                        $Val["type"] = 2;
                        $attr = json_decode($Val['record_attach'], true);
                        if($temporder['order_no'] != $attr['orderNo']){
                            $order = Db::name('order o')
                                ->field('o.user_id,p.product_name,p.num,o.order_fullname,o.order_mobile,p.userid_first,p.userid_second')
                                ->join('jay_order_product p', 'p.order_id=o.order_id','left')
                                ->where(['o.order_no'=>$attr['orderNo']])->find();
                            $order['order_mobile'] = substr($order['order_mobile'],0,3).'****'.substr($order['order_mobile'],7,4);

                            $User = UserModel::UserDataFind(array("u.user_id"=> $order["user_id"]),"u.level");

                            if ($User["level"] < 4){
                                if ($order["user_id"] == $order["userid_first"]){
                                    $UserCondition["u.user_id"] = $order["userid_second"];
                                }else{
                                    $UserCondition["u.user_id"] = $order["userid_first"];
                                }

                                $Superior = UserModel::UserDataFind($UserCondition,"u.nickname,u.mobile,u.reid");
                                if($Superior['reid'] != $user['user_id']){
                                    $Superior["mobile"] = Func_Phone_Replace($Superior["mobile"]);
                                }
                            }else{
                                $Superior["nickname"] = "";
                                $Superior["mobile"]   = "";
                            }

                            unset($order["user_id"]);
                            unset($order["userid_first"]);
                            unset($order["userid_second"]);

                            $order     = array_merge($order,$Superior);
                            $temporder = $order;
                        }

                        $Data[$Key] = array_merge($Val, $temporder);
                    }else{
                        $Order = OrderModel::OrderFind(array("o.order_id"=> $Val["order_id"]),"o.order_status,o.order_refundstatus");

                        if ($Val["action"] == $Cu::CommissionDecodeBack && $Order["order_status"] == 6 && $Order["order_refundstatus"] == 3){
                            $Data[$Key]["action"] = "订单退款扣佣金";
                        }else{
                            $Data[$Key]["action"] = CurrencyAction::getLabel($Val["action"]);
                        }
                    }
                    unset($Data[$Key]["order_id"]);
                }
            }

            $this->returnApiData("获取成功", 200,$Data);
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }

    }

}
