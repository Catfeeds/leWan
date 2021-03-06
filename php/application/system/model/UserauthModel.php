<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/18
 * Time: 13:41
 */

namespace app\system\model;
use think\Db;


class UserauthModel extends BaseModel{

    public static function TableName(){
        return Db::name("user_auth");
    }

    /**
     * @param array $Condition      查询条件
     * @param int $Psize            当前分页数默认第一页
     * @param int $PageSize         每页条数默认50条
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取实名认证数据
     * 肖亚子
     */
    public static function AuthList($Condition = array(), $Psize = 1, $PageSize = 50){
        $Field = "a.auth_id,a.truename,a.status,a.addtime,a.uptime,u.avatar,u.mobile,u.nickname";

        //查询总记录
        $Count     = self::TableName()
                        ->alias("a")
                        ->field($Field)
                        ->Join("user u","u.user_id = a.user_id","left")
                        ->where($Condition)
                        ->order('a.addtime desc')
                        ->count();

        $PageCount = ceil($Count/$PageSize);

        $List      = self::TableName()
                        ->alias("a")
                        ->field($Field)
                        ->Join("user u","u.user_id = a.user_id","left")
                        ->where($Condition)
                        ->page($Psize, $PageSize)
                        ->order('a.addtime desc')
                        ->select();


        $PaginaTion = parent::Paging($Count,$Psize,$PageCount,$List);

        return $PaginaTion;
    }

    /**
     * @param $Condition    查询条件
     * @param $Field        查询字段
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取认证详情休息
     * 肖亚子
     */
    public static function AuthFind($Condition = array(),$Field = ""){

        $Field = $Field == ""?"a.*":"a.*,u.avatar,u.mobile,u.nickname";

        $Data = self::TableName()
                ->alias("a")
                ->field($Field)
                ->Join("user u","u.user_id = a.user_id","left")
                ->where($Condition)
                ->find();

        return $Data;
    }

    /**
     * @param array $Condition  修改条件
     * @param $Data             修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改认证状态
     * 肖亚子
     */
    public static function AuthUpdate($Condition = array(),$Data){
        $AuthUp = self::TableName()->where($Condition)->update($Data);

        return $AuthUp;
    }
    /**
     * @param array $Condition  查询条件
     * @return int|string
     * 获取实名认证人数
     * 肖亚子
     */
    public static function AuthCount($Condition = array()){
        $Data = self::TableName()->where($Condition)->count();
        return $Data;
    }


}