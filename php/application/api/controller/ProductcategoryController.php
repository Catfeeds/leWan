<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/10/30
 * Time: 16:02
 * 商品分类控制器
 * 肖亚子
 */

namespace app\api\controller;
use app\api\model\ProductcategoryModel;


class ProductcategoryController extends ApiBaseController{

    public function CategoryList(){
        $Condition = array();

        $Condition["category_status"] = array("eq",1);
        $Condition["category_del"]    = array("eq",0);

        $List =  ProductcategoryModel::CateList($Condition);

        return $this->returnApiData('获取成功',200,$List);
    }

}