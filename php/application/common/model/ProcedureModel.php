<?php

namespace app\common\model;
use think\Config;
use Think\Db;

/**
 * 执行存储过程
 */
class ProcedureModel {

    /**
     * 执行存储过程
     * @param type $item
     */
    public function execute($action, $params = '', $params_out = '') {
        $sql = $this->sql($action, $params, $params_out);
        Db::execute($sql);
        $result = Db::query(Config::get('ALIYUN_TDDL_MASTER') . "select " . $params_out);
        if ($result && is_array($result) && count($result) > 0 && isset($result[0]['@error']) && $result[0]['@error'] == 1) {
            return false;
        }
        return $result;
    }

    private function sql($action, $params = '', $params_out = '') {
        $sql = Config::get('ALIYUN_TDDL_MASTER') . 'CALL ' . $action . '(';
        if ($params != '') {
            $sql .= $params;
        }
        if ($params != '' && $params_out != '') {
            $sql .= ',';
        }
        if ($params_out != '') {
            $sql .= $params_out;
        }
        $sql .= ');';
        return $sql;
    }

    public function outSql($action, $params = '', $params_out = '') {
        echo $this->sql($action, $params, $params_out) . '<br />';
    }

}
