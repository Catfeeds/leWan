<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/30
 * Time: 9:27
 * 城市模型
 * 肖亚子
 */

namespace app\api\model;
use think\Db;
use think\Request;

class AreaModel{

    public static function TableName(){
        return Db::name("region");
    }

    /**
     * @param array $Condition 查询条件
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取城市数据
     * 肖亚子
     */
    public static function AreaList($Condition = array()){
        $List = self::TableName()
                    ->field("id as areacode,name,parentid,leveltype,quanpin,pjstatus")
                    ->where($Condition)
                    ->select();

        return $List;
    }

    /**
     * @param $Condition 查询条件
     * @return mixed
     * 获取城市名
     * 肖亚子
     */
    public function AreaName($Condition){
        $Data = self::TableName()->where($Condition)->value("name");

        return $Data;
    }


}