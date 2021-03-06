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
        'p.product_startusetime,p.product_endusetime,p.product_reviewstatus,p.is_shengxian,p.product_status,p.product_del,p.price_type,p.reservationStr,p.temp_price,p.temp_commission,'.
        'p.product_sales_volume,p.product_sold,p.product_uptime,p.product_addtime,p.sold_out,p.sold_out_time,p.distributiontag,p.obtained_time,p.product_must_fill';
        $List = Db::name('product p')
                    ->field($field.', m.merchant_name,m.merchant_ssq,md.dboss_name')
                    ->join('jay_merchant m', 'm.merchant_id = p.merchant_id', 'left')
                    ->join('merchant_dboss md', 'm.dboss_id = md.id', 'left')
                    ->where($Condition)
                    ->page($Page, $PageSize)
                    ->order('p.product_id desc')
//                    ->order('p.sold_out asc,p.product_status desc,p.product_toplevel desc,p.product_uptime desc')
                    ->select();
        $PaginaTion = parent::Paging($Count,$Page,$PageCount,$List);
        return $PaginaTion;
    }

    /**
     * @param array $Condition  查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品信息
     * 肖亚子
     */
    public static function ProductFind($Condition = array()){
        $Data = self::TableName()->where($Condition)->find();

        return $Data;
    }

    /**
     * @param array $Condition  查询条件
     * @param string $Field     默认查询字段
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品以及关联商家信息
     * 肖亚子
     */
    public static function ProductFinds($Condition = array(),$Field = "p.product_toplevel,p.product_isexpress,m.merchant_pcode"){

        $Data = self::TableName()
                ->alias("p")
                ->join('merchant m', 'm.merchant_id = p.merchant_id', 'left')
                ->field('p.product_toplevel,p.product_isexpress,m.merchant_pcode')
                ->where($Condition)
                ->find();

        return $Data;
    }

    /**
     * @param array $Condition 查询条件
     * @param $Val  查询字段
     * @return mixed
     * 获取商品指定查询字段
     * 肖亚子
     */
    public static function ProductVal($Condition = array(),$Val){
        $Data = self::TableName()
                ->where($Condition)
                ->value($Val);

        return $Data;
    }

    /**
     * @param array $Condition 修改条件
     * @param $Data            修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改商品信息
     * 肖亚子
     */
    public static function ProductUpdate($Condition = array(),$Data){
        $UpData = self::TableName()->where($Condition)->update($Data);

        return $UpData;
    }

    /**
     * @param array $Condition  查询条件
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品规格数据
     * 肖亚子
     */
    public static function ProductPriceList($Condition = array()){
        $List = Db::name('product_price')
                ->where($Condition)
                ->select();

        return $List;
    }

    /**
     * @param array $Condition  查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品规格信息
     * 肖亚子
     */
    public static function ProductPriceFind($Condition = array()){
        $Data = Db::name('product_price')->where($Condition)->find();

        return $Data;
    }

    /**
     * @param array $Condition  查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品规格第一条数据
     * 肖亚子
     */
    public static function ProductPriceFindAsc($Condition = array()){
        $Data = Db::name('product_price')
                ->where($Condition)
                ->order("price_sale asc")
                ->find();

        return $Data;
    }

    /**
     * @param array $Condition 修改条件
     * @param $Data  修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改商品规格信息
     * 肖亚子
     */
    public static function ProductPriceUpdate($Condition = array(),$Data){
        $Data = Db::name('product_price')->where($Condition)->update($Data);

        return $Data;
    }

    /**
     * @param $Data  添加内容
     * @return \think\db\Query
     * 添加商品规格
     * 肖亚子
     */
    public static function ProductPriceAdd($Data){
        $Data = Db::name('product_price')->insert($Data);

        return $Data;
    }
    /**
     * @return array
     * 获取奖励配置数据
     * 肖亚子
     */
    public static function ParameterList(){
        $List = Db::name('parameter')->column('value', 'key');

        return $List;
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
     * @param array $Condition  查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取预约型号信息
     * 肖亚子
     */
    public static function ReservationpriceFind($Condition = array()){
        $Data = Db::name("product_reservationprice")->where($Condition)->find();

        return $Data;
    }

    /**
     * @param array $Condition  修改条件
     * @param $Data             修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改商品预约型号信息
     * 肖亚子
     */
    public static function ReservationpriceUpdate($Condition = array(),$Data){
        $Data = Db::name("product_reservationprice")->where($Condition)->update($Data);

        return $Data;
    }

    /**
     * @param array $Condition 查询条件
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除预约型号信息
     * 肖亚子
     */
    public static function ReservationpriceDel($Condition = array()){
        $Data = Db::name("product_reservationprice")->where($Condition)->delete();

        return $Data;
    }

    /**
     * 商品推送到 首页城市到店商品推荐表
     * @param $productid
     * @param $cityCode
     * @return bool
     */
    public static function pushProductToCity($productid,$cityCode,$type='',$product_isexpress=''){
        //已经存在数据
        if(Db::name('home_product_city')->where(['product_id'=>$productid,'city_code'=>$cityCode])->count()){
            return true;
        }else{
            if($productid && $cityCode){
                $rank = 0;
                if($type=='add'){
                    $last = Db::name('home_product_city')->order('id desc')->field('id')->find();
                    if($last){
                        $rank = intval($last['id'])+1;
                    }
                }
                $pcity['product_sort'] = $rank;
                $pcity['product_id'] = $productid;
                $pcity['city_code'] = $cityCode;
                $pcity['product_type'] = $product_isexpress;
                if(Db::name('home_product_city')->insertGetId($pcity)){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param array $Condition  删除条件
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 取消置顶，移除首页推荐
     * 肖亚子
     */
    public static function TopProvenceDel($Condition = array()){
        $Data = Db::name('home_product_top_provence')
                ->where($Condition)
                ->delete();

        return $Data;
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

    /**
     * @param array $Condition  查询条件
     * @param string $Field     查询字段
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取产品部门员工信息
     * 肖亚子
     */
    public static function StaffFind($Condition = array(),$Field = "*"){
        $Data = Db::name('staff')
                ->field($Field)
                ->where($Condition)
                ->find();

        return $Data;
    }

    /**
     * @param array $Condition  查询条件
     * @param string $Field     查询字段
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品分类数据
     * 肖亚子
     */
    public static function AategoryList($Condition = array(),$Field = "*"){
        $List = Db::name('product_category')
                ->field($Field)
                ->where($Condition)
                ->select();

        return $List;
    }

    /**
     * @param string $Field  查询字段
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品标签数据
     * 肖亚子
     */
    public static function TagsList($Field = "*"){
        $List = Db::name('product_tags')
                ->field($Field)
                ->select();

        return $List;
    }

    /**
     * @param array $Condition  查询条件
     * @param string $Field     查询字段
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取MySQL定时器执行日志
     * 肖亚子
     */
    public static function TimerActionFind($Condition = array(),$Field = "*"){
        $Data = Db::name('timer_action')
                ->field($Field)
                ->where($Condition)
                ->find();

        return $Data;
    }

    /**
     * @param $Data  添加内容
     * @return int|string
     * 添加MySQL定时器执行日志
     * 肖亚子
     */
    public static function TimerActionAdd($Data){
        $Data = Db::name('timer_action')->insertGetId($Data);

        return $Data;
    }

    /**
     * @param array $Condition 查询条件
     * @param string $Field    查询字段
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取推送城市数据
     * 肖亚子
     */
    public static function HomeProductCityFind($Condition = array(),$Field = "*"){
       $Data = Db::name('home_product_city')
               ->field($Field)
               ->where($Condition)
               ->select();

       return $Data;
    }

    /**
     * @param array $Condition 删除条件
     * @return int
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 删除推送城市数据
     * 肖亚子
     */
    public static function HomeProductCityDel($Condition = array()){
        $Data = Db::name('home_product_city')
                ->where($Condition)
                ->delete();

        return $Data;
    }

    /**
     * @param array $Condition 查询条件
     * @return int|string
     * 获取快递商品每日预约库存数量
     * 肖亚子
     */
    public static function ProductKuaidikucunCount($Condition = array()){
        $Count = Db::name('product_kuaidikucun')->where($Condition)->count();

        return $Count;
    }

    /**
     * @param array $Condition 查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取快递商品每日预约库存数量信息
     * 肖亚子
     */
    public static function ProductKuaidikucunFind($Condition = array()){
        $Data = Db::name('product_kuaidikucun')->where($Condition)->find();

        return $Data;
    }

    /**
     * @param array $Condition  修改条件
     * @param $Data  修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改快递商品每日预约库存数量信息
     * 肖亚子
     */
    public static function ProductKuaidikucunUpdate($Condition = array(),$Data){
        $Data = Db::name('product_kuaidikucun')->where($Condition)->update($Data);

        return $Data;
    }

    /**
     * @param $Data  添加内容
     * @return int|string
     * 添加快递商品每日预约库存数量信息
     * 肖亚子
     */
    public static function ProductKuaidikucunAdd($Data){
        $Add = Db::name('product_kuaidikucun')->insertGetId($Data);

        return $Add;
    }

    /**
     * @param $Data  添加内容
     * @return int|string
     * 添加预约商品日历
     * 肖亚子
     */
    public static function ProductReservationdayAdd($Data){
        $Add = Db::name('product_reservationday')->insertGetId($Data);

        return $Add;
    }

    /**
     * @param array $Condition 查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取预约商品日历信息
     * 肖亚子
     */
    public static function ProductReservationdayFind($Condition = array()){
        $Data = Db::name("product_reservationday")->where($Condition)->find();

        return $Data;
    }
    /**
     * @param array $Condition  修改条件
     * @param $Data  修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改预约商品日历
     * 肖亚子
     */
    public static function ProductReservationdayUpdate($Condition = array(),$Data){
        $DataUp = Db::name('product_reservationday')->where($Condition)->update($Data);

        return $DataUp;
    }

    /**
     * @param $Data  添加内容
     * @return int|string
     * 添加预约商品指定日期加价、属性
     * 肖亚子
     */
    public static function ProductReservationpriceAdd($Data){
        $Add = Db::name('product_reservationprice')->insertGetId($Data);

        return $Add;
    }

    /**
     * @param array $Codnition 查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 查询预约商品指定日期加价、属性
     * 肖亚子
     */
    public static function ProductReservationpriceFind($Codnition = array()){

        $Data = Db::name('product_reservationprice')->where($Codnition)->find();

        return $Data;
    }

    /**
     * @param array $Condition 修改条件
     * @param $Data 修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改预约商品指定日期加价、属性
     * 肖亚子
     */
    public static function ProductReservationpriceUpdate($Condition = array(),$Data){
        $DataUp = Db::name('product_reservationprice')->where($Condition)->update($Data);

        return $DataUp;
    }

}

