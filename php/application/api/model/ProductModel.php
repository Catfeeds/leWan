<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/30
 * Time: 17:30
 * 接口商品模型
 * 肖亚子
 */

namespace app\api\model;
use think\Db;

class ProductModel{

    public static function TableName(){
        return Db::name("product");
    }

    /**
     * @param array $Condition    查询条件
     * @param null $Fd            商家距离计算字段
     * @param $Order              排序
     * @param int $Psize          分页默认第一页
     * @param int $PageSize       分页条数默认一页20条
     * @param int $Compel         强制使用新的字段
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 商城商品列表查询
     * 肖亚子
     */
    public static function ShopProductList($Condition= array(),$Fd = null,$Order,$Psize=1, $PageSize=20,$Compel=false){
        if($Compel && $Fd){ //筛选字段非空且强制使用
            $Field = $Fd;
        }else{
            $Field = "p.product_id,p.product_pic,p.product_compic,p.product_name,p.temp_price,p.temp_commission,p.product_sales_volume,p.product_sold,product_returnall,p.product_timelimit,p.product_numlimit,p.product_starttime,p.product_endtime,p.sold_out,p.share_desc,p.product_uptime,p.product_addtime,m.merchant_name,r.name as region".$Fd;
        }
        $List  = self::TableName()
                    ->alias("p")
                    ->field($Field)
                    ->join("merchant m","m.merchant_id = p.merchant_id","left")
                    ->join("region r","r.id = m.merchant_ccode","left")
                    ->where($Condition)
                    ->page($Psize,$PageSize)
                    ->order($Order)
                    ->select();

        return $List;
    }

    /**
     * @param array $Condition  查询条件
     * @param int $Psize        分页数默认第一页
     * @param int $PageSize     分页条数，默认一页20条
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取预约中心可预约商品数据
     * 肖亚子
     */
    public static function ShopBookedProductList($Condition= array(),$Psize=1, $PageSize=20){

        $Field = "p.product_id,p.product_name";

        $List  = self::TableName()
                    ->alias("p")
                    ->field($Field)
                    ->join("merchant m","m.merchant_id = p.merchant_id","left")
                    ->join("region r","r.id = m.merchant_ccode","left")
                    ->where($Condition)
                    ->page($Psize,$PageSize)
                    ->order("p.sold_out asc,p.product_toplevel desc,p.product_uptime desc")
                    ->select();

        return $List;
    }

    /**
     * @param array $Condition 查询条件
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 根据商品商家获取所有分店
     * 肖亚子
     */
    public static function ShopList($Condition = array(),$Field = ""){
        if (!$Field){
            $Field = "m.merchant_id,m.merchant_alias as merchant_name";
        }
        $List = Db::name("merchant")
                    ->alias("m")
                    ->field($Field)
                    ->join("region r","r.id = m.merchant_ccode","left")
                    ->where($Condition)
                    ->select();

        return $List;
    }

    /**
     * @param array $Condition  查询条件
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品日历
     * 肖亚子
     */
    public static function ShopProductCalendarList($Condition = array()){
        $List = Db::name('product_reservationday')
                            ->alias("r")
                            ->field('r.reservationday_id,r.calendar,r.week,r.preday, sum(`p`.`totalnum`) totalnum, sum(`p`.`usenum`) usenum')
                            ->join('jay_product_reservationprice p', 'p.reservationday_id = r.reservationday_id', 'left')
                            ->where($Condition)
                            ->group('p.reservationday_id')
                            ->order('r.calendar asc')
                            ->limit(0,30)
                            ->select();

        return $List;
    }
    /**
     * @param array $Condition  查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品规格库存
     * 肖亚子
     */
    public static function ShopProdictPriceFind($Condition = array()){
        $Data = Db::name("product_price")->field("product_totalnum")->where($Condition)->find();

        return $Data;
    }

    /**
     * @param array $Condition     查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品详细
     * 肖亚子
     */
    public static function ShopProductData($Condition = array()){
        $Field = "p.product_id,p.merchant_id,p.product_name,p.product_pic,p.product_carousel,p.product_poster,p.product_cateids,p.product_tags,p.product_returnall,p.product_explosion,p.product_reservation,p.product_isexpress,p.product_sold,p.product_timelimit,p.product_numlimit,p.product_numlimit_num,p.product_starttime,p.product_endtime,p.product_startusetime,p.product_endusetime,p.price_type,p.reservationStr,p.product_info,p.product_useinfo,p.product_notice,p.product_description,p.sold_out,p.share_desc";
        $Data  = self::TableName()
                    ->alias("p")
                    ->field($Field)
                    ->join("merchant m","m.merchant_id = p.merchant_id","left")
                    ->join("region r","r.id = m.merchant_ccode","left")
                    ->where($Condition)
                    ->find();

        return $Data;
    }

    /**
     * @param array $Condition  查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取预约商品详情
     * 肖亚子
     */
    public static function ShopBookedProductData($Condition = array()){
        $Field = "p.product_id,p.product_name,p.product_info,p.product_useinfo,p.product_notice,p.product_reservation,p.product_isexpress,p.merchant_id,m.merchant_ssq,m.merchant_address,m.merchant_lat,m.merchant_lng";
        $Data  = self::TableName()
                    ->alias("p")
                    ->field($Field)
                    ->join("merchant m","m.merchant_id = p.merchant_id","left")
                    ->join("region r","r.id = m.merchant_ccode","left")
                    ->where($Condition)
                    ->find();

        return $Data;
    }

    /**
     * @param $Id   商品id
     * @return mixed
     * 获取商品海报图片
     * 肖亚子
     */
    public static function ShopProductPoster($Id,$Value = "product_poster"){
        $Data = self::TableName()->where("product_id","=",$Id)->value($Value);

        return $Data;
    }
    /**
     * @param array $Codnition   查询条件
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品规格价格数据
     * 肖亚子
     */
    public static function ShopProductPrice($Codnition = array(),$Field = ""){
        if (!$Field){
            $Field = "price_id,product_property,price_market,price_sale,price_commission,product_totalnum,product_buynum,product_dynamicnum";
        }

        $List = Db::name("product_price")
                    ->field($Field)
                    ->where($Codnition)
                    ->select();

        return $List;
    }

    /**
     * @param array $Condition    查询条件
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品标签
     * 肖亚子
     */
    public static function ShopTags($Condition = array()){
        $List = Db::name("product_tags")->field("tag_name")->where($Condition)->select();

        return $List;
    }
}