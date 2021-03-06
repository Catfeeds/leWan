<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/27
 * Time: 17:00
 * 轮播图模型
 * 肖亚子
 */
namespace  app\api\model;
use think\Db;
use think\Request;

class BannerModel{

    public static function TableName(){
        return Db::name("banner");
    }

    /**
     * @param array $Condition 查询条件
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取轮播数据
     * 肖亚子
     */
    public static function BannerList($Condition = array()){
        $Data = self::TableName()
                    ->field("alt,url,pic,jump,position,cat_id,pr_id,route")
                    ->where($Condition)
                    ->order("sort asc")
                    ->select();

        return $Data;
    }
}