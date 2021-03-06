<?php
/**
 * Created by PhpStorm.
 * User: Admini
 * Date: 2018/11/19
 * Time: 17:24
 */

namespace app\system\model;

/**
 * Excel操作类
 * Class ExcelModel
 * @package app\system\model
 */
class ExcelModel
{

    function export($column=array(), $data=array(), $firstMergeTitle='', $sheetName='报表', $fileName='', $isDown=true, $savePath='./static/finance'){
        vendor('phpexcel.PHPExcel');
        $obj = new \PHPExcel();
        //横向单元格标识
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $obj->getActiveSheet(0)->setTitle($sheetName);   //设置sheet名称
        $_row = 1;   //设置纵向单元格标识
        if($column){
            $_cnt = count($column);
            $obj->getActiveSheet(0)->mergeCells('A'.$_row.':'.$cellName[$_cnt-1].$_row);   //合并单元格
            $obj->setActiveSheetIndex(0)->setCellValue('A'.$_row, $firstMergeTitle);  //设置合并后的单元格内容
            $_row++;
            $i = 0;
            foreach($column AS $v){   //设置列标题
                $obj->setActiveSheetIndex(0)->setCellValue($cellName[$i].$_row, $v);
                $i++;
            }
            $_row++;
        }
        //填写数据
        if($data){
            $i = 0;
            foreach($data AS $_v){
                $j = 0;
                $linecolor = \PHPExcel_Style_Color::COLOR_BLACK;
                if($_v['colorstyle'] == 'red'){
                    $linecolor = \PHPExcel_Style_Color::COLOR_RED;
                }elseif($_v['colorstyle'] == 'blue'){
                    $linecolor = \PHPExcel_Style_Color::COLOR_BLUE;
                }
                foreach($_v AS $_cell){
                    //指定行的颜色
                    $obj->setActiveSheetIndex(0)->getStyle($cellName[$j] . ($i+$_row))->getFont()->getColor()->setARGB($linecolor);
                    $obj->getActiveSheet(0)->setCellValueExplicit($cellName[$j] . ($i+$_row), $_cell, \PHPExcel_Cell_DataType::TYPE_STRING);
                    $j++;
                }
                $i++;
            }
        }
        //文件名处理
        if(!$fileName){
            $fileName = uniqid(time(),true);
        }
        $objWrite = \PHPExcel_IOFactory::createWriter($obj, 'Excel2007');
        if($isDown){   //网页下载
            header('pragma:public');
            header("Content-Disposition:attachment;filename=$fileName.xlsx");
            $objWrite->save('php://output');exit;
        }
        //$_fileName = iconv("utf-8", "gb2312", $fileName);   //转码
        //$_savePath = $savePath.$_fileName.'.xlsx';
        //$objWrite->save($_savePath);
        //return $savePath.$fileName.'.xlsx';
    }



}