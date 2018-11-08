<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/3
 * Time: 14:18
 */

namespace app\api\controller;
use app\api\model\UserModel;
use app\api\controller\ApiBaseController;

class PubfunController extends ApiBaseController{

    /**
     * @param $Token     用户token
     * @param $Header    header头
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 用户判断
     * 肖亚子
     */
    static function UserLoginStatus($Token,$Header){

        $Condition["token"] = array("eq",$Token);

        if ($Header["product"] == "wechat"){
            $Finde = "u.user_id,u.status,uc.openid,uc.accesstoken_expired";
        }else{
            $Finde = "u.user_id,u.status";
        }

        $UserProving = UserModel::UserDataFind($Condition,$Finde);
       // self::returnApiData("账号已被禁止", 400,$UserProving);
        if (!$UserProving){
            self::returnApiData("账号不存在,请重新登录", 400);
        }
        if ($UserProving["status"] != 1){
            self::returnApiData("账号已被禁止", 400);
        }

        if ($Header["product"] == "wechat"){
            $Time = time();

            if ($Time >= $UserProving["accesstoken_expired"]){//微信授权过期默认获取
                $RenewCondition["user_id"] = array("eq",$UserProving["user_id"]);

                $BaseUrl         = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=". WX_APPID . "&secret=".WX_APPSECRET;
                $BaseAccesstoken = json_decode(curlGet($BaseUrl),true);

                $BaseDetails     = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$BaseAccesstoken["access_token"]."&openid=".$UserProving["openid"];
                $BaseDetails     = json_decode(curlGet($BaseDetails),true);

                $UserData["nickname"] = $BaseDetails["nickname"];
                $UserData["avatar"]   = $BaseDetails["headimgurl"];

                $UserUp = UserModel::UserUpdate($RenewCondition,$UserData);

                $ConnectData["subscribe"]               = $BaseDetails["subscribe"];
                $ConnectData["union_id"]                = $BaseDetails["unionid"];
                $ConnectData["accesstoken"]             = $BaseAccesstoken["access_token"];
                $ConnectData["accesstoken_expired"]     = parent::Tpl_Time($Time,7,2);//过期时间当前时间2小时
                $ConnectData["data"]                    = json_encode($BaseDetails);
                $ConnectData["addtime"]                 = $Time;

                $ConnectUp = UserModel::UserConnectUpdate($RenewCondition,$ConnectData);

            }
        }

    }

    /**
     * @param $Token    用户token
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 验证用户权限
     * 肖亚子
     */
    static function UserLevelPower($Token){
        $Condition["token"] = array("eq",$Token);

        $Level = UserModel::UserDataFind($Condition,"u.level");

        if ($Level["level"] <= 1){
            self::returnApiData("账号权限不足", 400);
        }
    }

    /**
     * @param $Token  用户token
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 验证用户是否进行实名认证
     * 肖亚子
     */
    static function UserRealName($Token){
        $Condition["token"] = array("eq",$Token);

        $UserProving = UserModel::UserDataFind($Condition,"u.user_id,u.auth");

        if ($UserProving["auth"] == 1){
            self::returnApiData("请进行实名认证", 400);
        }
    }

}