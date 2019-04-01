<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/6
 * Time: 17:53
 * 用户收入模型
 * 肖亚子
 */
namespace app\api\model;
use think\Db;

class AccountcashModel{

    public static function TableName($Month){
        return Db::name("account_cash".$Month);
    }

    /**
     * @param array $Condition  查询条件
     * @param int $Month        日期
     * @param int $Page         分页默认第一页
     * @param int $Psize        分页数默认20条
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 查询明细信息
     * 肖亚子
     */
    public function AccountcashList($Condition = array(),$Month,$Page = 1,$Psize = 20){

        $Data = self::TableName($Month)
                    ->field("order_id,record_action as action,record_amount as money,record_addtime as addtime")
                    ->where($Condition)
                    ->page($Page,$Psize)
                    ->order("record_id desc")
                    ->select();

        return $Data;
    }

    /**
     * @param array $Condition  查询条件
     * @param $Month            日期
     * @param int $Page         分页默认第一页
     * @param int $Psize        分页数默认20条
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 查询佣金信息
     * 肖亚子
     */
    public function AaccountCommissionList($Condition = array(),$Month,$Page = 1,$Psize = 20){
        $Dada = Db::name("account_commission".$Month)
                    ->field("order_id,record_action as action,record_amount as money,record_addtime as addtime,record_attach")
                    ->where($Condition)
                    ->page($Page,$Psize)
                    ->order("record_id desc")
                    ->select();

        return $Dada;
    }


}