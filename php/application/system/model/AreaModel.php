<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/12
 * Time: 10:31
 * 肖亚子
 * 城市model操作
 */

namespace app\system\model;
use think\Db;
use think\Request;


class AreaModel extends BaseModel {
    /**
     * @return \Model|\Think\Model
     * 省份模型
     */
    public static function TableName(){
        return Db::name("region");
    }

    /**
     * @param array $Condition 查询条件
     * @param int $Psize       分页数默认第一页
     * @param int $PageSize    分页数默认每页30条
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取城市每等级的列表数据
     * 肖亚子
     */
    public static function ProvenceList($Condition = array(),$Psize = 1,$PageSize = 30){

        $Count     = self::TableName()->where($Condition)->count();
        $PageCount = ceil($Count/$PageSize);
        $List      = self::TableName()->where($Condition)->page($Psize, $PageSize)->select();

        $PaginaTion = parent::Paging($Count,$Psize,$PageCount,$List);

        return $PaginaTion;
    }

    /**
     * @param array $Condition  查询条件
     * @param int $Type         城市等级
     * @param $Pcode            省级code标识
     * @return array|false|mixed|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 编辑城市获取数据
     * 肖亚子
     */
    public static function RegionAreaFind($Condition = array()){
        $FindData = self::TableName()->where($Condition)->find();

        return $FindData;
    }

    /**
     * @param $Condition  修改条件
     * @param $Data       修改内容
     * @return int|string|\think\db\Query|\Think\Model
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改城市内容
     * 肖亚子
     */

    public static function AreaUpdata($Condition, $Data){
         $AreaUp = self::TableName()->where($Condition)->update($Data);
        return $AreaUp;
    }

    public static function getOpenCity(){
        $where['status'] = 1;
        $where['leveltype'] = 2;
        $field = 'id,parentid,name,shortname,fullname,leveltype,citycode,maincity';
        $list = self::TableName()->field($field)->where($where)->select();
        return $list;
    }

}