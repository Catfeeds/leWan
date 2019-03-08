<?php

namespace app\tapi\controller;

use app\common\BaseController;
use app\tapi\model\OrderModel;
use Think\Db;

/**
 *  类描述: [欢乐云对接]
 *  创建人: [yih]
 *  修改备注: [说明本次修改内容]
 */
class HappycloudController extends BaseController {

    /**
     *
     */
    public function createOrder(){
        $ticketOrder["order_sn"] = "t2014120400222603";
        $ticketOrder["price"] =  "100.00";
        $ticketOrder["name"] =  "测试";
        $ticketOrder["phone"] =  "18200535820";
        $ticketOrder["idcard"] =  "510922199601114192";
        $ticketOrder["pay"] =  "vm";
        $ticketOrder["nums"] =  "1";
        $ticketOrder["appointment"] =  "true";
        $ticketOrder["appointment_date"] =  "2019-01-29";
        $ticketOrder["goodsCode"] =  "DYT19700101222";
        $ticketOrder["remark"] =  "备注";
        $data["transactionName"] = "GET_ORDER";
        $data["header"]["application"] = "getorder";
        $data["identityInfo"]["corpcode"] = OrderModel::corpcode;
        $data["identityInfo"]["dealername"] = OrderModel::cloud_appid;
        $data["Orders"]["main_sn"] ="10003";
        $data["Orders"]["orders_price"] ="100.00";
        $data["Orders"]["ticketOrders"]["ticketOrder"] = $ticketOrder;
        OrderModel::createOrderToHappyCloud($data, $ticketOrder["order_sn"]);

    }

    /**
     * 123门票核销通知处理乐玩订单状态
     */
    public function finishOrder(){
//        http://cs.lewan6.ren/tapi/Happycloud/finishOrder/?order_no=LW19021209552907819022264&checkNum=1&status=check&sign=f64e96804d5c55f2f8bf273a9c5bee44
        GLog('多元通网核销通知返回',json_encode($data = $this->request->param()));
        $order_no = $this->get('order_no');
       # $sub_order_no = $this->get('sub_order_no');
        $total = $this->get('total');
        $signature = $this->get('sign');
        $sign = md5('order_no='.$order_no. OrderModel::cloud_secret);
        if($signature == $sign){
           # $where['o.order_id'] = $order_no;
            $where['o.order_no'] = $order_no;
            $order = Db::name('order o')
                ->field('o.order_status,o.order_id,p.num')
                ->join('order_product p','p.order_id=o.order_id','left')
                ->where($where)->find();
            if(empty($order)){
                GLog('未找到订单信息',json_encode($where));
                echo  'error';
            }/*elseif($order['num']!=$total){
                GLog('订单份数不匹配','乐玩：'.$order['num'].'；多元通：'.$total);
                echo  'error';
            }*/else{
                if($order['order_status'] ==2 ||$order['order_status']==3){
                    $res = Db::table('jay_order')->where(['order_id'=>$order['order_id']])->update(['order_status'=>4,'order_uptime'=>time()]);
                    Db::table('jay_order_consume_code')->where(['order_id'=>$order['order_id']])->update(['status'=>2,'uptime'=>time()]);
                    if( $res !== false ){
                        GLog('多元通网核销订单成功','orderid='.$order_no);
                        echo  'success';
                    }else{
                        GLog('多元通网核销订单失败orderid='.$order_no,Db::table('jay_order')->getLastSql());
                        echo  'error';
                    }
                }else{
                    if($order['order_status'] ==4){
                        echo  'success';
                        GLog('订单已经核销 order no',$order_no);
                    }else if($order['order_status'] ==1){
                        GLog('订单未支付 order no',$order_no);
                        echo  'error';
                    }else{
                        GLog('订单状态异常 order no',$order_no);
                        echo  'error';
                    }
                }
            }
        }else{
            GLog('多元通网核销订单签名错误',$sign.'签名错误！'.$signature);
            echo  'error';
        }
    }

}