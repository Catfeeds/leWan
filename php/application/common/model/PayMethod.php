<?php
/**
 * Created by PhpStorm.
 * User: jay
 * Date: 2018/7/20
 * Time: 15:20
 */


namespace app\common\model;

class PayMethod{


    /**
     * 微信公众号支付
     */
    const WxJSApi = 'wechat_jsapi';

    /**
     * 微信小程序支付
     */
    const WxApplets = 'wechat_applets';

    /**
     * 微信APP支付
     */
    const WxAppNative = 'weichat_app';


    /**
     * 支付宝支付
     */
    const AlipayNative = 'alipay_app';



    public static function getLabelBynumber($number){
        switch ($number){
            case 1:
                return '微信公众号支付';
            case 2:
                return '支付宝APP';
            case 4:
                return '微信APP';
            default:
                return '未知';
        }
    }
}