<?php

namespace app\system\model;
use think\Db;
use think\Request;

class BranchModel extends BaseModel{

    public static function TableName(){
        return Db::name("fgs_admin_user");
    }

    /**
     * @param array $Condition  查询条件
     * @param int $Psize        分页数默认你第一页
     * @param int $PageSize     分页条数默认50条
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取分公司管理员数据
     * 肖亚子
     */
    public static function BranchList($Condition = array(),$Psize = 1,$PageSize = 50){
        $Field = "a.id,a.pid,a.username,a.sub_name,a.status,a.last_login_ip,a.last_login_time,a.create_time,group_concat(r.name) as regionname";
        $Count = self::TableName()
                    ->alias("a")
                    ->field($Field)
                    ->join("fgs_branch_city c","c.sub_id = a.id and c.del = 1","left")
                    ->join("region r","r.id = c.city_code","left")
                    ->where($Condition)
                    ->group("a.id")
                    ->count();

        $PageCount = ceil($Count/$PageSize);

        $List      = self::TableName()
                        ->alias("a")
                        ->field($Field)
                        ->join("fgs_branch_city c","c.sub_id = a.id and c.del = 1","left")
                        ->join("region r","r.id = c.city_code","left")
                        ->where($Condition)
                        ->group("a.id")
                        ->page($Psize, $PageSize)
                        ->select();

        $PaginaTion = parent::Paging($Count,$Psize,$PageCount,$List);

        return $PaginaTion;
    }

    /**
     * @param $data  添加内容
     * @return int|string
     * 添加分公司管理员
     * 肖亚子
     */
    public static function BranchAdd($data){
        return self::TableName()->insertGetId($data);
    }

    /**
     * @param array $condition 查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取分公司管理员详情
     * 肖亚子
     */
    public static function BranchFind($Condition = array()){
        $Field = "a.id,a.pid,a.username,a.sub_name,a.status,au.username as usernames,
        group_concat(r.name) as regionname,GROUP_CONCAT(c.province_code) as province,GROUP_CONCAT(c.city_code) as city";

        $Data =  self::TableName()
                    ->alias("a")
                    ->field($Field)
                    ->join("fgs_admin_user au","au.id = a.pid","left")
                    ->join("fgs_branch_city c","c.sub_id = a.id","left")
                    ->join("region r","r.id = c.city_code","left")
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
     * 获取分公司管理员信息
     */
    public static function BranchFinds($Condition = array()){
        $Data = self::TableName()->where($Condition)->find();

        return $Data;
    }
    /**
     * @param array $condition  修改条件
     * @param $Data             修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改分公司管理员信息
     * 肖亚子
     */
    public static function BranchUpdate($Condition = array(),$Data){
        $branchUp = self::TableName()->where($Condition)->update($Data);
        return $branchUp;
    }

    /**
     * @param array $Condition  修改条件
     * @param $Data             修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改分公司管理员关联账号的所有管理城市信息
     * 肖亚子
     */
    public static function BranchUpdateAll($Condition = array(),$Data){
        $Data = self::TableName()
                ->alias("fa")
                ->join("fgs_branch_city fc","fc.sub_id = fa.id","left")
                ->where($Condition)
                ->update($Data);

        return $Data;
    }



    /**
     * @param $Data  添加内容
     * @return int|string
     * 添加分公司管理员管理城市
     * 肖亚子
     */
    public static function BranchCityAdd($Data){
        $Data = Db::name("fgs_branch_city")->insertAll($Data);

        return $Data;
    }

    /**
     * @param array $Condition 查询条件
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * 获取分公司管理员管理城市详情
     * 肖亚子
     */
    public static function BranchCityFind($Condition = array()){
        $Data = Db::name("fgs_branch_city")->where($Condition)->find();

        return $Data;
    }

    /**
     * @param array $Condition  修改条件
     * @param $Data             修改内容
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * 修改分公司管理员管理城市数据
     * 肖亚子
     */
    public static function BramchCityUpdate($Condition = array(),$Data){
        $Data = Db::name("fgs_branch_city")
            ->where($Condition)
            ->update($Data);

        return $Data;
    }

}