<?php
namespace app\system\model;

use think\Db;
use think\Config;
use think\Request;
use think\Session;
use think\Cache;

/**
 * 商家相关
 * Enter description here ...
 * @author Administrator
 *
 */
class MerchantModel extends BaseModel{

    public static function TableName(){
        return Db::name("merchant");
    }

    /**
     * 查询列表
     * @param array $Condition
     * @param int $Page
     * @param int $PageSize
     */
    public static function getList($Condition=array(), $Page=1, $PageSize=20){
        //查询总记录
        $Count = Db::name('merchant m')
                    ->join("merchant mc","mc.parent_id = m.merchant_id","left")
                    ->where($Condition)
                    ->count();

        $PageCount = ceil($Count/$PageSize);
        $List = Db::name('merchant m')
                    ->field('m.merchant_id, m.parent_id,m.merchant_name, m.merchant_contact, m.merchant_contactmobile, m.merchant_ssq, m.merchant_address, m.merchant_status, m.merchant_remark, m.merchant_addtime, m.merchant_uptime, m.merchant_open,mc.merchant_name as main_name')
                    ->join("merchant mc","mc.merchant_id = m.parent_id","left")
                    ->where($Condition)
                    ->page($Page, $PageSize)
                    ->order('m.merchant_id desc')
                    ->select();
        if(key_exists('m.parent_id',$Condition)){
            foreach ($List as $k=>$v){
                $List[$k]['fdlist'] = Db::name('merchant')->where(['parent_id'=>$v['merchant_id']])->select();
            }
        }
        $PaginaTion = parent::Paging($Count,$Page,$PageCount,$List);
        return $PaginaTion;
    }


    public static function getMerchantAccountList($condition=array(), $page=1, $pageSize=20){
        $Count = Db::name('merchant_account ma')
            ->join('merchant m','m.merchant_id=ma.merchant_id','left')
            ->where($condition)
            ->page($page, $pageSize)->order('m.merchant_uptime desc')->count();
        $PageCount = ceil($Count/$pageSize);

        $list = Db::name('merchant_account ma')
            ->field('m.merchant_id, m.merchant_name, m.merchant_contact, m.merchant_contactmobile, 
             m.merchant_ssq, m.merchant_address, m.merchant_status,
             m.merchant_addtime,  m.merchant_open, ma.account_cash_expenditure,
              ma.account_cash_income, ma.account_cash_balance')
            ->join('merchant m','m.merchant_id=ma.merchant_id','left')
            ->where($condition)
            ->page($page, $pageSize)->order('m.merchant_uptime desc')->select();
        $PaginaTion = parent::Paging($Count,$page,$PageCount,$list);
        return $PaginaTion;

    }

    /**
     * @param array $Condition  查询条件
     * @param string $Field     查询字段
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商家信息
     * 肖亚子
     */

    public static function MerchantFind($Condition = array(),$Field = "*"){
        $Data = self::TableName()->field($Field)->where($Condition)->find();

        return $Data;
    }

    /**
     * @param array $Condition 查询条件
     * @return array
     * 获取商家
     * 肖亚子
     */
    public static function MerchantColumn($Condition = array()){
        $List = self::TableName()
            ->where($Condition)
            ->order("parent_id asc")
            ->column('merchant_id');

        return $List;
    }

    /**
     * @param array $Condition 查询条件
     * @param $Val   查询字段
     * @return mixed
     * 获取指定商家字段
     * 肖亚子
     */
    public static function MerchantVal($Condition = array(),$Val){
        $Data = self::TableName()
                ->where($Condition)
                ->value($Val);

        return $Data;
    }

    
}
