<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/19
 * Time: 15:25
 * 用户银行卡模型
 * 肖亚子
 */

namespace app\system\model;
use think\Db;

class UserbankModel extends BaseModel{

    public static function TableName(){
        return Db::name("user_bank");
    }

    /**
     * @param array $Condition   查询条件
     * @param int $Psize         分页数默认第一页
     * @param int $PageSize      分页条数默认50条
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取全部用户的银行卡信息
     * 肖亚子
     */
    public  function BankList($Condition = array(), $Psize = 1, $PageSize = 50){

        $Field = "b.ub_id,b.branch_name,b.account_name,b.account_number,b.account_tel,b.union_code,b.province,b.city,b.area,u.mobile,u.nickname,k.bank_name";

        //查询总记录
        $Count     = self::TableName()
                        ->alias("b")
                        ->field($Field)
                        ->Join("user u","u.user_id = b.user_id","left")
                        ->Join("bank k","k.bank_id = b.bank_id","left")
                        ->where($Condition)
                        ->order('b.user_id desc')
                        ->count();

        $PageCount = ceil($Count/$PageSize);

        $List      = self::TableName()
                        ->alias("b")
                        ->field($Field)
                        ->Join("user u","u.user_id = b.user_id","left")
                        ->Join("bank k","k.bank_id = b.bank_id","left")
                        ->where($Condition)
                        ->page($Psize, $PageSize)
                        ->order('b.user_id desc')
                        ->select();

        $PaginaTion = parent::Paging($Count,$Psize,$PageCount,$List);

        return $PaginaTion;
    }

    /**
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取平台银行数据
     * 肖亚子
     */
    public function PlatformBank(){
        $List = Db::name("bank")->field("bank_id,bank_name")->select();

        return $List;
    }

    /**
     * @param array $Condition  查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取用户银行卡信息
     * 肖亚子
     */
    public static function UserBankFind($Condition = array()){
        $Data = self::TableName()->where($Condition)->find();

        return $Data;
    }

    /**
     * @param $Id  城市id
     * @return mixed
     * 获取银行卡绑定的城市名
     * 肖亚子
     */
    public function UserBankArea($Id){
        $Data = Db::name("region")->where("id","=",$Id)->value("name");

        return $Data;
    }

    /**
     * @param $Id     银行卡id
     * @param $Data   修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改用户银行卡城市
     * 肖亚子
     */
    public function UserBankUpdate($Id,$Data){
        $Date = self::TableName()->where("ub_id","=",$Id)->update($Data);

        return $Date;
    }
}