<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2019/2/26
 * Time: 14:12
 * 小区盟主模型
 * 肖亚子
 */

namespace app\system\model;
use Think\Db;

class UserownerapplyModel extends BaseModel{

    public static function TableName(){
        return Db::name("user_owner_apply");
    }

    /**
     * @param array $Condition  查询条件
     * @param int $Psize        分页数
     * @param int $PageSize     分页条数
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取盟主数据
     * 肖亚子
     */
    public static function UserOwnerApplyList($Condition = array(),$Psize = 1,$PageSize = 20){
        $Field = "a.apply_id,a.realname,a.phone,a.status,a.addtime,a.uptime,u.nickname,u.username,u.avatar,u.userthumb,u.ownerstatus,c.community_name,r.fullname";

        $Count = self::TableName()
                    ->alias("a")
                    ->field($Field)
                    ->join("user u","u.user_id = a.user_id","left")
                    ->join("region_community c","c.community_id = a.community_id","left")
                    ->join("region r","r.id = c.district_id","left")
                    ->where($Condition)
                    ->order("a.addtime desc")
                    ->count();

        $PageCount = ceil($Count/$PageSize);

        $List = self::TableName()
                    ->alias("a")
                    ->field($Field)
                    ->join("user u","u.user_id = a.user_id","left")
                    ->join("region_community c","c.community_id = a.community_id","left")
                    ->join("region r","r.id = c.district_id","left")
                    ->where($Condition)
                    ->order("a.addtime desc")
                    ->page($Psize, $PageSize)
                    ->select();

        $PaginaTion = parent::Paging($Count,$Psize,$PageCount,$List);

        return $PaginaTion;
    }

    /**
     * @param array $Condition  查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取盟主信息
     * 肖亚子
     */
    public static function UserOwnerApplyFind($Condition = array()){
        $Field = "a.apply_id,a.community_id,a.realname,a.phone,a.status,a.introduce,a.remark,a.uptime,a.addtime,u.user_id,u.nickname,
        u.username,u.avatar,u.ownerstatus,c.community_name,r.fullname";
        $Data = self::TableName()
                ->alias("a")
                ->field($Field)
                ->join("user u","u.user_id = a.user_id","left")
                ->join("region_community c","c.community_id = a.community_id","left")
                ->join("region r","r.id = c.district_id","left")
                ->where($Condition)
                ->find();

        return $Data;
    }

    /**
     * @param array $Condition 查询条件
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取盟主数量
     * 肖亚子
     */
    public static function UserOwnerApplyTotal($Condition = array()){
        $Count = self::TableName()->where($Condition)->count();

        return $Count;
    }

    /**
     * @param $Condition  修改条件
     * @param $Data       修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改小区盟主申请状态
     * 肖亚子
     */
    public static function UserOwnerApplyUpdate($Condition,$Data){
        $Data = self::TableName()->where($Condition)->update($Data);

        return $Data;
    }

    /**
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取盟主状态数量
     * 肖亚子
     */
    public static function UserOwnerApplyCount(){
        $Count = self::TableName()->field("count(*) as count,status")->group("status")->select();

        $Status = array("apply"=>0,"reject"=>0,"adopt"=>0);

        foreach ($Count as $Key => $Val){
            switch ($Val["status"]){
                case 0:$Status["apply"] = $Val["count"];break;
                case 1:$Status["reject"]= $Val["count"];break;
                case 2:$Status["adopt"] = $Val["count"];break;
            }
        }

        return $Status;
    }
}