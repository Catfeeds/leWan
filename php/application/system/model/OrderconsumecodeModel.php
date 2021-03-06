<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/26
 * Time: 11:27
 * 预约订单消费码
 * 肖亚子
 */

namespace app\system\model;
use think\Db;

class OrderconsumecodeModel extends BaseModel{

    public static function TableName(){
        return Db::name("order_consume_code");
    }

    /**
     * @param array $Condition   查询条件
     * @param int $Psize         查询页数默认第一页
     * @param int $PageSize      每页条数默认50条
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取消费码数据
     * 肖亚子
     */
    public function  CodeList($Condition=array(), $Psize=1, $PageSize=50){
        $Field     = "c.consume_code_id,c.consume_code,c.status,c.uptime,c.addtime,o.order_no,o.order_fullname,o.order_mobile,u.mobile,u.nickname,m.merchant_name,mc.merchant_name as merchant_cname";
        $Count     = self::TableName()
                        ->alias("c")
                        ->field($Field)
                        ->Join("order o","o.order_id = c.order_id","left")
                        ->Join("user u","u.user_id = c.user_id","left")
                        ->Join("merchant m","m.merchant_id = o.merchant_id","left")
                        ->Join("merchant mc","mc.merchant_id = c.fen_merchant_id","left")
                        ->where($Condition)
                        ->order('c.consume_code_id desc')
                        ->count();

        $PageCount = ceil($Count/$PageSize);

        $List      = self::TableName()
                        ->alias("c")
                        ->field($Field)
                        ->Join("order o","o.order_id = c.order_id","left")
                        ->Join("user u","u.user_id = c.user_id","left")
                        ->Join("merchant m","m.merchant_id = o.merchant_id","left")
                        ->Join("merchant mc","mc.merchant_id = c.fen_merchant_id","left")
                        ->where($Condition)
                        ->order("c.consume_code_id desc")
                        ->page($Psize,$PageSize)
                        ->select();

        $PaginaTion = parent::Paging($Count,$Psize,$PageCount,$List);

        return $PaginaTion;
    }

    /**
     * @param array $Condition       搜索查询条件
     * @param array $CountCondition  单独消费码状态条件
     * @param $Type                  查询消费码状态类型
     * @param $Status                列表展示类型状态
     * @return int|string
     * 查询消费码状态数量
     * 肖亚子
     */
    public function CodeCount($Condition = array(),$CountCondition = array(),$Type,$Status){
        if ($Type == $Status){
            $Count = self::TableName()
                        ->alias("c")
                ->Join("order o","o.order_id = c.order_id","left")
                ->Join("user u","u.user_id = c.user_id","left")
                ->Join("merchant m","m.merchant_id = o.merchant_id","left")
                ->where($Condition)
                ->order('c.consume_code_id desc')
                ->count();
        }else{
            $Count = self::TableName()
                        ->alias("c")
                        ->Join("order o","o.order_id = c.order_id","left")
                        ->Join("user u","u.user_id = c.user_id","left")
                        ->Join("merchant m","m.merchant_id = o.merchant_id","left")
                        ->where($CountCondition)
                        ->order('c.consume_code_id desc')
                        ->count();
        }

        return $Count;
    }
}