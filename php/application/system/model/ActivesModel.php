<?php

namespace app\system\model;
use think\Db;
use think\Request;

class ActivesModel extends BaseModel{

    public static function TableName(){
        return Db::name("actives");
    }

    /**
     * @param array $condition  查询条件
     * @param int $psize        分页数默认你第一页
     * @param int $pageSize     分页条数默认15条
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取活动数列表数据
     * 肖亚子
     */
    public static function getActivesList($condition = array(),$psize = 1,$pageSize = 15){
        $Field = "a.a_id,a.thumb,a.type,a.title,a.starttime,a.endtime,a.status,a.addtime,group_concat(re.fullname) as fullname,a.productids";
        $count     = self::TableName()
                        ->alias("a")
                        ->field($Field)
                        ->join("region re","instr(a.citycode,re.id )","left")
                        ->where($condition)
                        ->group("a.a_id")
                        ->buildSql();

        $pageCount = ceil($count/$pageSize);

        $list      = self::TableName()
                        ->alias("a")
                        ->field($Field)
                        ->join("region re","instr(a.citycode,re.id )","left")
                        ->where($condition)
                        ->page($psize, $pageSize)
                        ->group("a.a_id")
                        ->order("a.provencecode asc,a.citycode asc,a.sort asc")
                        ->select();

        $PaginaTion = parent::Paging($count,$psize,$pageCount,$list);
        return $PaginaTion;
    }

    /**
     * @param $Data  添加内容
     * @return int|string
     * 添加活动
     * 肖亚子
     */
    public static function getActivesAdd($Data){
        $Add = self::TableName()->insert($Data);

        return $Add;
    }

    /**
     * @param array $Condition  查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 查询活动详情
     * 肖亚子
     */
    public static function getActivesFind($Condition = array()){
        $Data = self::TableName()
                ->alias("a")
                ->field("a.*,GROUP_CONCAT(r.name) as regionname,GROUP_CONCAT(r.parentid) as province,
                        GROUP_CONCAT(r.id) as city")
                ->join("region r","instr(a.citycode,r.id)","left")
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
     * 获取活动表详情
     * 肖亚子
     */
    public static function getActivesFinds($Condition = array()){
        $Data = self::TableName()->where($Condition)->find();

        return $Data;
    }

    /**
     * @param array $Condition  修改条件
     * @param $Data             修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改活动内容
     * 肖亚子
     */
    public function getActivesUpdate($Condition = array(),$Data){
        $DataUp = self::TableName()->where($Condition)->update($Data);

        return $DataUp;
    }
    /**
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取商品分类
     * 肖亚子
     */
    public function CategoryList(){
       $CaList = Db::name("product_category")
                    ->field("category_id,category_name")
                    ->where(array("category_status" => 1,"category_del"=>0))
                    ->select();

       return $CaList;
    }

}