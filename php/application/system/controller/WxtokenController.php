<?php

namespace app\system\controller;

use think\Controller;
use think\Db;
use app\system\model\WxModel;

/**
 * 微信url + token配置
 */
//define your token
//define("TOKEN", "860b9320428cd3fde55b22baea0f2936");
class WxtokenController extends Controller {

    public function index() {
        $wconfig = Db::name('sys_config')->where('tab = 2')->column('value', 'field');
        if ($wconfig['wx_status'] == 1) {
            $this->valid();    //接口配置是调用
        } else {
            $this->responseMsg();   //正式环境调用
        }
    }

    public function valid() {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            exit;
        }
        exit;
    }

    public function responseMsg() {
        //get post data, May be due to the different environments
        $postStr = isset($GLOBALS["HTTP_RAW_POST_DATA"])?$GLOBALS["HTTP_RAW_POST_DATA"]:'';
        if($postStr == ''){
            $postStr = file_get_contents('php://input');
        }
        //extract post data
        if (!empty($postStr)) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
              the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //微信接口工具
            $wm = new WxModel($postObj);
            $wm->response();
        } else {
            echo "";
            exit;
        }
    }

    private function checkSignature() {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }

        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    public function test() {
        //get post data, May be due to the different environments
        $postStr = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><FromUserName><![CDATA[FromUser]]></FromUserName><CreateTime>1550133351</CreateTime><MsgType><![CDATA[event]]></MsgType><Event><![CDATA[subscribe]]></Event></xml>";
        //extract post data
        if (!empty($postStr)) {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
              the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            //微信接口工具
            $wm = new WxModel($postObj);
            $wm->response();
        } else {
            echo "null";
            exit;
        }
    }

}

?>