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
    public function UserDataFind($Condition = array(),$Filed = ""){
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
   public function UserFindUid($Token){
        $Data = self::TableName()->where('token','=',$Token)->value("user_id");

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
   public function UserConnectFind($Condition = array()){
        $Data = Db::name("user_connect")->where($Condition)->find();

        return $Data;
   }

    /**
     * @param $Data       添加内容
     * @return int|string
     * 添加用户
     * 肖亚子
     */
   public function UserAdd($Data){
        $User = self::TableName()->insertGetId($Data);
        return $User;
   }

    /**
     * @param $Data       添加内容
     * @return int|string
     * 添加用户第三方关联信息
     * 肖亚子
     */
   public function UserConnectAdd($Data){
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
   public function UserUpdate($Condition = array(),$Data){
       $Data = self::TableName()->where($Condition)->update($Data);

       return $Data;
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
    public function UserConnectUpdate($Condition = array(),$Data){
           $Data = Db::name("user_connect")->where($Condition)->update($Data);
           return $Data;
    }

    /**
     * @param array $Condition  查询条件
     * @return int|string
     * 获取用户人数
     */
    public function UserCount($Condition = array()){
        $Count = self::TableName()->where($Condition)->count();

        return $Count;
    }
    /**
     * @param array $Condition  查询条件
     * @return int|string
     * 获取用户好友信息
     */
    public function getUserFriendList($Condition = array(),$Filed = "", $Paged=1,$Psize=10){
        if (!$Filed){
            $Filed = "user_id,nickname,avatar,level,status";
        }
        $Data = self::TableName()->field($Filed)
            ->where($Condition)->page($Paged,$Psize)->select();


        //获取好友下单数
            foreach ($Data as &$val){
                if($val && $val['user_id']){
                    $val['order_count'] = OrderModel::GetOrderCountByUserId($val['user_id']);
                }
            }
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
    public function UserAccount($Condition = array(),$Field = "*"){
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
    public function UserAccountFinance($Condition = array(),$Field = "*"){
        $Data = Db::name("account_finance")->field($Field)->where($Condition)->find();

        return $Data;
    }
    /**
     * @return array
     * 获取用户佣金配置
     * 肖亚子
     */
   public function UserParameterList(){
      $Parameter =  Db::name('parameter')->column('value', 'key');
      return $Parameter;
   }
}