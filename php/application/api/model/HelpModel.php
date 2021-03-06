<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/29
 * Time: 14:43
 */

namespace app\api\model;
use think\Cache;
use think\Db;

/**
 * 接口
 * Class ApiBaseModel
 * @package app\api\model
 */
class HelpModel
{

    /**
     * 会员邀请码
     * 生成8位 字母+数字的唯一码
     * @return string
     */
    public static function makeUserCode($len=6) {
        $code = self::getcode($len);
        if (Db::name('user')->where(['recode'=>$code])->count() == 0) {
            return $code;
        } else {
            while(true) {
                self::makeUserCode();
            }
        }
    }


    /**
     * 生成23位不重复订单号
     * @return string
     */
    public static function makeOrderNumber() {
        $orderNo = 'LW'.date('ymdHis').sprintf('%03d', rand(0, 99));
        $orderNo .= substr($orderNo, rand(2, 10),3). substr(microtime(), 2, 5);
        return $orderNo;
    }


    /**
     * 预约订单
     * @return string
     */
    public function makeROrderNumber() {
        $orderNo = 'y'.date('ymdHis').sprintf('%03d', rand(0, 99));
        $orderNo .= substr($orderNo, rand(2, 10),3). substr(microtime(), 2, 5);
        return $orderNo;
    }


    /**
     * 生成订单电子码
     */
    public function makeConsumeCode($orderId){
        $code = rand(10,99).sprintf('%03d', $orderId).substr(microtime(), 2, 5).rand(10,99);
        return $code;
    }





    /*****************************/
    private static function getcode($Number = 6){
        $code="ABCDEFGHIGKLMNOPQRSTUVWXYZ";
        $rand=$code[rand(0,25)].strtoupper(dechex(date('m'))).date('d').substr(time(),-5).substr(microtime(),2,5).sprintf('%02d',rand(0,99));
        for(
            $a = md5( $rand, true ),
            $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
            $d = '',
            $f = 0;
            $f < $Number;
            $g = ord( $a[ $f ] ),
            $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
            $f++
        );
        return $d;
    }


    /**
     * 全局的Access_token
     * @param bool $forced 强制刷新
     * @return mixed
     */
    public function getAccessToken($forced=false){
        //查询access_token
        $access_token  = Db::name('access_token')->find(1);
        $access_ticket = Db::name('access_jsapi_ticket')->find(1);
        if($access_token['expire'] < time()-910 || $forced){
            //过期，获取新的token
            $BaseUrl         = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=". WX_APPID . "&secret=".WX_APPSECRET;
            $BaseAccesstoken = json_decode(curlGet($BaseUrl),true);
            $access_token['access_token'] = $BaseAccesstoken['access_token'];
            $access_token['expire']       = $BaseAccesstoken['expires_in']+time();
            Db::name('access_token')->update($access_token);

//            $Ticketurl = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token={$BaseAccesstoken["access_token"]}";
//            $WechatTicket =  json_decode(curlGet($Ticketurl));
//
//            $access_ticket["ticket"] = $WechatTicket->ticket;
//            $access_ticket["expire"] = $WechatTicket->expires_in + time();
//
//            Db::name("access_jsapi_ticket")->update($access_ticket);
        }

//        $Access["access_token"] = $access_token['access_token'];
//        $Access["ticket"]       = $access_ticket['ticket'];

        return  $access_token['access_token'];
    }

}