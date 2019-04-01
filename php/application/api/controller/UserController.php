<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/3
 * Time: 13:58
 * 用户接口
 * 肖亚子
 */

namespace app\api\controller;
use app\api\model\JpushModel;
use app\api\model\OpenTmModel;
use app\api\model\OrderModel;
use app\api\model\ProductModel;
use app\api\model\UserModel;
use app\common\model\AccountRecordModel;
use app\common\model\CurrencyAction;
use app\common\model\ProcedureModel;
use app\common\model\Tag;
use think\Db;
use Think\Exception;
use app\api\model\HelpModel;
use app\api\model\UserUpgradeModel;
use app\api\model\UserauthModel;
use app\common\model\Paymodel;

class UserController extends ApiBaseController{
    /**
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * 用户注册
     * 肖亚子
     */
    public function UserRegister(){
        try{
            $Token       = input("post.token","","htmlspecialchars,strip_tags");
            $Recode      = input("post.recode","","htmlspecialchars,strip_tags");//推荐码
            $Mobile      = input("post.mobile","","htmlspecialchars,strip_tags");//注册电话
            $ProvingCode = input("post.provingcode","","htmlspecialchars,strip_tags");//验证码

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断

            parent::Tpl_Empty($Recode,"请输入推荐码",2);
            parent::Tpl_FullSpace($Recode,"请输入推荐码",2);
            parent::Tpl_NoSpaces($Recode,"请输入正确的推荐码",2);
            parent::Tpl_Alphanumeric($Recode,"请输入正确的推荐码",2);
            parent::Tpl_Lengths($Recode,"推荐码正确长度为6-16",6,16,2);
            parent::Tpl_Empty($Mobile,"请输入手机号",2);
            parent::Tpl_FullSpace($Mobile,"请输入正确的手机号",2);
            parent::Tpl_Phone($Mobile,"请输入正确的手机号码",2);
            parent::Tpl_Empty($ProvingCode,"请输入验证码",2);
            parent::Tpl_FullSpace($ProvingCode,"请输入正确的验证码",2);
            parent::Tpl_Lengths($ProvingCode,"验证码为6位",6,6,2);

            $User = Db::name('user')->where(['token'=>$Token])->find();
            $Uid = $User['user_id'];
            if ($User){
                if ($User["level"] > 1){
                    $this->returnApiData("您的微信账号已注册,如需更改手机号请先登录", 400);
                }
            }else{
                $this->returnApiData("请登录", 400);
            }

            $UserMobile = UserModel::UserDataFind(array("mobile"=>array("eq",$Mobile)),"u.mobile");
            if ($UserMobile){
                $this->returnApiData("该手机号已被注册,请重新输入", 400);
            }
            //推荐人是否存在
            $Referee = Db::name('user')->field('user_id,path,floor') ->where(['recode'=>$Recode])->find();
            if ($Referee){
                $Data["reid"]  = $Referee["user_id"];
                $Data["path"]  = $Referee["path"].$Referee["user_id"].",";
                $Data["floor"] = $Referee["floor"] + 1;
            }else{
                $this->returnApiData("推荐人不存在", 400);
            }

            $Punfu->PhoneVerification($Mobile,$ProvingCode);//验证码判断

            $Locus  = self::UserMobileArea($Mobile);
            $Random = Func_Random(6);

            $Data["recode"]       = HelpModel::makeUserCode(6);
            $Data["mobile"]       = $Mobile;
            $Data["password"]     = func_user_hash(substr($Mobile,-8),$Random);
            $Data["dllkey"]       = $Random;
            $Data["level"]        = 2;
            $Data["reg_time"]     = time();
            $Data["upgrade_time"] = time();
            $Data["mobileaddr"]   = $Locus["prov"]."/".$Locus["city"]."/".$Locus["type"];
            //升级超级会员
            $ConditionUp["user_id"] = array("eq",$Uid);
            $UserData = UserModel::UserUpdate($ConditionUp,$Data);
            if ($UserData !== false){
                UserUpgradeModel::check($Uid,1);//用户升级检测
                $uplog['user_id'] = $Uid;
                $uplog['old_level'] = 1;
                $uplog['new_level'] = 2;
                $uplog['remark'] = '注册超级会员';
                $uplog['addtime'] = time();
                Db::name('user_upgrade')->insert($uplog);
                //发送注册推送
                $accessToken = Db::name('access_token')->find(1);
                $otm = new OpenTmModel();
                $jpush['title'] = $msgdata['title'] = '您有好友注册成功';
                $msgdata['keyword1'] = $Mobile."\r\n会员昵称：".$User['nickname'];
                $msgdata['keyword2'] = date('Y-m-d H:i:s');
                $msgdata['remark'] = '感谢您的分享！';
                $jpush['alert']= "您有好友注册成功!会员昵称".$User['nickname']."！感谢您的分享";
                $joption['type'] = JpushModel::JPUSH_MSG_NORMAL;
                $jpush['platform'] =  'all';
                $userpush = UserModel::getPushObj($Referee["user_id"]);
                if($userpush['deviceid']){ //极光推送
                    JpushModel::sendMsgSpecial($userpush['deviceid'],$jpush,$joption);
                }

                if($userpush['openid']){ //微信推送
                    $otm->sendTplmsg7($userpush['openid'], $msgdata, $accessToken['access_token']);
                }else{
                    GLog('注册推送通知失败','没有openID');
                    if($Referee["user_id"]){
                        GLog('注册推送失败1','上级');
                    }
                }


//                $openid = Db::name('user_connect')->where(['user_id'=>$Referee["user_id"],'platform'=>'wechat'])->value('openid');
//                if($openid){
//                    $otm->sendTplmsg7($openid, $msgdata, $accessToken['access_token']);
//                }
                
                //上线运营达人
                $zh = substr($Mobile, 0, 3). '***' .substr($Mobile, 7,4);
                $msgdata['keyword1'] = $zh."\r\n会员昵称：".$User['nickname'];
                $sql = "select c.openid,u.deviceid from jay_user u ".
                    " left join jay_user_connect c on c.user_id = u.user_id".
                    " where FIND_IN_SET(u.user_id, '".$Referee['path'].$Referee['user_id']."') and u.`level`>= 4 and c.platform='wechat' order by u.floor desc limit 1";
                $yydrOpenid = Db::query($sql);
                $jpush['title'] = '您的团队有新人注册成功';
                if(!empty($yydrOpenid) && $yydrOpenid[0]['deviceid']){ //极光推送
                    JpushModel::sendMsgSpecial($userpush['deviceid'],$jpush,$joption);
                }

                if(!empty($yydrOpenid) && $yydrOpenid[0]['openid']){ //微信推送
                    $otm->sendTplmsg7($yydrOpenid[0]['openid'], $msgdata, $accessToken['access_token']);
                }else{
                    GLog('注册推送失败'.$Referee['path'].$Referee['user_id'],'2级'.Db::name('user u')->getLastSql());
                }

                $this->returnApiData("注册成功", 200);
            }else{
                $this->returnApiData("注册失败", 400);
            }

        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }
    }

    /**
     * 手机号+动态码登录
     */
    public function UserLogin(){
        try{
            $Mobile      = input("post.mobile","","htmlspecialchars,strip_tags");//注册电话
            $ProvingCode = input("post.provingcode","","htmlspecialchars,strip_tags");//验证码
            $Punfu       = new PubfunController();
            //APP审核账号
            if($Mobile != '13990141242' || $ProvingCode != '666666'){
                $Punfu->PhoneVerification($Mobile, $ProvingCode);
            }
            //验证用户是否存在
            $user = Db::name('user')->where(['mobile'=>$Mobile])->find();
            if(!$user){
                $this->returnApiData("手机号不存在", 400);
            }
            $UserData = $Punfu->UserInfo(array("u.mobile" => $Mobile));

            $this->returnApiData("登录成功", 200,$UserData);
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }

    }

    /**
     * 检查手机号是否绑定
     */
    public function mobileisbind(){
        try{
            $mobile = $this->post('mobile', '');
            $user = Db::name('user')->where(['mobile'=>$mobile])->find();
            if(!$user){
                $this->returnApiData('手机号未绑定', 200, ['bind'=>0]);
            }else{
                $this->returnApiData('手机号已绑定', 200, ['bind'=>1]);
            }
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }

    }


    /**
     * 更换绑定手机号
     */
    public function changemobilebind(){
        try{
            $Mobile      = input("post.mobile","","htmlspecialchars,strip_tags");//注册电话
            $ProvingCode = input("post.provingcode","","htmlspecialchars,strip_tags");//验证码
            $Punfu       = new PubfunController();

            $Punfu->PhoneVerification($Mobile, $ProvingCode);
            $user = getUserByToken();
            Db::name('user')->where(['user_id'=>$user['user_id']])->update(['mobile'=>$Mobile]);
            $this->returnApiData('绑定成功', 200);
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
     * 获取用户个人信息
     * 肖亚子
     */
    public function UserPersonal(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断

            $UserData = $Punfu->UserInfo(array("u.token"=>$Token));

            $this->returnApiData("获取成功", 200,$UserData);
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
     * 用户更改平台昵称
     * 肖亚子
     */
    public function UserChangeNickname(){
        try{
            $Token     = input("post.token","","htmlspecialchars,strip_tags");
            $UserName  = input("post.username","","htmlspecialchars,strip_tags");
            $userthumb = input("post.userthumb","","htmlspecialchars,strip_tags");

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断

            $Uid = UserModel::UserFindUid($Token);

            parent::Tpl_Empty($UserName,"请输入昵称",2);
            parent::Tpl_FullSpace($UserName,"请输入昵称",2);
            parent::Tpl_StringLength($UserName,"昵称只能1-12字",3,1,12,2);
            parent::Tpl_Empty($userthumb,"请上传头像",2);
            parent::Tpl_FullSpace($userthumb,"请上传头像",2);

            $UserUp = UserModel::UserUpdate(array("user_id"=>$Uid),array("username"=>$UserName,"userthumb"=>$userthumb));

            if ($UserUp === false){
                $this->returnApiData("昵称更改失败", 400);
            }

            $this->returnApiData("昵称更改成功", 200);
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
     * 获取注册用户我的好友
     * 肖亚子
     */
    public function UserFriends(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Punfu->UserLevelPower($Token);//用户权限验证

            //获取我的好友
            $Time   = strtotime(date("Y-m-d",time()));
            $Fecode = UserModel::UserDataFind(array("u.token"=>array("eq",$Token)),"u.user_id,u.recode,u.floor,u.level");
            $Uid    = $Fecode["user_id"];
            $Floor  = $Fecode["floor"] + 2;

            $C_Condition[] = array("exp","sid = {$Uid} and reid = 0 and reg_time > {$Time}");

            $Condition["reid"] = array("eq",$Uid);
            $Condition["level"] = array("lt",4);
            $NCondition[]       = array("exp"," reid = {$Uid} and reg_time > {$Time}");
            $UnderCondition[]   = array("exp","find_in_set($Uid,path)");
            $NewCondition[]     = array("exp","find_in_set($Uid,path) and reg_time >$Time");

            $UseFriendsr["customer"]    = UserModel::UserCount(array("sid"=>array("eq",$Uid),"reid"=>array("eq",0)));//获取客户人数
            $UseFriendsr["newcustomer"] = UserModel::UserCount($C_Condition);//获取最新客户人数
            $UseFriendsr["directly"]    = UserModel::UserCount($Condition);//获取直属好友人数并且没独立出去
            $UseFriendsr["newdirectly"] = UserModel::UserCount($NCondition);//获取最新直属好友人数
            $UseFriendsr["whole"]       = UserModel::UserAllMyFriends($UnderCondition,$Uid,$Fecode["level"],$Floor);//获取全部好友人数
            $UseFriendsr["newwhole"]    = UserModel::UserAllMyFriends($NewCondition,$Uid,$Fecode["level"],$Floor,2);//获取最新全部好友人数

            $this->returnApiData("获取成功", 200,$UseFriendsr);
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
     * 获取注册用户钱包
     * 肖亚子
     */
    public function UserWallet(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Punfu->UserLevelPower($Token);//用户权限验证

            $Uid = UserModel::UserFindUid($Token);

            $UserData = array();

            //获取我的钱包
            $AccCondition["user_id"]     = array("eq",$Uid);
            $AccCondition["account_tag"] = array("eq",0);
            $AcfCondition["user_id"]     = array("eq",$Uid);
            $AcfCondition["finance_tag"] = array("eq",0);

            $Acc = UserModel::UserAccount($AccCondition,"account_commission_income,account_cash_balance,account_commission_balance,account_commission_expenditure");
           // $Acf = UserModel::UserAccountFinance($AcfCondition,"finance_withdraw,finance_first,finance_second,finance_operations,finance_operationchilds,finance_playerhost");

            //累计提现
            $jrtime = strtotime(date('Y-m-d'));
            $txamount = Db::name('user_withdraw')->where(['user_id'=>$Uid, 'withdraw_status'=>['in', '0,2,3,6']])->sum('withdraw_amount');
            //累计结算
           // $jsamount = Db::name('account_cash'.Tag::getMonth())->where(['user_id'=>$Uid, 'record_addtime'=>['between',$jrtime.','.($jrtime+86400)], 'record_action'=>['in', '802,803']])->sum('record_amount');

            $Sumup = abs($txamount);
            $UserReward["income"]     = $Acc["account_commission_income"]>0?$Acc["account_commission_income"]:"0.00";
            $UserReward["putforward"] = $Acc["account_cash_balance"]>0?$Acc["account_cash_balance"]:"0.00";
            $UserReward["pending"]    = $Acc["account_commission_balance"]>0?$Acc["account_commission_balance"]:"0.00";
            $UserReward["grandtotal"] = abs($Acc["account_commission_expenditure"])>0?abs($Acc['account_commission_expenditure']):"0.00";
            $UserReward["sumup"]      = $Sumup>0?$Sumup:"0.00";
            $UserData["reward"]       = $UserReward;

            //获取收入指南
            $TodayTime     = strtotime(date("Ymd",time()));
            $YesterdayTime = strtotime(date("Ymd",strtotime("-1 day",time())));

            $TodayCondition["user_id"]         = array("eq",$Uid);
            $TodayCondition["account_tag"]     = array("eq",date("Ymd",time()));
            $YesterdayCondition["user_id"]     = array("eq",$Uid);
            $YesterdayCondition[]              = array("exp","record_action=802 or record_action=803 and  record_addtime > {$YesterdayTime} and record_addtime < {$TodayTime}");
            $ThismonthCondition["user_id"]     = array("eq",$Uid);
            $ThismonthCondition["account_tag"] = array("eq",date("Ym",time()));
            $LastmonthCondition["user_id"]     = array("eq",$Uid);
            $LastmonthCondition["finance_tag"] = array("eq",date("Ym",strtotime('-1 month')));

            // 今日、
            $toay = Db::name('account_commission'.Tag::getMonth())->where(['user_id'=>$Uid, 'record_addtime'=>['between',$jrtime.','.($jrtime+86400)], 'record_action'=>['in', '601,602,603,604,606,607,608,609,610,611']])->sum('record_amount');
            $Income["today"] = sprintf('%.2f', $toay);
            //昨日、
            $Yesterday = Db::name('account_finance')->where(['user_id'=>$Uid, 'finance_tag'=>Tag::getYesterday()])->field('finance_xrmd+finance_first+finance_second+finance_operations+finance_operationchilds+finance_playerhost+finance_playerhostzhishu+finance_reward+finance_rewardback+finance_reward10+finance_rewardxrzm as amount')->find();
            $Income["yesterday"] = sprintf('%.2f', $Yesterday['amount']);
            //当月
            $thismonth = Db::name('account_finance')->where(['user_id'=>$Uid, 'finance_tag'=>Tag::getMonth()])->field('finance_xrmd+finance_first+finance_second+finance_operations+finance_operationchilds+finance_playerhost+finance_playerhostzhishu+finance_reward+finance_rewardback+finance_reward10+finance_rewardxrzm as amount')->find();
            $Income["thismonth"] = sprintf('%.2f', $thismonth['amount']+$toay*1);
            //上月
            $lastmonth = Db::name('account_finance')->where(['user_id'=>$Uid, 'finance_tag'=>Tag::getLastMonth()])->field('finance_xrmd+finance_first+finance_second+finance_operations+finance_operationchilds+finance_playerhost+finance_playerhostzhishu+finance_reward+finance_rewardback+finance_reward10+finance_rewardxrzm as amount')->find();
            $Income["lastmonth"] = sprintf('%.2f', $lastmonth['amount']);
            $UserData["income"] = $Income;

            $this->returnApiData("获取成功", 200,$UserData);
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
     * 获取我的推荐人信息
     * 肖亚子
     */
    public function UserRefereeData(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Punfu->UserLevelPower($Token);//用户权限验证
            $user = getUserByToken();
            $Uid  = $user['user_id'];
            $Reid = UserModel::UserDataFind(array("u.user_id"=>array("eq",$Uid)),"u.reid");

            if (!$Reid["reid"]){
                $this->returnApiData("没有推荐人", 400);
            }

            $data['reuser'] = Db::name('user')->field('nickname,avatar,`path`,mobile')->find($Reid);
            $yydr = Db::name('user')->field('nickname,avatar,`path`,mobile')->where("FIND_IN_SET(user_id, '".$user['path']."') and `level`=4")->order('floor desc')->find();
            $data['hasyydr'] = 0;
            if($yydr){
                $data['hasyydr'] = 1;
                $data['yydr'] = $yydr;
            }
            $this->returnApiData("获取成功", 200, $data);
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }

    }
    /**
     * 获取推荐码用户详细
     * 肖亚子
     */
    public function UserReferee(){
        try{
            $Recode = input("post.recode","","htmlspecialchars,strip_tags");//用户推荐码

            parent::Tpl_Empty($Recode,"请输入推荐码",2);
            parent::Tpl_FullSpace($Recode,"请输入推荐码",2);
            parent::Tpl_NoSpaces($Recode,"请输入正确的推荐码",2);
            parent::Tpl_Alphanumeric($Recode,"请输入正确的推荐码",2);
            parent::Tpl_Lengths($Recode,"推荐码正确长度为6-16位",6,16,2);

            $Condition["u.recode"] = array("eq",$Recode);

            $Referee = UserModel::UserDataFind($Condition,"u.nickname,u.avatar");

            if (!$Referee){
                $this->returnApiData("推荐人不存在", 400);
            }

            $this->returnApiData("获取成功", 200,$Referee);
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
     * 用户第一次分享二维码生成
     * 肖亚子
     */
    public function UserQrCode(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");
            $Url   = input("post.url","","htmlspecialchars,strip_tags");

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Punfu->UserLevelPower($Token);//用户权限验证

            parent::Tpl_Empty($Url,"请求错误",2);
            parent::Tpl_FullSpace($Url,"请求错误",2);

            $User = UserModel::UserFinds($Token);

            $Data["url"]         = $Url."?recode={$User['recode']}";
            $Data["Picturename"] = $User["recode"];

            $Invitation = QrCode($Data,1);//生成邀请海报

            $this->returnApiData("获取成功", 200,array("url"=>$Invitation));

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
     * 获取用户邀请注册二维码
     * 肖亚子
     */
    public function UserInviteQRCode(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");
            $Type  = intval(input("post.type"));
            $Url   = input("post.url","","htmlspecialchars,strip_tags");

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Punfu->UserLevelPower($Token);//用户权限验证
            // $Punfu->UserRealName($Token);//验证用户是否实名认证

            if (!in_array($Type,array(1,2,3,4))){
                $this->returnApiData("请选择邀请函背景", 400);
            }

            parent::Tpl_Empty($Url,"请求错误",2);
            parent::Tpl_FullSpace($Url,"请求错误",2);

            $Uid      = UserModel::UserFindUid($Token);
            $User     = UserModel::UserFinds($Token);
            $TrueName = UserauthModel::UserAuthFind($Uid);

            $Data["url"]         = $Url."?recode={$User['recode']}";
            $Data["truename"]    = $TrueName;
            $Data["Picturename"] = $User["recode"];
            $Data["type"]        = $Type;

            $Invitation = QrCode($Data,2);//生成邀请海报

            $this->returnApiData("获取成功", 200,array("url"=>$Invitation));
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }
    }

    /**
     * @param $Mobile   手机号
     * @return mixed
     * 获取用户手机号归属
     * 肖亚子
     */
    private function UserMobileArea($Mobile){
        $MobileUrl = "https://sp0.baidu.com/8aQDcjqpAAV3otqbppnN2DJv/api.php?query={$Mobile}&resource_id=6004&ie=utf8&oe=utf8&format=json";
        $MobileUrl = json_decode(curlGet($MobileUrl),true);

        if (!$MobileUrl){
            $this->returnApiData("请输入正确的手机号", 400);
        }else{
            $Locus = $MobileUrl["data"][0];
            return $Locus;
            // $Data["mobileaddr"] = $Locus["prov"]."/".$Locus["city"]."/".$Locus["type"];
        }
    }

    /**
     *  获取注册用户我的好友列表
     * author@yihong
     */
    public function UserFriendsList(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");
            $Status = intval(input("post.type",1));//状态 1全部好友，2 我的客户 ，3直属好友
            $Page   = intval(input("post.page",1));//分页默认第一页
            $Psize  = intval(input("post.psize",10));//分页条数默认10条
            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            //$Punfu->UserLevelPower($Token);//用户权限验证
            //获取我的好友
            $Fecode = UserModel::UserDataFind(array("u.token"=>array("eq",$Token)),"u.user_id,u.recode,u.floor,u.level");
            $Uid    = $Fecode["user_id"];
            $Floor  = $Fecode["floor"] + 2;
            $filed = "user_id,nickname,username,avatar,userthumb,level";
            if($Status == UserModel::ALL_USER_FRIEND){ //全部好友
                $Where[] = array("exp","find_in_set($Uid,path)");
                $userid = UserModel::UserAllMyFriends($Where,$Uid,$Fecode["level"],$Floor,3);
                $count= count($userid);
                $List = UserModel::UserOperateListByUserIds($userid,$filed,$Page,$Psize);//获取全部好友
            }elseif ($Status == UserModel::USER_CUSTOMER){ //我的客户
                $Where = array("sid"=>array("eq",$Uid),"reid"=>array("eq",0));
                $count    = UserModel::UserCount($Where);//获取客户人数
                $List    = UserModel::getUserFriendList($Where,$filed, $Page,$Psize);//获取我的好友
            }else{ //我的直属好友
                $Where["reid"] = array("eq",$Uid);
                $Where["level"] = array("lt",4); //todo 好友直属部包含运营达人与玩主
                $filed .= ",mobile";
                $count  = UserModel::UserCount($Where);//获取直属好友人数并且没独立出去
                $List   = UserModel::getUserFriendList($Where,$filed, $Page,$Psize);//获取我的好友
            }

            foreach ($List as $Key => $Val){
                if($Status != UserModel::ALL_USER_FRIEND && $Status!= UserModel::USER_CUSTOMER){
                    //获取好友直属数量
                    if($Val && $Val['user_id']){
                        $map["reid"] = array("eq",$Val['user_id']);
                        $List[$Key]['order_count'] = UserModel::UserCount($map);//获取直属好友人数并且没独立出去
                    }
                }else{
                    //获取好友下单数量
                    if($Val && $Val['user_id']){
                        $List[$Key]['order_count'] = OrderModel::GetOrderCountByUserId($Val['user_id']);
                    }
                }

                $List[$Key]['level'] = UserModel::getLeveName($Val['level']); //好友级别

                if ($Val["username"]){
                    $List[$Key]["nickname"] = $Val["username"];
                    unset($List[$Key]["username"]);
                }
                if ($Val["userthumb"]){
                    $List[$Key]["avatar"] = $Val["userthumb"];
                    unset($List[$Key]["userthumb"]);
                }
            }

            $UseFriendsr['count'] = $count;
            $UseFriendsr['list'] = $List;
            $this->returnApiData("获取成功", 200,$UseFriendsr);
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
     * 获取我的订单列表
     * 肖亚子
     */
    public function UserOrderList(){
        try{
            $Token  = input("post.token","","htmlspecialchars,strip_tags");
            $Page   = intval(input("post.page",1));//分页默认第一页
            $Psize  = intval(input("post.psize",10));//分页条数默认10条
            $Type   = intval(input("post.type",'1'));//订单状态默认1 1全部 2待付款 3已付款 4已完成 5退款

            $Punfu  = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Userid = UserModel::UserFindUid($Token);

            $Condition["o.user_id"]      = array("eq",$Userid);
            $Condition["o.order_status"] = array("neq",0);

            if ($Type == 2){
                $Condition["o.order_status"] = array("eq",1);
            }elseif ($Type == 3){
                $Condition[] = array("exp","o.order_status >= 2 and o.order_status < 4");
            }elseif ($Type == 4){
                $Condition["o.order_status"] = array("eq",4);
            }elseif ($Type == 5){
                $Condition["o.order_status"] = array("eq",6);
            }

            $OrderList = OrderModel::OrderList($Condition,$Page,$Psize);
           // $this->returnApiData("获取成功", 200,$OrderList);
            if ($OrderList){
                $OrderList = self::OrderStatus($OrderList);
            }
            $this->returnApiData("获取成功", 200,$OrderList);
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
     * 用户订单详情
     * 肖亚子
     */
    public function UserOrderInfo(){
        try{
            $Token   = input("post.token","","htmlspecialchars,strip_tags");
            $Orderid = intval(input("post.order_id",0));//订单ID

            $Punfu   = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断

            parent::Tpl_Empty($Orderid,"请求错误",2);

            $Uid = UserModel::UserFindUid($Token);

            $Condition["o.order_id"] = array("eq",$Orderid);
            $Condition["o.user_id"] = array("eq",$Uid);

            $OrderInfo = OrderModel::getOrderInfoByOrderId($Condition);

            if($OrderInfo){
                $OrderData = self::OrderFindStatus($OrderInfo,$Uid);

                $this->returnApiData("获取成功", 200,$OrderData);
            }else{
                $this->returnApiData("订单不存在", 400);
            }

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
     * 获取达人后台订单数据
     * 肖亚子
     */
    public function OrderDaren(){
        try{
            $Token = input("post.token","","htmlspecialchars,strip_tags");
            $Page  = intval(input("post.page",1));//分页默认第一页

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断

            $Level = UserModel::UserDataFind(array("u.token"=> array("eq",$Token)),"u.level");

            if (!in_array($Level["level"],array(4,5))){
                $this->returnApiData("权限不足", 400);
            }

            $Uid = UserModel::UserFindUid($Token);

            $Condition[] = array("exp","(o.order_status >= 2 or o.order_status <= 4) and (p.userid_first = {$Uid} or p.userid_second = {$Uid} or p.userid_operations = {$Uid} or p.userid_operations_child = {$Uid} or p.userid_playerhost_child = {$Uid} )");

            $List = OrderModel::OrderDarenList($Condition,$Page,10);

            $this->returnApiData("获取成功", 200,$List);

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
     * 用户确认收货
     * 肖亚子
     */
    public function OrderConfirm(){
        try{
            $Token   = input("post.token","","htmlspecialchars,strip_tags");
            $OrderId = intval(input("post.order_id",1));//订单id

            $Punfu = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断

            $Uid = UserModel::UserFindUid($Token);

            $Condition["order_id"]        = array("eq",$OrderId);
            $Condition["user_id"]         = array("eq",$Uid);
            $Condition["order_isexpress"] = array("eq",2);
            $Condition["order_status"]    = array("eq",3);

            //查询订单是不是待收货
            $OrderFind = OrderModel::UserOrderFind($Condition);
            if (!$OrderFind){
                $this->returnApiData("订单信息错误,确认收货失败", 400);
            }

            $DataCondition["order_id"] = array("eq",$OrderId);
            $DataCondition["user_id"]  = array("eq",$Uid);
            Db::startTrans();
            //订单改为收货完成
            $OrderUp = OrderModel::OrderUpdate($DataCondition,array("order_status"=>4));
            $pm = new  ProcedureModel();
            $res6 = $pm->execute('merchant_kuaidi_income', $OrderId, '@error');
            if($OrderUp === false || !$res6){
                Db::rollback();
                $this->returnApiData("确认收货失败", 400);
            }
            Db::commit();
            $this->returnApiData("收货成功", 200);
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }
    }
    /**
     * @param $List 订单数据
     * 0订单过期 1待付款 2待发货 3待预约发货 4待收货 5待使用 6预约订单已使用 7已完成  8取消订单 9申请退款 10 申请换货
     * 订单列表状态转换
     * 肖亚子
     */
    private function OrderStatus($List){
        $Time = strtotime("-30 minutes", time());

        $OrderList = array();
        foreach ($List as $Key => $Val){

            if ($Val["order_status"] == 1 &&  $Val["order_addtime"] < $Time){
                continue;
            }

            if ($Val["order_status"] == 6){
                $List[$Key]["order_status"] = 9;
            }

            if ($Val["order_isexpress"] == 1){
                if ($Val["order_reservation"] == 1 && $Val["distributiontag"] == 0){//到店预约商品
                    $Condition["cc.order_id"] = array("eq",$Val["order_id"]);
                    $Condition["cc.status"]   = array("elt",2);
                    $Condition["re.reservation_status"] = array("gt",0);
                    $Condition["re.reservation_status"] = array("lt",4);
                    $CodeCondition["cc.order_id"] = array("eq",$Val["order_id"]);
                    $CodeCondition["cc.status"]   = array("elt",2);

                    $ReservationList = OrderModel::OrderConsumeCodeList($Condition);
                    $CodeList        = OrderModel::OrderConsumeCodeNoList($CodeCondition);

                    if ($ReservationList){
                        $ReCount   = count($ReservationList);
                        $CodeCount = count($CodeList);

                        if ($ReCount != $CodeCount){
                            if ($Val["order_status"] == 2){
                                $List[$Key]["order_status"] = 6;
                            }
                        }else{
                            foreach ($CodeList as $K => $V){
                                if ($V["status"] == 1){
                                    if ($Val["order_status"] == 2){
                                        $List[$Key]["order_status"] = 5;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($Val["order_status"] == 4){
                            $List[$Key]["order_status"] = 7;
                        }
                    }else{
                        if ($Val["order_status"] == 2){
                            $List[$Key]["order_status"] = 5;
                        }elseif ($Val["order_status"] == 4){
                            $List[$Key]["order_status"] = 7;
                        }
                    }

                }else{//到店免预约商品/电话预约
                    if ($Val["order_status"] == 2){
                        $List[$Key]["order_status"] = 5;
                    }elseif ($Val["order_status"] == 4){
                        $List[$Key]["order_status"] = 7;
                    }
                }

            }elseif ($Val["order_isexpress"] == 2 && $Val["order_reservation"] == 1){
                $OrderFahuo = OrderModel::OrderReservationFahuoFind(array("rf.order_id"=>array("eq",$Val["order_id"])));

                if ($OrderFahuo){
                    if ($Val["order_status"] == 3){
                        $List[$Key]["order_status"] = 4;
                    }elseif ($Val["order_status"] == 4){
                        $List[$Key]["order_status"] = 7;
                    }
                }else{
                    if ($Val["order_status"] == 2){
                        $List[$Key]["order_status"] = 3;
                    }
                }
            }elseif ($Val["order_isexpress"] == 2 && $Val["order_reservation"] == 2){
                if ($Val["order_status"] == 3){
                    $List[$Key]["order_status"] = 4;
                }elseif ($Val["order_status"] == 4){
                    $List[$Key]["order_status"] = 7;
                }
            }

            unset($List[$Key]["order_isexpress"]);
            unset($List[$Key]["order_reservation"]);
            unset($List[$Key]["order_addtime"]);
            unset($List[$Key]["distributiontag"]);

            $OrderList[] = $List[$Key];
        }

        return $OrderList;
    }

    /**
     * @param $Data 转换数据
     * 订单详情转换状态
     * 肖亚子
     */
    private function OrderFindStatus($Data,$Uid){
        $OrderCode = array();
        $Count     = 0;
        $ShopCondition[]  = array("exp","(m.merchant_id = {$Data["merchant_id"]} or m.parent_id = {$Data["merchant_id"]}) and m.merchant_status = 2 and m.merchant_open = 1 and r.status = 1");
        $ShopList         = ProductModel::ShopList($ShopCondition,"m.merchant_alias as merchant_name,m.merchant_400tel,m.merchant_ssq,m.merchant_address,m.merchant_lng,m.merchant_lat");

        if ($Data["endusetime"] < time()){
            $EndTime = 2;//订单商品结束时间过期等于2
        }else{
            $EndTime = 1;//订单商品结束时间未过期等于1
        }
        if ($Data["status"] == 6){
            $Data["status"] = 9;
        }

        if ($Data["isexpress"] == 1 && $Data["reservation"] == 1){

            $Condition["cc.order_id"]           = array("eq",$Data["order_id"]);
            $Condition["cc.status"]             = array("elt",2);
            $Condition["re.reservation_status"] = array("gt",0);
            $Condition["re.reservation_status"] = array("lt",4);
            $CodeCondition["cc.order_id"]       = array("eq",$Data["order_id"]);
            $CodeCondition["cc.status"]         = array("elt",2);
            $CodeCondition["cc.user_id"]        = array("eq",$Uid);

            $ReservationList = OrderModel::OrderConsumeCodeList($Condition);
            $CodeList        = OrderModel::OrderConsumeCodeNoList($CodeCondition);

            if ($ReservationList){
                $ReCount   = count($ReservationList);
                $CodeCount = count($CodeList);

                if ($ReCount != $CodeCount){
                    if ($Data["status"] == 2){
                        $Data["status"] = 6;
                    }
                }else{
                    foreach ($CodeList as $Key => $Val){
                        if ($Val["status"] == 1){
                            if ($Data["status"] == 2){
                                $Data["status"] = 5;
                                break;
                            }
                        }
                    }
                }

                if ($Data["status"] == 4){
                    $Data["status"] = 7;
                }
            }else{
                if ($Data["status"] == 2){
                    $Data["status"] = 5;
                }elseif ($Data["status"] == 4){
                    $Data["status"] = 7;
                }
            }

            $OrderCodeCondition["cc.order_id"] = array("eq",$Data["order_id"]);
            $OrderCodeCondition["cc.user_id"]  = array("eq",$Uid);

            if($Data["distributiontag"] == 0){
                $CodeList  = OrderModel::OrderConsumeCodeList($OrderCodeCondition);

                if ($CodeList){
                    foreach ($CodeList as $K => $V){
                        if (!$V["reservation_id"] || ($V["reservation_no"] && ($V["reservation_status"] == 0||$V["reservation_status"] == 4))){//未预约或取消了预约
                            $Codes["consume_code"] = $V["consume_code"];
                            $Codes["consume_status"]       = $V["status"];
                            $Codes["consume_type"]         = 1;
                            $OrderCode[]           = $Codes;
                        }elseif ($V["reservation_id"] || $V["reservation_no"] && $V["reservation_status"] == 1){//已预约待使用
                            $Codes["consume_code"] = $V["consume_code"];
                            $Codes["consume_status"]       = $V["status"];
                            $Codes["consume_type"]         = 2;
                            $OrderCode[]           = $Codes;
                        }
                    }
                }
            }

            $Count = count($OrderCode);
        }elseif ($Data["isexpress"] == 1 && $Data["reservation"] >= 2 ){
            if ($Data["status"] == 2){
                $Data["status"] = 5;
            }elseif ($Data["status"] == 4){
                $Data["status"] = 7;
            }

            if ($Data["distributiontag"] == 0){
                $OrderCodeCondition["cc.order_id"] = array("eq",$Data["order_id"]);
                $OrderCodeCondition["cc.user_id"]  = array("eq",$Uid);
                $CodeList  = OrderModel::OrderConsumeCodeNoList($OrderCodeCondition);

                if ($CodeList){
                    foreach ($CodeList as $K => $V){
                        $Codes["consume_code"] = $V["consume_code"];
                        $Codes["consume_status"] = $V["status"];
                        $Codes["consume_type"]   = 2;
                        $OrderCode[]             = $Codes;
                    }
                }
            }

        } elseif ($Data["isexpress"] == 2 && $Data["reservation"] == 1){
            $OrderFahuo = OrderModel::OrderReservationFahuoFind(array("rf.order_id"=>array("eq",$Data["order_id"])));
            if ($OrderFahuo){
                if ($Data["status"] == 3){
                    $Data["status"] = 4;
                }elseif ($Data["status"] == 4){
                    $Data["status"] = 7;
                }
            }else{
                if ($Data["status"] == 2){
                    $Data["status"] = 3;
                }
            }
        }elseif ($Data["isexpress"] == 2 && $Data["reservation"] == 2){
            if ($Data["status"] == 3){
                $Data["status"] = 4;
            }elseif ($Data["status"] == 4){
                $Data["status"] = 7;
            }
        }

        unset($Data["merchant_id"]);
        unset($Data["distributiontag"]);

        $Data["endtime"]    = $EndTime;
        $Data["code_count"] = $Count;
        $Data['payendtime'] = $Data['addtime']+1800;
        $Data["shop"]       = $ShopList;
        $Data["code"]       = $OrderCode;
        return $Data;
    }

    /**
     * 获取消息
     */
    public function getSysMsg(){

        try{
            $Token   = input("post.token","","htmlspecialchars,strip_tags");
            $Punfu   = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Uid = UserModel::UserFindUid($Token);
            //'0所有人，1安卓，2iOS，3微信 ,4APP',
            $touser = $this->post('touser','web');
            if($touser == "android"){
                $condition['m.msg_user']   = array('in','0,1,4');
            }else if($touser == "ios"){
                $condition['m.msg_user']   = array('in','0,2,4');
            }else{
                $condition['m.msg_user']   = array('in','0,3');
            }
            $page = $this->post('page',1);
            $pagesize = 10;
            $condition['m.msg_status'] = 2;//已推送的消息
            $count =Db::name('msg m')->where($condition)->count();
            $list = Db::name('msg m') ->field('m.msg_id,m.msg_type,m.msg_title,m.msg_content,m.send_time')
                ->where($condition)
                ->page($page, $pagesize)
                ->order('m.msg_id desc')
                ->select();
            $newlist = [];
            $condition['r.user_id'] = $Uid;
            $readcount= Db::name('msg m')
                    ->join('msg_read r','r.msg_id=m.msg_id')
                    ->where($condition)
                    ->count();//当前用户已读总条数
            $unread = $count-$readcount;//剩余未读条数
            foreach ($list as &$val){
                if($val){
                    $readinfo = Db::name('msg_read')->where(array('user_id'=>$Uid,'msg_id'=>$val['msg_id']))->find();
                    if($readinfo){
                        $val['isread'] = 1;
                    }else{
                        $val['isread'] = 0;
                    }
                    unset($val['user_id']);
                    $newlist[]=$val;
                }
            }
            $data['count'] = $count;
            $data['unread'] = $unread;
            $data['list'] = $newlist;
            $this->returnApiData('获取成功', 200, $data);
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }
    }
    /**
     * 标记已读消息
     */
    public function readMsg(){
        try{
            $Token   = input("post.token","","htmlspecialchars,strip_tags");
            $Punfu   = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Uid = UserModel::UserFindUid($Token);
            $msgid = $this->post('msg_id');
            $umcount = Db::name('msg_read')->where(array('msg_id'=>$msgid,'user_id'=>$Uid))->count();
            if(!$umcount){ //已读
                $data['msg_id'] = $this->post('msg_id');
                $data['user_id'] = $Uid;
                $data['read_time'] = time();
                Db::name('msg_read')->insert($data);
            }
            $this->returnApiData('获取成功', 200);
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }
    }


    /**
     * 获取消息详情
     */
    public function getSysMsgInfo(){

        try{
            $Token   = input("post.token","","htmlspecialchars,strip_tags");
            $Punfu   = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            //  $Uid = UserModel::UserFindUid($Token);
            //'0所有人，1安卓，2iOS，3微信 ,4APP',
            $msgid = $this->post('m_id');
            if(!$msgid){
                $this->returnApiData('消息ID不能为空', 400);
            }
            $condition['msg_status'] = 2;//已推送的消息
            $condition['msg_id'] = $msgid;
            $info = Db::name('msg')
                ->field('msg_id,msg_type,msg_title,msg_content,send_time')
                ->where($condition)
                ->find();
            $this->returnApiData('获取成功', 200, $info);
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }
    }
    /**
     * 用户取消预约
     */
    public function cancelUserReservation(){
        try{
            $Token   = input("post.token","","htmlspecialchars,strip_tags");
            $Punfu   = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
            $Uid = UserModel::UserFindUid($Token);
            $resId   = input("post.res_id");
            $where['user_id'] = $Uid;
            $where['reservation_id'] = $resId;
            $where['reservation_status'] = 1; //已预约成功
            $field = 'r.reservation_id,pr.calendar,r.reservation_no,r.reservation_transaction_id as transaction_id
            ,r.reservation_payment,r.reservation_addprice,r.consume_code_id,r.reservation_addprice as totalfee';
            $quxiaoyuyue_day = Db::name('parameter')->where(array('key'=>'quxiaoyuyue_day'))->value('value');
            if($quxiaoyuyue_day){
                $rs = Db::name('order_user_reservation r')
                    ->field($field)
                    ->join('product_reservationday pr','pr.reservationday_id=r.reservationday_id')
                    ->where($where)->find();
                //判断是否满足预约提前取消条件 公式 预约时间>= 当前时间+提前取消时间 ”列：2018-07-05> 2018-07-(01+2) “
                if($rs && $rs['calendar'] >=  strtotime("+{$quxiaoyuyue_day} day")){
                    Db::startTrans();
                    $data['reservation_status'] = 4;
                    $res = Db::name('order_user_reservation')->where(array('reservation_id'=>$resId))->update($data);
                    if($res){
                        Db::commit();
                        Db::name('order_consume_code')->where(array('consume_code_id'=>$rs['consume_code_id']))->update(array('status'=>1));
                        if($rs['reservation_no']){ //有预约加价退款
                            $pm = new Paymodel();
                            $pm->wxRefund($rs);
                        }
                    }else{
                        Db::rollback();
                        $this->returnApiData('取消预约失败', 400);
                    }
                }else{
                    $this->returnApiData('没有查到预约信息', 400);
                }
            }
            $this->returnApiData('取消预约成功', 200);
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
     * 用户进行锁粉
     * 肖亚子
     */
    public function UserLockPowder(){
        try{
            $Token  = input("post.token","","htmlspecialchars,strip_tags");
            $Recode = input("post.recode","","htmlspecialchars,strip_tags");
            $Punfu  = new PubfunController();
            $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断

            parent::Tpl_Empty($Recode,"锁粉失败",2);
            parent::Tpl_FullSpace($Recode,"锁粉失败",2);
            parent::Tpl_StringLength($Recode,"锁粉失败",3,6,20,2);

            $Uid  = UserModel::UserFindUid($Token);
            $User = UserModel::UserDataFind(array("u.user_id" => array("eq",$Uid)),"u.sid,u.nickname");

            if ($User["sid"]){
                $this->returnApiData('已被锁粉', 400);
            }

            $Condition["u.recode"]  = array("eq",$Recode);
            $Condition["u.user_id"] = array("neq",$Uid);

            $ReUser = UserModel::UserDataFind($Condition,"u.user_id,u.level");
            if ($ReUser && $ReUser["level"] > 1){
                $Data["sid"] = $ReUser["user_id"];
                $Data["lock_time"] = time();
            }else{
                GLog('用户锁粉=>推荐用户',json_encode($ReUser));
                $this->returnApiData('锁粉失败', 400);
            }
            $UserRid = UserModel::UserUpdate(array("user_id"=>array("eq",$Uid)),$Data);
            if (!$UserRid){
                GLog('用户锁粉SQL',Db::name("user")->getLastSql());
                $this->returnApiData('锁粉失败', 400);
            }
            //微信锁粉通知
            UserModel::suoFenMsg($User['nickname'],$ReUser["user_id"]);

            $this->returnApiData('锁粉成功', 200);
        }catch (Exception $e){
            parent::Tpl_Abnormal($e->getMessage());
        }

    }


    /**
     * 刷新用户位置
     */
    public function refreshLocation(){
        $user = getUserByToken();
        $y = $this->post('lat', '');
        $x = $this->post('lng', '');

        $M_PI = 3.14159265358979324;
        $a = 6378245.0;
        $ee = 0.00669342162296594323;
        $x_pi = $M_PI * 3000.0 / 180.0;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
        $data['lng'] = $z * cos($theta) + 0.0065;
        $data['lat'] = $z * sin($theta) + 0.006;
        Db::name('user')->where(['user_id'=>$user['user_id']])->update($data);
        $this->returnApiData('刷新成功', 200);
    }


    /**
     * 达人后台
     */
    public function talentShow(){
        $Token = input("post.token","","htmlspecialchars,strip_tags");
        $paged   = intval(input("post.page",1));//分页默认第一页
        $psize  = intval(input("post.psize",10));//分页条数默认10条
        $Punfu = new PubfunController();
        $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
        $Uid      = UserModel::UserFindUid($Token);
        $rs = UserModel::getUserFriendsByUserId($Uid,$paged,$psize);
        $this->returnApiData("获取成功", 200,$rs);
    }

    /**
     * 10万激励活动
     */
    public function directSelling(){
        $Token = input("post.token","","htmlspecialchars,strip_tags");
        $Punfu = new PubfunController();
        $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
        $user = getUserByToken();
        if($user['level'] == 1){
            $this->returnApiData('请先注册成达人才能参与活动噢',400);
        }
        $date = date('Ymd');
        $hours = date('H');
        $where['ac.record_action'] = CurrencyAction::CommissionActivesReward;
        if($hours>=15 && $hours<17){ //15点到17点时间段展示15点前的中奖数据
            $startime = strtotime(date('Y-m-d 15:00:00'));
            $endtime = strtotime(date('Y-m-d 16:59:59'));
            $where = $this->TimeContrast($startime,$endtime,'ac.record_addtime',$where);
        }elseif($hours>17 && $hours<=23){ //17点到23点时间段展示直卖份数
            $num = Db::table('view_zhimaiuser23')->where(['userid_first'=>$user['user_id'],'uptime'=>$date])->value('num');
        }elseif($hours>23 && $hours<24){ //23点到24点时间段展示17点到23点的中奖数据
            $startime = strtotime(date('Y-m-d'.' 23:00:00'));
            $endtime = strtotime(date('Y-m-d'.' 23:59:59'));
            $where = $this->TimeContrast($startime,$endtime,'ac.record_addtime',$where);
        }else{ //0点到15点时间段展示直卖份数
            $num = Db::table('view_zhimaiuser')->where(['userid_first'=>$user['user_id'],'uptime'=>$date])->value('num');
        }


        $arm = new AccountRecordModel();
        $startmonth = date('Ym');
        $res = $arm->getByShareCondition($where,'commission',$startmonth);

        $list =[];
        foreach ( $res['list'] as $k=>$v){
            $list[$k]['mobile'] = Func_Name($v['mobile'],3,4);
            $list[$k]['nickname'] = '*'.mb_substr($v['nickname'],0,1,'utf-8').'~';
            $list[$k]['record_addtime'] = $v['record_addtime'];
        }
        $data['list'] = !empty($list)?$list:[];
        $data['nowtime'] = time();
        $data['num'] = $num?$num:0;
        $data['ftime1'] = strtotime(date('Y-m-d'));
        $data['ftime2'] = strtotime(date('Y-m-d'.' 15:00:00'));
        $data['stime1'] = strtotime(date('Y-m-d'.' 17:00:00'));
        $data['stime2'] = strtotime(date('Y-m-d'.' 23:00:00'));
        $this->returnApiData("获取成功", 200,$data);
    }

    /**
     *活动列表
     */
    public function getActivesList(){
        $Token = input("post.token","","htmlspecialchars,strip_tags");
        $paged   = intval(input("post.page",1));//分页默认第一页
        $psize  = intval(input("post.psize",10));//分页条数默认10条
        $citycode = $this->post('citycode', '510100');
        $Punfu = new PubfunController();
        $Punfu->UserLoginStatus($Token,$this->headerData);//用户判断
        $condition['status'] = 1;
        $condition['citycode'] = [['=', $citycode], ['=', ''], 'or'];
        $list = Db::name('actives')->field('a_id,thumb,title,starttime,endtime,addtime,`type`')->where($condition)->page($paged, $psize)->order('sort asc')->select();

        $this->returnApiData("获取成功", 200,$list);
    }


    /**
     * 新人免单活动页面
     */
    public function activeXrmd(){
        $user = getUserByToken();
        if($user['level'] == 1){
            $this->returnApiData('请先注册成达人才能参与活动噢',400);
        }
        //查询本月参与次数
        $data['innum'] = Db::name('order o')
                    ->join('jay_order_product p','p.order_id=o.order_id','left')
                    ->where(['o.order_status'=>['gt',1], 'p.product_returnall'=>1, 'o.user_id'=>$user['user_id'], "FROM_UNIXTIME(o.order_addtime,'%Y%m')"=>date('Ym')])
                    ->count();
        //板块规则
        $data['rule'] = Db::name('page')->where(['id'=>10])->value('content');
        //新人免单商品
        $data['products'] = Db::name('product')->field('product_id,product_name,product_pic')->where(['product_status'=>1, 'product_reviewstatus'=>2, 'product_del'=>0,'sold_out'=>0, 'product_returnall'=>1])->order('product_id desc')->select();
        $this->returnApiData("获取成功", 200, $data);
    }


    /**
     * 升级奖励活动页面
     */
    public function activesjjl(){
        $user = getUserByToken();
        if($user['level'] == 1){
            $this->returnApiData('请先注册成达人才能参与活动噢',400);
        }
        $data['count2'] = Db::name('user')->where(['level'=>2,'reid'=>$user['user_id']])->count();
        $data['count3'] = Db::name('user')->where(['level'=>3,'reid'=>$user['user_id']])->count();
        $data['count4'] = Db::name('user')->where(['level'=>4,'reid'=>$user['user_id']])->count();
        $this->returnApiData("获取成功", 200, $data);
    }

    /**
     * 单品直卖活动页面-统计份数
     */
    public function activesdp(){
        $user = getUserByToken();
        if($user['level'] == 1){
            $this->returnApiData('请先注册成达人才能参与活动噢',400);
        }
        $id = $this->post('id', 0);
        $data = Db::name('actives')->find($id);
        if(!$data){
            $this->returnApiData("没有数据", 400, $data);
        }
        $whereproduct = ' p.product_id in ('.trim($data['productids'],',').') ';
        $condition = ' and o.order_addtime between '.$data['starttime'].' and '.$data['endtime'];
        //自己直卖
        $sql = "SELECT t.nickname, t.mobile, sum(t.num) num from (
                      select p.userid_first, u.nickname, u.mobile, u.`level`, sum(p.num) num, reu.nickname parentnickname, reu.mobile parentmobile from jay_order o
                      left join jay_order_product p on p.order_id=o.order_id
                      left join jay_user u on u.user_id = p.userid_first
                      left join jay_user reu on reu.user_id = u.reid
                      where".$whereproduct." and p.product_returnall=0 and o.user_id!=p.userid_first and p.userid_first>0 and o.order_status>1 ".$condition."
                      group by p.userid_first
                        union all
                      select p.userid_second userid_first, fen.nickname, fen.mobile, fen.`level`, sum(p.num) num, reu.nickname parentnickname, reu.mobile parentmobile from jay_order o
                      left join jay_order_product p on p.order_id=o.order_id
                      left join jay_user byer on byer.user_id = o.user_id
                      left join jay_user fen on fen.user_id = p.userid_second
                      left join jay_user reu on reu.user_id = fen.reid
                      where ".$whereproduct." and p.product_returnall=0 and byer.`level`>1 and o.user_id=p.userid_first and o.order_status>1 ".$condition."
                      group by p.userid_second
                      ) t where t.userid_first = ".$user['user_id']."
                      group by t.userid_first
                      order by num desc;";

        //自己直卖
        $sql2 = "SELECT t.nickname, t.mobile, sum(t.num) num from (
                      select p.userid_first, u.nickname, u.mobile, u.`level`, sum(p.num) num, reu.nickname parentnickname, reu.mobile parentmobile from jay_order o
                      left join jay_order_product p on p.order_id=o.order_id
                      left join jay_user u on u.user_id = p.userid_first
                      left join jay_user reu on reu.user_id = u.reid
                      where".$whereproduct." and p.product_returnall=0 and o.user_id!=p.userid_first and p.userid_first>0 and o.order_status>1 ".$condition."
                      group by p.userid_first
                        union all
                      select p.userid_second userid_first, fen.nickname, fen.mobile, fen.`level`, sum(p.num) num, reu.nickname parentnickname, reu.mobile parentmobile from jay_order o
                      left join jay_order_product p on p.order_id=o.order_id
                      left join jay_user byer on byer.user_id = o.user_id
                      left join jay_user fen on fen.user_id = p.userid_second
                      left join jay_user reu on reu.user_id = fen.reid
                      where ".$whereproduct." and p.product_returnall=0 and byer.`level`>1 and o.user_id=p.userid_first and o.order_status>1 ".$condition."
                      group by p.userid_second
                      ) t
                      group by t.userid_first
                      HAVING num >= ".$data['rwnum1']."
                      order by num desc;";

        $zhimai = Db::query($sql);
        $data['zhimai'] = $zhimai[0]['num']*1;
        $list = Db::query($sql2);
        foreach ($list as $k=>$v){
            $list[$k]['mobile'] = substr($v['mobile'], 0, 3). '***' .substr($v['mobile'], 7,4);
            $list[$k]['nickname'] = '*'.mb_substr($v['nickname'],0,1,'utf-8').'~';
        }
        $data['mingdan'] = $list;
        $this->returnApiData("获取成功", 200, $data);
    }

}