<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/7
 * Time: 15:40
 * 提现模型
 * 肖亚子
 */

namespace app\api\model;
use think\Db;

class UserwithdrawModel {

    public static function TableName(){
        return Db::name("user_withdraw");
    }

    /**
     * @param array $Condition  查询条件
     * @param $Page             分页数默认第一页
     * @param int $Psize        分页条数默认20条
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户提现明细
     * 肖亚子
     */
    public static function WithdrawList($Condition = array(),$Page ,$Psize = 20){
        $Data = self::TableName()
                ->field("withdraw_amount as money,withdraw_type as type,withdraw_status as status,withdraw_reason as reason,withdraw_addtime as addtime")
                ->where($Condition)
                ->page($Page,$Psize)
                ->select();

        return $Data;
    }

    /**
     * @param $Data     添加内容
     * @return int|string
     * 添加提现申请
     * 肖亚子
     */
    public function WithdrawAdd($Data){
        $Data = self::TableName()->insert($Data);

        return $Data;
    }

    /**
     * @param array $Condition  修改条件
     * @param $Data             修改内容
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改账户信息
     * 肖亚子
     */
    public static function UserAccountUpdate($Condition = array(),$Data){
        $Data = Db::name("account")->where($Condition)->update($Data);

        return $Data;
    }

    /**
     * @param array $Condition  查询条件
     * @return mixed
     * 获取系统配置数据
     * 肖亚子
     */
    public static function ParameterFind($Condition = array()){
        $Data = Db::name("parameter")->where($Condition)->value("value");

        return $Data;
    }


    /**
     * 微信提现
     * @param $data
     * @return mixed
     */
    public static function wxWithdrawal($appid,$mchid=WX_MCHID,$data){
        $webdata = array(
          'mch_appid' => $appid,//商户账号appid (app)
//            'mch_appid' => WX_APPID,//商户账号appid
            'mchid'     => $mchid,//商户号
            'nonce_str' => md5(time()),//随机字符串
            'partner_trade_no'=> $data['order_on'], //商户订单号，需要唯一
            'openid' => $data['openid'],//转账用户的openid
            'check_name'=> 'NO_CHECK', //OPTION_CHECK不强制校验真实姓名, FORCE_CHECK：强制 NO_CHECK：
            'amount' => $data['amount']*100, //付款金额单位为分
            'desc'   => $data['desc'],//企业付款描述信息
            'spbill_create_ip' => request()->ip(),//获取IP
        );
        $webdata['sign']= self::getSing($webdata,WX_KEY);
        $wget = array2xml($webdata);
        $pay_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';//api地址
        return self::curlPost($pay_url,$wget,true);//发送数据
    }

    private static function getSing($data,$key){
        ksort($data);//排序
        $str='';
        foreach($data as $k=>$v) {
            $str.=$k.'='.$v.'&';
        }
        $str.='key='.$key;
        return md5($str);//加密
    }

    /**
     * @param string $url       请求路劲
     * @param string $postData  请求参数
     * @param array  $useCert   证书
     * @return mixed
     */
    public static function curlPost($url = '', $postData = '', $useCert = false){
        Vendor("WxPay.WxPay#Config");
        $config = new \WxPayConfig();
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if ($useCert) {
            $sslCertPath = "";
            $sslKeyPath = "";
            $config->GetSSLCertPath($sslCertPath, $sslKeyPath);
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, $sslCertPath);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, $sslKeyPath);
        }
        if(stripos($url,"https://")!==FALSE){
            //https请求 不验证证书和host
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }    else    {
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

}