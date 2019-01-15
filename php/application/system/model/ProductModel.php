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
class ProductModel extends BaseModel{

    public static function TableName(){
        return Db::name("product");
    }

    /**
     * 查询列表
     * @param array $Condition
     * @param int $Page
     * @param int $PageSize
     */
    public static function getList($Condition=array(), $Page=1, $PageSize=20){
        //查询总记录
        $Count = Db::name('product p')
                    ->join('jay_merchant m', 'm.merchant_id = p.merchant_id', 'left')
                    ->where($Condition)
                    ->count();

        $PageCount = ceil($Count/$PageSize);

        $field = 'p.product_id, p.merchant_id, p.fen_merchant_ids, p.purchase_id, p.product_name, p.product_pic, p.product_cateids, p.catefinance_id, p.product_tags, p.product_toplevel,'.
        'p.product_returnall,p.product_explosion, p.product_reservation,p.product_isexpress,p.product_timelimit,p.product_numlimit,p.product_numlimit_num,p.product_starttime,p.product_endtime,'.
        'p.product_startusetime,p.product_endusetime,p.product_reviewstatus,p.product_status,p.product_del,p.price_type,p.reservationStr,p.temp_price,p.temp_commission,'.
        'p.product_sales_volume,p.product_sold,p.product_uptime,p.product_addtime,p.sold_out,p.sold_out_time,p.distributiontag';
        $List = Db::name('product p')
                    ->field($field.', m.merchant_name')
                    ->join('jay_merchant m', 'm.merchant_id = p.merchant_id', 'left')
                    ->where($Condition)
                    ->page($Page, $PageSize)
                    ->order('p.sold_out asc,p.product_status desc,p.product_toplevel desc,p.product_uptime desc')
                    ->select();
        $PaginaTion = parent::Paging($Count,$Page,$PageCount,$List);
        return $PaginaTion;
    }


    /**
     * 商品操作日志
     * @param $product_id
     * @param int $operator_from 1店小二；2商家
     * @param $operator_name
     * @param $operator_id
     * @param $action
     */
    public function log($product_id, $operator_from=1,$operator_name, $operator_id, $action){
        $vo['product_id'] = $product_id;
        $vo['operator_from'] = $operator_from;
        $vo['operator_name'] = $operator_name;
        $vo['operator_id'] = $operator_id;
        $vo['action'] = $action;
        $vo['addtime'] = time();
        Db::name('product_log')->insert($vo);
    }

    /**
     * 暂时没有使用
     * @param array $Condition
     * @param int $Page
     * @param int $PageSize
     * @return mixed
     */
    public function performance($Condition=array(), $Page=1, $PageSize=20){
        //查询总记录
        $Count = Db::name('product_performance pp')->where($Condition)->count();

        $PageCount = ceil($Count/$PageSize);

        $List = Db::name('product_performance pp')
            ->field('pp.*, p.product_name, u.nickname ')
            ->join('jay_product p', 'pp.product_id = p.product_id', 'left')
            ->join('jay_user u', 'u.user_id = p.purchase_id', 'left')
            ->where($Condition)->page($Page, $PageSize)
            ->order('pp.id asc')->select();
        $PaginaTion = parent::Paging($Count,$Page,$PageCount,$List);
        return $PaginaTion;
    }

    /**
     * @param $Data   添加内容
     * @return int|string
     * 添加商品消费码
     * 肖亚子
     */
    public static function ProductCode($Data){
        $Data = Db::name("product_distribution_code")->insert($Data);

        return $Data;
    }

    /**
     * 商品推送到 首页城市到店商品推荐表
     * @param $productid
     * @param $cityCode
     * @return bool
     */
    public static function pushProductToCity($productid,$cityCode){
        //已经存在数据
        if(Db::name('home_product_city')->where(['product_id'=>$productid,'city_code'=>$cityCode])->count()){
            return true;
        }else{
            if($productid && $cityCode){
                $pcity['product_id'] = $productid;
                $pcity['city_code'] = $cityCode;
                if(Db::name('home_product_city')->insertGetId($pcity)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 置顶商品加入首页省份到店商品置顶推荐表
     * @param $productid
     * @param $pcode
     * @param $city
     * @return bool
     */
    public static function pushProductToProvince($productid,$pcode,$city){
        Db::name('home_product_top_provence')
          ->where(['product_id'=>$productid,'provence_code'=>$pcode])
          ->delete();
        $topprovence['provence_code'] = $pcode;
        $topprovence['push_city_codes'] = $city;
        $topprovence['product_id'] = $productid;
        if(Db::name('home_product_top_provence')->insertGetId($topprovence)){
        return true;
        }
        return false;
    }
}

