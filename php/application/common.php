<?php

use think\Config;

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017 https://www.xieenjie.com
// +----------------------------------------------------------------------
// | Author: jay
// +----------------------------------------------------------------------
// 应用公共函数文件

/**
 * 调试函数
 * Enter description here ...
 * @param $data1
 */
function fuck($data1, $data2 = [], $data3 = []) {
    \think\Db::rollback();
    echo '<pre>';
    print_r($data1);
    print_r($data2);
    print_r($data3);
    exit;
}

/**
 * 删除指定目录下面的所有文件
 * Enter description here ...
 * @param unknown_type $dir
 */
function rrmdir($src) {
    $dir = opendir($src);
    while (false !== ( $file = readdir($dir))) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if (is_dir($full)) {
                rrmdir($full);
            } else {
                unlink($full);
            }
        }
    }
    closedir($dir);
    rmdir($src);
}

/**
 * @param string $url 请求路劲
 * @return mixed
 * curlget请求
 * 肖亚子
 */
function curlGet($url = ''){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数

    //https请求 不验证证书和host
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}


/**
 * 获取微信access_token
 */
function get_wx_access_token() {
    $APPID = WX_APPID;
    $APPSECRET = WX_APPSECRET;

    $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$APPID&secret=$APPSECRET";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
    //使用https协议
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);    //json 字符串
    curl_close($ch);
    $msg = json_decode($result);   //将消息专为obj
    return $msg->access_token;
}


/**
 * @param string $url       请求路劲
 * @param string $postData  请求参数
 * @param array $options    空，可不填
 * @return mixed
 */
function curlPost($url = '', $postData = '', $options = array()){

    if (is_array($postData)) {
        $postData = http_build_query($postData);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
    if (!empty($options)) {
        curl_setopt_array($ch, $options);
    }
    //https请求 不验证证书和host
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * curl微信接口
 */
function wx_get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //不返还数据
    //使用https协议
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);    //json 字符串
    curl_close($ch);
    $res = json_decode($result, JSON_UNESCAPED_SLASHES);
    return $res;
}

/**
 * curl微信接口
 */
function wx_post($url, $msg_body) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $msg_body); // 发送json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($msg_body)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //不返还数据
    //使用https协议
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);    //json 字符串
    curl_close($ch);
    $res = json_decode($result, JSON_UNESCAPED_SLASHES);
    return $res;
}

function wx_post2($url, $msg_body) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $msg_body); // 发送json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($msg_body)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  //不返还数据
    //使用https协议
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);    //json 字符串
    curl_close($ch);
    $res = json_decode($result, JSON_UNESCAPED_SLASHES);
    return $res;
}

/**
 * 获取微信网页授权的url
 * @param unknown $url
 * @return string
 */
function get_wx_snslink($url) {
    $codeurl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WX_APPID . "&redirect_uri=" . urlencode($url) . "&response_type=code&scope=snsapi_userinfo&state=" . time() . "#wechat_redirect";
    return $codeurl;
}

/**
 * 生成网页授权的url
 * @param unknown $url
 * @param unknown $args
 * @return string
 */
function urlwx($url, $args) {
    if (config('app_debug')) {
        return url($url, $args);
    } else {
        if(session('?user')){
            return url($url, $args);
        }
        return get_wx_snslink(WX_HTTPADDR . url($url, $args));
    }
}


/**
 * 根据参数数组 返回 字符串
 * @param type $params
 * @return type
 */
function get_url_params_str($params) {
    $str = '';
    foreach ($params as $k => $v) {
        $str .= $k . '=' . $v . '&';
    }
    if ($str != '') {
        $str = substr($str, 0, strlen($str) - 1);
    }
    return $str;
}

/**
 * post 请求
 * @param type $fields
 * @param type $url
 * @return 返回数组
 */
function post($fields, $url) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
    $return_str = curl_exec($curl);
    curl_close($curl);
    return json_decode($return_str, JSON_UNESCAPED_SLASHES);
}

/**
 * 退出
 * @param $msg
 */
function alert($msg){
    echo '<meta charset="utf-8" /><script>alert("'.$msg.'"); window.history.go(-1);</script>';
}


function valid_require($field, $name, $type='string', $len=1){
    $value = I('post.'.$field);
    if(empty($value)){
        ajax_return($name.'必填');
    }
    if($type == 'int' && intval($value) == 0){
        ajax_return($name.'必填');
    }
    if($type == 'string' && $len > 1){
        if(strlen($value) < $len){
            ajax_return($name.'至少'.$len.'个字符');
        }
    }
    //正则
    if($type != 'int' && $type != 'string'){
        if(!validateExtend($value, $type)){
            ajax_return($name.'格式不正确');
        }
    }
    return $value;
}

function ajax_return($msg, $code = 400, $data = '') {
    header('Content-Type:application/json; charset=utf-8');
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: Origin, No-Cache, X-Requested-With, If-Modified-Since, Pragma, Last-Modified, Cache-Control, Expires, Content-Type, X-E4M-With, token, version, signature");
    if ($data != '') {
        $result['result'] = $data;
    } else {
        $result['result'] = (object) array();
    }
    $result['code'] = $code;
    $result['msg'] = $msg;
    echo json_encode($result);
    exit;
}



/**
 * 通用验证函数扩展
 * @param string $data 待验证数据
 * @param string $method 验证方法名 (从通用配置文件中获取对应正则信息)
 * @param boolean $exp 正则是否自定义,默认否
 */
function validateExtend($data, $method, $exp=false) {
    if ((!is_numeric($data) && empty($data)) || empty($method)) {
        return false;
    }

    if (!$exp) {
        $common_validate = config('COMMON_VALIDATE');
        $method = isset($common_validate[$method]) ? $common_validate[$method] : false;
    }

    if ($method) {
        return preg_match($method, $data);
    }
    else {
        return false;
    }
}

/**
 * 阿里云发送短信
 * 入住：SMS_145592744[orderno,ruzhu,likai,price,num]
 * @return object
 * stdClass Object
    (
    [Message] => 模板不合法(不存在或被拉黑)
    [RequestId] => 2D50876A-F8CC-46EF-BDBA-4B5BF6B21788
    [Code] => isv.SMS_TEMPLATE_ILLEGAL； 状态码-返回OK代表请求成功,其他错误码详见错误码列表
    )
 *
 * 调用示例：sendSms('15828218481', config('aliyun_sms.templatecode1'),['orderno'=>'2323','day'=>'2017-12-12','name'=>'人家']);
 */
function sendSmsAliyun($tel, $templatecode, $param) {

    vendor('aliyunsms.SignatureHelper');
    $params = array ();

    // *** 需用户填写部分 ***
    // 必填：是否启用https
    $security = false;

    // 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    $accessKeyId = config('aliyun_sms.accessKeyId');
    $accessKeySecret = config('aliyun_sms.accessKeySecret');

    // 必填: 短信接收号码
    $params["PhoneNumbers"] = $tel;

    // 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
    $params["SignName"] = config('aliyun_sms.sign');

    // 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
    $params["TemplateCode"] = $templatecode;

    // 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
//    $params['TemplateParam'] = Array (
//        "code" => "12345",
//        "product" => "阿里通信"
//    );
    $params['TemplateParam'] = $param;
    // 可选: 设置发送短信流水号
    //$params['OutId'] = "12345";
    // 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
    //$params['SmsUpExtendCode'] = "1234567";
    // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
    if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
        $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
    }

    // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
    $helper = new \Aliyun\DySDKLite\SignatureHelper();

    // 此处可能会抛出异常，注意catch
    $content = $helper->request(
        $accessKeyId,
        $accessKeySecret,
        "dysmsapi.aliyuncs.com",
        array_merge($params, array(
            "RegionId" => "cn-hangzhou",
            "Action" => "SendSms",
            "Version" => "2017-05-25",
        )),
        $security
    );
    if($content->Code != 'OK'){
        ajaxreturn($content->Message,200);
    }
    return $content;
}


//****成都创信信息技术短信发送
/**
 * @param $tel  发送手机号
 * @param $content 短信内容
 * @param bool $return 是否返回结果
 * @return bool
 */
function sendSmsCdxx($tel, $content, $return=false){
    $target = "http://dc.28inter.com/sms.aspx";
    //替换成自己的测试账号,参数顺序和wenservice对应
    $post_data = "action=send&userid=".config('cdxx_sms.userid')."&account=".config('cdxx_sms.account')."&password=".config('cdxx_sms.password')."&mobile=$tel&sendTime=&content=".rawurlencode($content);
    //$binarydata = pack("A", $post_data);
    $gets = cdCxjsPost($post_data, $target);
    $start=strpos($gets,"<?xml");
    $data=substr($gets,$start);
    $xml=simplexml_load_string($data);
    $res = json_decode(json_encode($xml),TRUE);
    if($return){
        if($res['returnstatus'] == 'Success'){
            return true;
        }else{
            $date = date('Y-m-d H:i:s');
            file_put_contents('./msglog.txt', "\r\nmessage({$date}):".$res['message'], FILE_APPEND);
            return false;
        }
    }else{
        if($res['returnstatus'] == 'Faild'){
            //ajaxreturn($res['message'], 400);
            file_put_contents('./msglog.txt', "\r\nmessage:".$res['message'], FILE_APPEND);
        }
        return true;
    }
}

function cdCxjsPost($data, $target) {
    $url_info = parse_url($target);
    $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
    $httpheader .= "Host:" . $url_info['host'] . "\r\n";
    $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
    $httpheader .= "Content-Length:" . strlen($data) . "\r\n";
    $httpheader .= "Connection:close\r\n\r\n";
    //$httpheader .= "Connection:Keep-Alive\r\n\r\n";
    $httpheader .= $data;

    $fd = fsockopen($url_info['host'], 80);
    fwrite($fd, $httpheader);
    $gets = "";
    while(!feof($fd)) {
        $gets .= fread($fd, 128);
    }
    fclose($fd);
    return $gets;
}


//自定义获取header
function get_all_header(){
    // 忽略获取的header数据。这个函数后面会用到。主要是起过滤作用
    $ignore = array('host','accept','content-length','content-type');
    $headers = array();
    //这里大家有兴趣的话，可以打印一下。会出来很多的header头信息。咱们想要的部分，都是‘http_'开头的。所以下面会进行过滤输出。
    /*    var_dump($_SERVER);
        exit;*/

    foreach($_SERVER as $key=>$value){
        if(substr($key, 0, 5)==='HTTP_'){
            //这里取到的都是'http_'开头的数据。
            //前去开头的前5位
            $key = substr($key, 5);
            //把$key中的'_'下划线都替换为空字符串
            $key = str_replace('_', ' ', $key);
            //再把$key中的空字符串替换成‘-’
            $key = str_replace(' ', '-', $key);
            //把$key中的所有字符转换为小写
            $key = strtolower($key);

            //这里主要是过滤上面写的$ignore数组中的数据
            if(!in_array($key, $ignore)){
                $headers[$key] = $value;
            }
        }
    }
    //输出获取到的header
    return $headers;
}

function valinarray($a, $v){
    $array = explode(',', $a);
    foreach ($array as $k=>$b){
        if($b == $v){
            echo 'checked';
            break;
        }
    }
}

/**
 * 获取数组长度
 * @param $array
 * @return int
 */
function getExplodlen($array){
    $i = 0;
    foreach ($array as $k=>$v){
        if(trim($v) == ''){
            break;
        }
        $i++;
    }
    return $i;
}


function date2($exp, $time){
    if(!$time){
        return '';
    }else{
        return date($exp, $time);
    }
}

function ajaxreturn($msg, $status=200, $data=[]){
    $res['code']    = $status;
    $res['message'] = $msg;
    $res['data']    = $data;
    header('content-type:application/json;charset=utf8');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    \think\Db::rollback();
    exit;
}

/**
 * 根据token获取会员
 */
function getUserByToken(){
    $token = $_POST['token'];
    //1.查询用户
    $user = \think\Db::name('user')->where(['token'=>$token])->find();
    if(!$user){
        ajaxreturn('用户不存在', 400);
    }
    if($user['status'] > 1){
        ajaxreturn('账号异常', 400);
    }
    return $user;
}

/**
 * 调用新浪接口将长链接转为短链接
 * @param  string        $source    申请应用的AppKey 如有过期，请到新浪开发者中心重新申请
 * @param  array|string  $url_long  长链接，支持多个转换（需要先执行urlencode)
 * @return array
 */
function createShortUrl($url_long,$source = 1681459862){
    // 参数检查
    if(empty($source) || !$url_long){
        return false;
    }
    // 参数处理，字符串转为数组
    if(!is_array($url_long)){
        $url_long = array($url_long);
    }
    // 拼接url_long参数请求格式
    $url_param = array_map(function($value){
        return '&url_long='.urlencode($value);
    }, $url_long);
    $url_param = implode('', $url_param);
    // 新浪生成短链接接口
    $api = 'http://api.t.sina.com.cn/short_url/shorten.json';
    // 请求url
    $request_url = sprintf($api.'?source=%s%s', $source, $url_param);
    $result = array();
    // 执行请求
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $request_url);
    $data = curl_exec($ch);
    if($error=curl_errno($ch)){
        return false;
    }
    curl_close($ch);
    $result = json_decode($data, true);
    if(!empty($result) && $result[0]['type']<1){
        return $result[0]['url_short'];
    }else{
        return 'err';
    }
//    return $result;
}

/**
 * 生成短连接
 * @param $url
 * @return mixed
 */
function createShortUrl1($url){
    $geturl = 'http://suo.im/api.php?url='.urlencode($url);
    return curlGet($geturl);
}


function notifytest(){
    $data['out_trade_no'] = 'LW18122808441008628015858';
    $data['transaction_id'] = 'LW18122808441008628015858';
    return $data;
}


/**
 * 把返回的数据集转换成Tree
 * @access public
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 */
function listToTree($list, $pk='id',$pid = 'pid',$child = '_child',$root=0) {
    // 创建Tree
    $tree = array();
    if(is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            }else{
                if (isset($refer[$parentId])) {
                    $parent =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 对查询结果集进行排序
 * @access public
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function listSortBy($list,$field, $sortby='asc') {
    if(is_array($list)){
        $refer = $resultSet = array();
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortby) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc':// 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ( $refer as $key=> $val)
            $resultSet[] = &$list[$key];
        return $resultSet;
    }
    return false;
}

/**
 * 拼接图片全路径并替换'\'为'/'
 * @param $picpath
 * @return mixed
 */
function getFullPicPath($picpath){
   $path =  Config("picture") .$picpath;
    return str_replace('\\', '/', $path);

}

function getArray($Arr,$Key,$Val){

    if ($Key && $Val){
        $Arr[$Key] = $Val;
    }

    return $Arr;
}

/**
 *
 * @param type $cate 日志分类 （方便筛选）
 * @param type $message 日志消息
 * @param type $level 日志级别
 */
function GLog($cate,$message,$level= \Think\Log::ALERT){
    $msg = "($cate) $message";
    \Think\Log::record($msg,$level);
}

function getSelfSignStr($data){
    $str = '';
    if(is_array($data)){
        $data['signkey'] = Config::get('signkey');
        foreach ($data as $key=>$val) {
            $str.=$key.'='.$val.'&';
        }

    }
    return sha1($str);
}

/**
 * 加密函数
 * @param $str
 * @param string $key 掩码
 * @return string
 */
function lockStr($str,$key='lewanlianmeng'){
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    $nh = rand(0,64);
    $ch = $chars[$nh];
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $str = base64_encode($str);
    $tmp = '';
    $i=0;$j=0;$k = 0;
    for ($i=0; $i<strlen($str); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = ($nh+strpos($chars,$str[$i])+ord($mdKey[$k++]))%64;
        $tmp .= $chars[$j];
    }
    return urlencode($ch.$tmp);
}

/**
 * 解密函数
 * @param $str
 * @param string $key 掩码
 * @return bool|string
 */
function unlockStr($str,$key='lewanlianmeng'){
    $txt = urldecode($str);
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    $ch = $str[0];
    $nh = strpos($chars,$ch);
    $mdKey = md5($key.$ch);
    $mdKey = substr($mdKey,$nh%8, $nh%8+7);
    $str = substr($str,1);
    $tmp = '';
    $i=0;$j=0; $k = 0;
    for ($i=0; $i<strlen($str); $i++) {
        $k = $k == strlen($mdKey) ? 0 : $k;
        $j = strpos($chars,$str[$i])-$nh - ord($mdKey[$k++]);
        while ($j<0) $j+=64;
        $tmp .= $chars[$j];
    }
    return base64_decode($tmp);
}


//将XML转为array
function xmlToArray($xml)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    return $values;
}


/**
 * 构建层级（树状）数组
 * @param array  $array          要进行处理的一维数组，经过该函数处理后，该数组自动转为树状数组
 * @param string $pid_name       父级ID的字段名
 * @param string $child_key_name 子元素键名
 * @return array|bool
 */
function array2tree(&$array, $pid_name = 'pid', $child_key_name = 'children')
{
    $counter = array_children_count($array, $pid_name);
    if (!isset($counter[0]) || $counter[0] == 0) {
        return $array;
    }
    $tree = [];
    while (isset($counter[0]) && $counter[0] > 0) {
        $temp = array_shift($array);
        if (isset($counter[$temp['id']]) && $counter[$temp['id']] > 0) {
            array_push($array, $temp);
        } else {
            if ($temp[$pid_name] == 0) {
                $tree[] = $temp;
            } else {
                $array = array_child_append($array, $temp[$pid_name], $temp, $child_key_name);
            }
        }
        $counter = array_children_count($array, $pid_name);
    }

    return $tree;
}


/**
 * 子元素计数器
 * @param array $array
 * @param int   $pid
 * @return array
 */
function array_children_count($array, $pid)
{
    $counter = [];
    foreach ($array as $item) {
        $count = isset($counter[$item[$pid]]) ? $counter[$item[$pid]] : 0;
        $count++;
        $counter[$item[$pid]] = $count;
    }

    return $counter;
}