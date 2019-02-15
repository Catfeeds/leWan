<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/1
 * Time: 9:51
 * 用户模型
 * 肖亚子
 */

namespace app\api\model;
use think\Db;

class UserModel{

    const ALL_USER_FRIEND = 1;//获取用户全部好友
    const USER_CUSTOMER = 2;//我的客户

    public static function TableName(){
        return Db::name("user");
    }

    /**
     * @param array $Condition  查询条件
     * @param string $Filed     获取字段
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户信息
     * 肖亚子
     */
    public static function UserDataFind($Condition = array(),$Filed = ""){
        if (!$Filed){
            $Filed = "u.user_id as uid,u.token,u.nickname,u.avatar,u.level,u.status";//uc.wxopen_id
        }
        $Data = self::TableName()
                ->alias("u")
                ->field($Filed)
                ->where($Condition)
                ->find();

        return $Data;
   }

    /**
     * @param $Token 用户token
     * @return mixed
     * 获取用户uid
     */
   public static function UserFindUid($Token){
        $Data = self::TableName()->where('token','=',$Token)->value("user_id");
        return $Data;
   }

    /**
     * @param $Token          用户token
     * @return \think\db\Query
     * 获取用户等级,推荐码
     */
   public static function UserFinds($Token){
       $Data = self::TableName()->field("user_id,recode,level,lookover")->where('token','=',$Token)->find();
       return $Data;
   }

    /**
     * @param array $Condition     查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户
     */
   public static function UserConnectFind($Condition = array()){
        $Data = Db::name("user_connect")->where($Condition)->find();

        return $Data;
   }

    /**
     * @param array $Uid   用户id
     * @param array $Prid  商品id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户是否有新人免单订单
     * 肖亚子
     */
   public static function UserFreesheet($Uid,$Prid){
       $Condition["o.user_id"]           = array("eq",$Uid);
       $Condition[]                      = array("exp","o.order_status >= 2 and o.order_status < 5");
       $Condition["p.product_id"]        = array("eq",$Prid);
       $Condition["p.product_returnall"] = array("eq",1);

       $Data = Db::name("order")
                   ->alias("o")
                   ->field("o.order_id")
                   ->join("order_product p","p.order_id = o.order_id","left")
                   ->where($Condition)
                   ->find();

       return $Data;
   }
    /**
     * @param $Data       添加内容
     * @return int|string
     * 添加用户
     * 肖亚子
     */
   public static function UserAdd($Data){
        $User = self::TableName()->insertGetId($Data);
        return $User;
   }

    /**
     * @param $Data       添加内容
     * @return int|string
     * 添加用户第三方关联信息
     * 肖亚子
     */
   public static function UserConnectAdd($Data){
       $Connect = Db::name("user_connect")->insert($Data);
       return $Connect;
   }

    /**
     * @param array $Condition     修改条件
     * @param $Data                修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改用户信息
     * 肖亚子
     */
   public static function UserUpdate($Condition = array(),$Data){
       $Data = self::TableName()->where($Condition)->update($Data);

       return $Data;
   }

    /**
     * @param $Uid 用户uid
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改用户实时活跃时间
     * 肖亚子
     */
   public static function UserUpdateTime($Uid){
       $data['up_time'] = time();
       $data['viewtimes_all'] = ['exp', 'viewtimes_all+1'];
       $data['viewtimes_everyday'] = ['exp', 'viewtimes_everyday+1'];
       $Data = self::TableName()->where("user_id","=",$Uid)->update($data);
   }
    /**
     * @param array $Condition  修改条件
     * @param $Data             修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改用户第三方关联信息
     * 肖亚子
     */
    public static function UserConnectUpdate($Condition = array(),$Data){
           $Data = Db::name("user_connect")->where($Condition)->update($Data);
           return $Data;
    }

    /**
     * @param array $Condition  查询条件
     * @return int|string
     * 获取用户人数
     */
    public static function UserCount($Condition = array()){
        $Count = self::TableName()->where($Condition)->count();
        return $Count;
    }


    /**
     * @param $Condition  查询条件
     * @param int $Uid    用户id
     * @param int $Type   1 获取全部用户 2获取最新用户
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取下级为独立团队用户
     * 肖亚子
     */
    public static function UserOperateUserids($Condition,$Uid = 0,$Type = 1,&$userid=array()){
        $Time = strtotime(date("Y-m-d",time()));
        if ($Uid>0){
            $Condition["reid"] = $Uid;
        }
        $List  = Db::name("user")->field("user_id,reg_time")->where($Condition)->select();
        foreach ($List as $key=>$value){
            if ($Type == 1){
                $userid[$value["user_id"]]=$value["user_id"];
            }else{
                if ($value["reg_time"] > $Time){
                    $userid[$value["user_id"]]=$value["user_id"];
                }
            }
            self::UserOperateUserids($Condition, $value["user_id"],$Type,$userid); //调用函数，传入参数，继续查询下级
        }
    }

    /**
     * @param $Condition  查询条件
     * @param int $Uid    用户id
     * @param int $Type   1 获取全部用户 2获取最新用户
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取下级为独立团队用户此方法抛弃不用
     * 肖亚子
     */
    public static function UserOperateCount($Condition,$Uid = 0,$Type = 1){
        $Time = strtotime(date("Y-m-d",time()));

        if ($Uid){
            $Condition["reid"] = $Uid;
        }

        $List  = Db::name("user")->field("user_id,reg_time")->where($Condition)->select();
        $Count = 0;

        foreach ($List as $key=>$value){
            if ($Type == 1){
                $Count += 1;
            }else{
                if ($value["reg_time"] > $Time){
                    $Count += 1;
                }
            }

            $Condition["reid"] = $value["user_id"];

            $Branch = self::UserOperateCount($Condition,0,$Type); //调用函数，传入参数，继续查询下级

            if($Branch){
                $Count += $Branch;
            }
        }

        return $Count;
    }

    /**
     * @param $Condition  查询条件
     * @param $Uid        用户id
     * @param $Level      用户等级
     * @param $Floor      用户层数
     * @param int $Type   1 获取全部用户 2获取最新用户 3获取用户id
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户全部好友
     * 肖亚子
     */
    public static function UserAllMyFriends($Condition,$Uid,$Level,$Floor,$Type = 1){
        $Time = strtotime(date("Y-m-d",time()));

        if (in_array($Level,array(2,3))){
            $UnderCondition[] = array("exp","find_in_set($Uid,path) and (level > 3  or floor > {$Floor})");
        }elseif($Level == 4){//运营达人看自己的下级,独立出去的用户团队不查
            $UnderCondition[] = array("exp","find_in_set($Uid,path) and level > 3");
        }else{//玩主查看全部的推荐用户,玩主团队不查
            $UnderCondition[] = array("exp","find_in_set($Uid,path) and level > 4");
        }

        if ($Type != 3){
            $Count = self::TableName()->where($Condition)->count();
        }else{
            $Count = self::TableName()->field("user_id")->where($Condition)->select();

            $Count = array_reduce($Count, function ($result, $value) {
                return array_merge($result, array_values($value));
            }, array());
        }

        $User  = self::TableName()->field("user_id,reid,path,level,floor")->where($UnderCondition)->order("floor asc ,level desc")->select();

        foreach ($User as $Key => $Val){
            foreach ($User as $K => $V){
                if (in_array($Val["user_id"],explode(",",$V["path"]))){
                    unset($User[$K]);
                }
            }
        }

        foreach ($User as $Key => $Val){
            $C   = array();
            if($Type == 1 || $Type == 3){
                $C[] = array("exp","find_in_set({$Val["user_id"]},path)");
            }else{
                $C[] = array("exp","find_in_set({$Val["user_id"]},path) and reg_time > $Time");
            }

            if ($Type == 1){
                $Count -= (self::TableName()->where($C)->count()+1);
            }elseif ($Type == 2){
                $Count -= self::TableName()->where($C)->count();
            }else{
                $User_id = self::TableName()->field("user_id")->where($C)->select();
                $User_id[]["user_id"] = $Val["user_id"];
                $User_id = array_reduce($User_id, function ($result, $value) {
                    return array_merge($result, array_values($value));
                }, array());
                $Count = array_diff($Count,$User_id);
            }
        }

        if ($Type != 3){
            if ($Count >= 0){
                $Count = $Count;
            }else{
                $Count = 0;
            }
        }

        return $Count;
    }

    /**
     * 获取用户下面全部好友
     * @param $Condition
     * @param int $Uid
     * @param string $filed
     * @param array $reids
     * @param int $Paged
     * @param int $Psize
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public static function UserOperateListByUserIds($userids,$filed='user_id',$Paged=1,$Psize=10){
        if (empty($userids)){
            return [];
        }

        $map['user_id'] =array('in',implode(',',$userids)) ;
        $friends  = Db::name("user")->field($filed)->where($map)->page($Paged,$Psize)->select();
        return $friends;
    }

    /**
     * 获取用户下面全部好友
     * @param $Condition
     * @param int $Uid
     * @param string $filed
     * @param array $reids
     * @param int $Paged
     * @param int $Psize
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public static function UserOperateList($Condition,$Uid = 0,$filed='user_id',&$reids=[],$Paged=1,$Psize=10){
        if ($Uid){
            $Condition["reid"] = $Uid;
            $reids[$Uid] = $Uid;
        }
        $List  = Db::name("user")->field($filed)->where($Condition)->select();
        foreach ($List as $key=>$value){
            $reids[$value["user_id"]] = $value["user_id"];
            $Condition["reid"] = $value["user_id"];
            self::UserOperateList($Condition, $value["user_id"],$filed,$reids); //调用函数，传入参数，继续查询下级
        }

        $map = $Condition;
        $map['reid'] = array('in',implode(',',$reids));
        $friends  = Db::name("user")->field($filed)->where($map)->page($Paged,$Psize)->select();
        return $friends;
    }



    /**
     * @param array $Condition  查询条件
     * @return int|string
     * 获取用户好友信息
     */
    public static function getUserFriendList($Condition = array(),$Filed = "", $Paged=1,$Psize=10){

        if (!$Filed){
            $Filed = "user_id,nickname,avatar,level,status";
        }

        $Data = self::TableName()
                    ->field($Filed)
                    ->where($Condition)
                    ->order("reg_time desc")
                    ->page($Paged,$Psize)
                    ->select();

        return $Data;
    }



    /**
     * @param $Condition   查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户升级数据
     * 肖亚子
     */
    public function UserUpgrade($Condition){
        $Data = self::TableName()
                    ->alias("u")
                    ->field("up.upgrade_id")
                    ->join("user_upgrade up","up.user_id = u.user_id","left")
                    ->where($Condition)
                    ->order("up.upgrade_id desc")
                    ->count();

        return $Data;
    }

    /**
     * @param array $Condition   查询条件
     * @param string $Field      查询字段
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户账户金额数据
     * 肖亚子
     */
    public static function UserAccount($Condition = array(),$Field = "*"){
        $Data = Db::name("account")->field($Field)->where($Condition)->find();

        return $Data;
    }

    /**
     * @param $Condition      查询条件
     * @param $Date           查询表后缀
     * @param string $Field   查询字段
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取昨日结算数据
     * 肖亚子
     */
    public function UserAccountCash($Condition,$Date,$Field = "*"){
        $Data = Db::name("account_cash".$Date)->field($Field)->where($Condition)->find();

        return $Data;
    }

    /**
     * @param $Condition       查询条件
     * @param string $Field    查询字段
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取上月已结算数据
     * 肖亚子
     */
    public function UserFinance($Condition,$Field = "*"){
        $Data = Db::name("account_finance")->field($Field)->where($Condition)->find();

        return $Data;
    }
    /**
     * @param array $Condition   查询条件
     * @param string $Field      查询字段
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户佣金信息
     * 肖亚子
     */
    public static function UserAccountFinance($Condition = array(),$Field = "*"){
        $Data = Db::name("account_finance")->field($Field)->where($Condition)->find();

        return $Data;
    }
    /**
     * @return array
     * 获取用户佣金配置
     * 肖亚子
     */
    public static function UserParameterList(){
        $Parameter =  Db::name('parameter')->column('value', 'key');
        return $Parameter;
    }

    /**
     * 获取用户等级对应的等级名
     * @param $level
     * @return bool|string
     */
    public static function getLeveName($level){
        if($level){
            switch ($level){
                case 1:return '普通用户';break;
                case 2:return '超级会员';break;
                case 3:return '分享达人';break;
                case 4:return '运营达人';break;
                case 5:return '玩主';break;
            }
        }else{
            return false;
        }

    }

    /**
     * 锁粉模板推送通知上级
     */
    public static function  suoFenMsg($nickname,$sid){
        //微信锁粉通知
        $data['title'] = '恭喜您通过分享成功锁定一位会员！';
        $data['nickname'] = $nickname;
        $data['remark'] = '记得提醒他关注平台。';
        $accessToken =  Db::name('access_token')->value('access_token');
        $ucuser = UserModel::UserConnectFind(['user_id'=>$sid,'platform'=>'wechat','subscribe'=>1]);
        OpenTmModel::sendTplmsg10($ucuser['openid'],$data,$accessToken);
    }
}