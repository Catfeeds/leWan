<?php
/**
 * Created by PhpStorm.
 * User: jay
 * Date: 2018/7/20
 * Time: 13:53
 */
namespace app\common\model;
use think\Db;
use think\Request;


/**
 * 支付工具
 * Class Paymodel
 * @package app\common\model
 */
class Paymodel{


    /**
     * 微信公众号支付-统一下单
     * @param $openId
     * @param $orderNo
     * @param $amount
     * @param $notifyurl
     * @param $subject
     * @param $body
     * @return \json数据，可直接填入js函数作为参数
     */
    public function wxJsPay($openId, $orderNo, $amount, $notifyurl, $subject='乐玩联盟', $body='乐玩联盟'){
        //调用JSAPI
        Vendor("WxPay.WxPay#Api");
        Vendor("WxPay.WxPay#JsApiPay");

        $tools = new \JsApiPay();

        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($body);
        $input->SetAttach($subject);
        $input->SetOut_trade_no($orderNo);
        $input->SetTotal_fee($amount*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url(url($notifyurl, ['payway'=>PayMethod::WxJSApi], true, true));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $config = new \WxPayConfig();
        $order = \WxPayApi::unifiedOrder($config, $input);
        //统一下单结果
        //fuck($order);
        if($order['return_code'] != 'SUCCESS'){
            ajaxreturn($order['return_msg'], 400);
        }
        if($order['result_code'] != 'SUCCESS'){
            ajaxreturn($order['err_code_des'], 400);
        }
        //返回js参数调起支付
        $jsApiParameters = $tools->GetJsApiParameters($order);
        return $jsApiParameters;
    }


    /**
     * 微信APP支付
     * @param $orderNo
     * @param $amount
     * @param $notifyurl
     * @param string $subject
     * @param string $body
     * @return mixed
     */
    public function wxAPPPay($orderNo, $amount, $notifyurl, $subject='乐玩联盟', $body='乐玩联盟'){
        //调用JSAPI
        Vendor("WxPay.WxPay#Api");
        Vendor("WxPay.WxPay#Config");
        Vendor("WxPay.WxPay#AppPay");
        $config = new \WxPayConfig();
        $wx = new \Wxpay($config);//实例化微信支付控制器
        $order = $wx->getPrePayOrder($body, $orderNo, $amount, url($notifyurl, ['payway'=>PayMethod::WxAppNative], true, true));//调用微信支付的方法
        if($order['return_code'] != 'SUCCESS'){
            ajaxreturn($order['return_msg'], 400);
        }
        if($order['result_code'] != 'SUCCESS'){
            ajaxreturn($order['err_code_des'], 400);
        }
        $payParameter = $wx->getAppPayParameter($order['prepay_id']);
        return $payParameter;
    }



    /**
     * 处理微信退款
     * @param $order
     * @return bool|\成功时返回，其他抛异常
     */
    public function wxRefund($order){
        Vendor("WxPay.WxPay#Api");
        Vendor("WxPay.WxPay#JsApiPay");
        try{
            $transaction_id = $order["transaction_id"];
            $total_fee = $order["totalfee"]*100;
            $refund_fee = $order["refundfee"]*100;
            $input = new \WxPayRefund();
            $input->SetTransaction_id($transaction_id);
            $input->SetTotal_fee($total_fee);
            $input->SetRefund_fee($refund_fee);

            $config = new \WxPayConfig();
            $input->SetOut_refund_no("sdkphp".date("YmdHis"));
            $input->SetOp_user_id($config->GetMerchantId());

            $wx=  \WxPayApi::refund($config, $input);

            GLog("Refund",json_encode($wx,JSON_UNESCAPED_UNICODE ));
            return $wx;
        } catch(Exception $e) {
            GLog("Refund",json_decode($e),JSON_UNESCAPED_UNICODE);
            return false;
        }
    }

}