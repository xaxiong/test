<?php
namespace angle\Util;
use think\Config;
use think\Db;

/**
 * 常用工具类
 * User: xaxiong
 * Date: 2016/12/19
 * Time: 13:47
 */
class Tool{

    /**
     * 简单加密处理函数
     * @param $data
     * @return string
     */
    public static function encodeData($data){
        return urlencode(json_encode($data));
    }

    /**
     * 简单解密处理函数
     * @param $data
     * @return mixed
     */
    public static function decodeData($data){
        return json_decode(urldecode($data),true);
    }

    /**
     * 获取数据表前缀
     * @param string $mprefix 取值为:'o':origial原始表;'d':dest 业务表;'sys':system系统表，标准知识库模型;'com':common公共表
     * @return mixed|string
     */
    public static function getPrefix($mprefix=''){
        return empty($mprefix)?Config::get('database.prefix'):Config::get('database.prefix').Config::get('database.mprefix')[$mprefix];
    }
    public static function getTablePrefix($mprefix=''){
        $typeList=[
            "origial"=>'o_',
            "dest"=>'t_',
            "system"=>'sys_',
            "common"=>'com_',
        ];
        if(in_array($mprefix,array_keys($typeList))){
            return $typeList[$mprefix];
        }else{
            return '';
        }
    }
    public static function getTableCategoryTypes($type){
        $typeList=[
            'dest'=>0,
            "origial"=>1,
            "system"=>2,
            "common"=>3,
        ];
        if(in_array($type,array_keys($typeList))){
            return $typeList[$type];
        }else{
            return '';
        }
    }

    /**
     * 对excel里的日期进行格式转化
     * @param $val 数据
     * @param $format 格式
     * @return string
     */
    public static function GetExceDate($val,$format){
        if(!empty($val)) {
            $jd = \GregorianToJD(1, 1, 1970);
            $date = \JDToGregorian($jd + intval($val) - 25569);
//        list($month, $day, $year) = explode('/',$date);
            if (!empty($format)) $date = date($format, strtotime($date));
            return $date;
        }else{
            return null;
        }
    }


    /**
     * 格式化数字
     * @param $number 要格式化的数值
     * @param $type 类型 normal,electric
     * @param $type 单位后缀
     */
    public static function numberFormat($number,$type='normal',$extUnit='')
    {
        /**
         * @var array 数据格式化类型
         */
        $FORMAT_TYPE=array(
            'normal'=>array(
                array(
                    'base'=>100000000,
                    'unit'=>'亿'
                ),
                array(
                    'base'=>10000,
                    'unit'=>'万'
                ),
                array(
                    'base'=>1000,
                    'unit'=>'千'
                ),
                array(
                    'base'=>1,
                    'unit'=>''
                )
            ),
            'electric'=>array(
                array(
                    'base'=>1000000000000,
                    'unit'=>'TW'
                ),
                array(
                    'base'=>1000000000,
                    'unit'=>'GW'
                ),
                array(
                    'base'=>1000000,
                    'unit'=>'MW'
                ),
                array(
                    'base'=>1000,
                    'unit'=>'KW'
                ),
                array(
                    'base'=>1,
                    'unit'=>'W'
                )
            )
        );

        $FORMAT_TYPE_ITEM=$FORMAT_TYPE[$type];
        //$keys=array_keys($FORMAT_TYPE_ITEM);
        //var_dump($FORMAT_TYPE_ITEM[$keys[0]]);exit();
        if(empty($number)){
            $arr['value']= 0;
            $arr['unit']='';
            $arr['base']=1;
            return $arr;
        }
        return  self::getMaxUnit($number,$FORMAT_TYPE_ITEM,$extUnit);

    }

    /**
     * 获取最大单位数据
     * @param $number 数字
     * @param $AllArr 数据类型配置
     * @param string $extUnit 单位
     * @param array $currArr 当前数据
     * @return array
     */
    public static function getMaxUnit($number,$AllArr,$extUnit='',$currArr=array()){
        //if($index>count($desArr)) return $returnArr;
        if(empty($currArr)){
            $currArr=$AllArr[0];
        }
        $key=$currArr['base'];
        $value=$currArr['unit'];

        $leaveNum=$number/$key;
        $resdata=array();
        //arrayStep::getInstance($AllArr)->setCurrent($key);
        if($leaveNum > 0 && $leaveNum < 1){
            //$nextArr=arrayStep::getInstance($AllArr)->getNext();
            $nextArr=next($AllArr);//var_dump($nextArr);
            $data=self::getMaxUnit($number,$AllArr,$extUnit,$nextArr);
            if(!empty($data))return $data;
        }else{
            $resdata= array(
                'value'=>number_format(round($leaveNum)),
                'unit'=>$value.$extUnit,
                'base'=>$key
            );
            //var_dump($resdata);
            return $resdata;
            exit();
        }
    }

    /**
     * 无限极分类树的生成
     * @param $list 数据
     * @param string $pk key名称
     * @param string $pid 父级编号
     * @param string $child
     * @param int $root
     * @return array
     */
    public static function list_to_tree($list, $pk='id',$pid = 'pid',$child = 'child',$root=0) {
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
//无限极分类返回列表行记录
    /**
     *
     * @param unknown $cate_info_sort  返回的数据
     * @param unknown $data  原始数据
     * @param string $pk  表主键
     * @param string $pid_name  表父字段id
     * @param number $pid  开始的父字段id
     * @param string $kk 分割线
     */
   public static function list_to_Tree1(&$cate_info_sort,$data,$pk='id',$pid_name="pid",$pid=0,$kk="|"){
        foreach($data as $k=> $v){
            if($v[$pid_name]==$pid){
                foreach ($v as $ke=>$vl){
                    $cate_info_sort[$k][$ke] = $vl;
                }
                $cate_info_sort[$k]["kk"] = $kk;
                $pos = strpos($kk, "--");
                if($pos === false){
                    $cate_info_sort[$k]["sun"] = 0;
                }else{
                    $cate_info_sort[$k]["sun"] = 1;
                }
                self::cate_sort_child($cate_info_sort,$data ,$pk,$pid_name,$v[$pk],"&nbsp;&nbsp;&nbsp;&nbsp;".$kk."--");
            }
        }
    }
//无限极分类返回列表行记录
    /**
     *
     * @param unknown $cate_info_sort  返回的数据
     * @param unknown $data  原始数据
     * @param string $pk  表主键
     * @param string $pid_name  表父字段id
     * @param number $pid  开始的父字段id
     * @param string $kk 分割线
     */
    private static function cate_sort_child(&$cate_info_sort,$data,$pk='id',$pid_name="role_pid",$pid=0,$kk="|"){
        foreach($data as $k=> $v){
            if($v[$pid_name]==$pid){
                foreach ($v as $ke=>$vl){
                    $cate_info_sort[$k][$ke] = $vl;
                }
                $cate_info_sort[$k]["kk"] = $kk;
                $pos = strpos($kk, "--");
                if($pos === false){
                    $cate_info_sort[$k]["sun"] = 0;
                }else{
                    $cate_info_sort[$k]["sun"] = 1;
                }
                self::cate_sort_child($cate_info_sort,$data ,$pk,$pid_name,$v[$pk],"&nbsp;&nbsp;&nbsp;&nbsp;".$kk."--");
            }
        }
    }

    /**
     * GET调用接口获取返回值
     * @param $url url地址
     * @param array $header 请求头配置
     * @return mixed
     */
    public function curlGET($url,$header=array()){


        $_SESSION['issTcSession']['get'][$url]['url'] = $url;
        $_SESSION['issTcSession']['get'][$url]['header'] = $header;
//        echo $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT,60);   //只需要设置一个秒的数量就可以
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,0);
        $starttime=microtime(true);
        $temp = curl_exec($ch);
        $endtime=microtime(true);
        $data="---------接口请求总时间:".($endtime-$starttime)."-----------------------\n本次接口地址为:$url\n\n\n\n";
        $_SESSION['logdata'.$_GET['a']]=$data;
        curl_close($ch);
        return $temp;
    }

    /**
     * POST调用接口获取返回值
     * @param $url url地址
     * @param string $data 参数
     * @param array $header  请求头
     * @return mixed
     */
    public function curlPOST($url,$data='',$header=array()){

        $_SESSION['issTcSession']['post'][$url]['url'] = $url;
        $_SESSION['issTcSession']['post'][$url]['data'] = $data;
        $_SESSION['issTcSession']['post'][$url]['header'] = $header;
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT,60);   //只需要设置一个秒的数量就可以
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,0);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回

        $starttime=microtime(true);
        $tmpInfo = curl_exec($curl); // 执行操作
//        if (curl_errno($curl)) {
//            echo 'Errno'.curl_error($curl);
//        }
        $endtime=microtime(true);
        $data="---------接口请求总时间:".($endtime-$starttime)."-----------------------\n本次接口地址为:$url\n\n\n\n";
        $_SESSION['logdata'.$_GET['a']]=$data;
        curl_close($curl); // 关键CURL会话
        return $tmpInfo; // 返回数据
    }

    /**
     * json数据返回格式化
     * @param $data 数据
     * @param string $status 状态
     * @param string $errorCode 错误代码
     * @param string $message 消息
     * @param null $total 数据条数
     */
    public function jsonReturn($data,$status='1',$errorCode='1',$message='',$total=null)
    {

        if($data)
        {
            $json['status'] = 1;
            $json['errorCode'] = $errorCode;
            $json['errorMsg'] = '请求成功';
            if($total)
            {
                $json['total'] = $total;
            }
            else
            {
                $json['total'] = count($data);
            }
            $json['data'] = $data;
        }
        else
        {
            if($status != '1')
            {
                $json['status'] = $status;
                $json['errorCode'] = $errorCode;
                $json['errorMsg'] =empty($message)? '请求失败':$message;
            }
            else
            {
                $json['status'] = '0';
                $json['errorCode'] = '0';
                $json['errorMsg'] =empty($message)? '请求失败':$message;
            }
            $json['total'] = '0';
            $json['data'] = array();
        }
        header("Content-type: application/json");
        echo json_encode($json);

    }


    /**
     * 保留小数
     * @param $number 数字
     * @param int $num 几位小数
     * @return float|int
     */
    public function decimal($number,$num=1){
        $number  = round($number,$num);
        $number1 = intval($number);
        $number2 = $number > $number1 ? $number : $number1;
        return $number2;

    }

    /**
     * 数组排序
     * @param $arr 数组
     * @param $keys key名
     * @param string $type 排序类型 asc,desc
     * @param int $is0
     * @param int $isK
     * @return array
     */
    function arraySort($arr, $keys, $type = 'asc',$is0=0,$isK=0) {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v){
            $keysvalue[$k] = $v[$keys];
        }
        $type == 'asc' ? asort($keysvalue) : arsort($keysvalue);
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {

            if($isK == 1)
            {
                $new_array[$k] = $arr[$k];
            }
            else
            {
                $new_array[] = $arr[$k];
            }

        }
        if($is0 == 1)
        {
            foreach($new_array as $vo_is0)
            {
                if($vo_is0[$keys] > 0){
                    $new_array_s[] = $vo_is0;
                }
            }
            return $new_array_s;
        }
        return $new_array;
    }

    /**
     * 判断是否 为空 给 后端数据转成null
     * @param $data
     * @param int $isStr 0表示不转字符串，1：表示传成字符串
     * @return mixed
     */
    function arrIsNull($data,$isStr=0)
    {
        foreach($data as $k=>&$vo)
        {
            if(empty($vo) && ($k != 'startRow') && ($k != 'endRow') && ($k != 'education'))
            {
                $vo = null;
            }else{
                if($isStr){
                    $vo .= "";
                }
            }
        }
        return $data;
    }

    /**
     * 着色
     * @param $colorarr
     * @param $data
     * @return mixed
     */
    function color2data($colorarr,$data){
        $count = count($colorarr);
        $count_data = count($data);
        $d = intval($count_data/$count);
        $d = $d <= 1 ? 1 : $d;
        $c = 0;
        $n = 1;
        foreach($data as &$vo)
        {
            $temp['style'] = 'esriSFSSolid';
            $temp['color'] = $colorarr[$c];
            $outline['color'] = array(106, 212, 238,255);
            $outline['width'] = 1;
            $temp['outline'] = $outline;
            $vo['coverStyle'] =$temp;
            if($n == $d)
            {
                if($c < $count-1)
                {
                    $c++;
                }
                $n = 1;
            }
            else
            {
                $n++;
            }
        }
        return $data;
    }

    /**
     * 截取字符串
     * @param $str
     * @param $start
     * @param $lenth
     * @return string
     */
    public function subStr($str, $start, $lenth)
    {
        $str1 = mb_substr($str,$start,$lenth,'UTF8');
        return $str==$str1 ? $str : $str1.'...';
    }

    /**
     * 截取字符串
     * @param $str
     * @return int
     */
    public function str2num($str)
    {
        $num1 = (int)$str;
        if($num1 == $str)
        {

            return $num1;
        }
        else
        {
            return $str;
        }

    }

    /**
     * 字符串转数组转字符串
     * @param $data
     * @return mixed
     */
    public function str2arr2str($data){
        foreach($data as &$vo){
            foreach($vo as &$vo1){
                if(is_array($vo1))
                {
                    $vo1 = json_encode($vo1);
//                    $vo1 = implode(',',$vo1);
                }
            }
        }
        return $data;
    }

    function letter($value)
    {
        $letter = '';
        do {
            $letter = chr(65 + ($value % 26)) . $letter;
            $temp = intval($value / 26);
            if ($temp > 0) {
                $value = $value - 26;
            }
            $value = intval($value / 26);
        } while ($temp != 0);
        return $letter;
    }

    /**
     * 调试函数
     * @param unknown $str 要叠加字符的变量名
     * @param unknown $addstr 要添加的字符
     * @param string $ifdel 是否将之前的变量清空
     * @return string
     */
    public static function halt($logFile,$str,$ifdel=false)
    {
        header("Content-type: text/html; charset=utf-8");
        $logname =LOG_PATH. $logFile . '-' . date("Ymd") . '.html';
        $tmpstr = date("Y-m-d h:i:sa") . ' ';

        if ($ifdel) {
            //清除日志文件内容
            @file_put_contents($logname, "");
        }
        if (!empty($str)) {
            $tmpstr .= '--' . $str;
        }
        try {
            //将日导写入日导文件
            self::writeFile($logname, $tmpstr . "\r\n", 'a+');
        } catch (Exception $e) {
            echo '写入日志出现错误！，错误内容为：' . $e->getMessage();
            //$this->writeFile($logname,$str.'Error:'.$e);
        }
    }


    /**
     * 写文件
     * @param    string  $file   文件路径
     * @param    string  $str    写入内容
     * @param    char    $mode   写入模式
     */
    public static function writeFile($file,$str,$mode='w')
    {
        $oldmask = @umask(0);
        $fp = @fopen($file,$mode);
        @flock($fp, 3);
        if(!$fp)
        {
            Return false;
        }
        else
        {
            @fwrite($fp,$str);
            @fclose($fp);
            @umask($oldmask);
            Return true;
        }
    }

    /**
     * gbk 转 utf8
     * @param $str
     * @return string
     */
    public static function utf8_to_gbk($str){
        return mb_convert_encoding($str,'GBK','UTF-8');
    }

    /**
     * utf8 转 gbk
     * @param $str
     * @return string
     */
    public static function gbk_to_utf8($str){
        return mb_convert_encoding($str, 'UTF-8', 'GBK');
    }

    /**
     * utf8 转 gbk
     * @param $str
     * @return string
     */
    public static function utf8_to_gb18030($str){
        return mb_convert_encoding($str,  'GB18030','UTF-8');
    }
    public static function gb18030_to_utf8($str){
        return mb_convert_encoding($str,'UTF-8',  'GB18030');
    }

    public static function path_info($filepath,$ds=DS)
    {
        $filepath=str_replace(['/','\\'],[$ds,$ds],$filepath);
        $path_parts = array();
        $path_parts ['dirname'] = rtrim(substr($filepath, 0, strrpos($filepath,$ds)),$ds).$ds;
        $path_parts ['basename'] = ltrim(substr($filepath, strrpos($filepath, $ds)),$ds);
        $path_parts ['extension'] = substr(strrchr($filepath, '.'), 1);
        $path_parts ['filename'] = ltrim(substr($path_parts ['basename'], 0, strrpos($path_parts ['basename'], '.')),$ds);
        return $path_parts;
    }

    /**
     * 字符转码
     * @param $str 字符
     * @param string $Code 目标编码 "ASCII","UTF-8","GB2312","GBK","BIG5"
     * @return mixed|string
     */
    public  static function convertCode($str,$Code="GB2312"){
        $fromCode = mb_detect_encoding($str, array("ASCII","UTF-8","GB2312","GBK","BIG5"));
       // return mb_convert_encoding($str,$Code,$encode);
        return iconv($fromCode,$Code."//IGNORE",$str);
    }
    public static function array_iconv($arr,$out_charset="GB2312"){
        $fromCode = mb_detect_encoding(serialize($arr), array("ASCII","UTF-8","GB2312","GBK","BIG5"));
        return eval('return '.\mb_convert_encoding(var_export($arr,true).';',$out_charset,$fromCode));
    }

    /**
     * 是否为windows操作系统，
     * @return bool true:windows，false:其它操作系统
     */
    function isWinOS(){
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') return true;
        else return false;
    }
    /**
     * 获取当前操作系统
     * @return string
     */
    function getOS(){
        $os='';
        $Agent=$_SERVER['HTTP_USER_AGENT'];
        if (eregi('win',$Agent)&&strpos($Agent, '95')){
            $os='Windows 95';
        }elseif(eregi('win 9x',$Agent)&&strpos($Agent, '4.90')){
            $os='Windows ME';
        }elseif(eregi('win',$Agent)&&ereg('98',$Agent)){
            $os='Windows 98';
        }elseif(eregi('win',$Agent)&&eregi('nt 5.0',$Agent)){
            $os='Windows 2000';
        }elseif(eregi('win',$Agent)&&eregi('nt 6.0',$Agent)){
            $os='Windows Vista';
        }elseif(eregi('win',$Agent)&&eregi('nt 6.1',$Agent)){
            $os='Windows 7';
        }elseif(eregi('win',$Agent)&&eregi('nt 5.1',$Agent)){
            $os='Windows XP';
        }elseif(eregi('win',$Agent)&&eregi('nt',$Agent)){
            $os='Windows NT';
        }elseif(eregi('win',$Agent)&&ereg('32',$Agent)){
            $os='Windows 32';
        }elseif(eregi('linux',$Agent)){
            $os='Linux';
        }elseif(eregi('unix',$Agent)){
            $os='Unix';
        }else if(eregi('sun',$Agent)&&eregi('os',$Agent)){
            $os='SunOS';
        }elseif(eregi('ibm',$Agent)&&eregi('os',$Agent)){
            $os='IBM OS/2';
        }elseif(eregi('Mac',$Agent)&&eregi('PC',$Agent)){
            $os='Macintosh';
        }elseif(eregi('PowerPC',$Agent)){
            $os='PowerPC';
        }elseif(eregi('AIX',$Agent)){
            $os='AIX';
        }elseif(eregi('HPUX',$Agent)){
            $os='HPUX';
        }elseif(eregi('NetBSD',$Agent)){
            $os='NetBSD';
        }elseif(eregi('BSD',$Agent)){
            $os='BSD';
        }elseif(ereg('OSF1',$Agent)){
            $os='OSF1';
        }elseif(ereg('IRIX',$Agent)){
            $os='IRIX';
        }elseif(eregi('FreeBSD',$Agent)){
            $os='FreeBSD';
        }elseif($os==''){
            $os='Unknown';
        }
        return $os;
    }

    /**
     * 定时任务日志处理函数
     * @param $TaskData 任务数据
     * @param $status 状态值，取值为： 0：待执行 1：执行中 2：已执行 3：待删除 4：已删除 5：异常'
     * @param $info 备注信息
     * @return mixed
     */
    public static function TaskLog($TaskData,$status,$info){
        $data=array(
            'timer_id'=>$TaskData['timer_id'],
            'timer_event'=>$TaskData['timer_event'],
            'status'=>$status,
            'from_type'=>$TaskData['from_type'],
            'from_id'=>$TaskData['from_id'],
            'addtime'=>time(),
            'remark'=>$info,
        );
        return Db::table(Tool::getPrefix("sys").'timer_log')->insert($data);
    }

    public function checkmobile($mobilephone) {
        $mobilephone = trim($mobilephone);
        if(preg_match("/^13[0-9]{1}[0-9]{8}$|15[01236789]{1}[0-9]{8}$|18[01236789]{1}[0-9]{8}$/",$mobilephone)){
            return  $mobilephone;
        } else {
            return false;
        }
    }

    /**
     * Data转xml数据
     * @param $data array
     * @return mixed string
     */
    public static function data2xmlfunc($data){
        $xml = new \SimpleXMLElement('<xml></xml>');
        self::data2xml($xml, $data);
        return $xml->asXML();
    }
    public static function data2xml($xml, $data, $item = 'item'){
        foreach ($data as $key => $value) {
            is_numeric($key) && ($key = $item);
            if (is_array($value) || is_object($value)) {
                $child = $xml->addChild($key);
                self::data2xml($child, $value, $item);
            } else {
                if (is_numeric($value)) {
                    $child = $xml->addChild($key, $value);
                } else {
                    $child = $xml->addChild($key);
                    $node = dom_import_simplexml($child);
                    $node->appendChild($node->ownerDocument->createCDATASection($value));
                }
            }
        }
    }

    /**
     * 将xml字符串转xml数组
     * @param $xmlString
     */
    public static function getXmlArray($xmlString){
            //禁止引用外部xml实体
            libxml_disable_entity_loader(true);
            $values = json_decode(json_encode(simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
            return $values;
   }

    /**
     * php截取指定两个字符之间字符串，默认字符集为utf-8 Power by 大耳朵图图
     * @param string $begin  开始字符串
     * @param string $end    结束字符串
     * @param string $str    需要截取的字符串
     * @return string
     */
    public static function cut($begin,$end,$str){
        $b = mb_strpos($str,$begin) + mb_strlen($begin);
        $e = mb_strpos($str,$end) - $b;

        return mb_substr($str,$b,$e);
    }
    
    //简单赋值
    public static function getValue($data){
        return empty($data)?0:$data;
    }
    //同比，环比
    public static function getValue2($data1,$data2){
        if($data1=='0.00'){
            $data1 = 0;
        }
        if($data2=='0.00'){
            $data2 = 0;
        }
        if(empty($data1)&& empty($data2)){
            return 0;
        }else if(!empty($data1)&& empty($data2)){
            return 0;
        }else if(empty($data1)&& !empty($data2)){
            return 0;
        }else {
            return round(($data1-$data2)/abs($data2)*100,2);
        }
    }
    
    //占比
    public static function getValue3($data1,$data2){
        if($data1=='0.00'){
            $data1 = 0;
        }
        if($data2=='0.00'){
            $data2 = 0;
        }
        if(empty($data1)&& empty($data2)){
            return 0;
        }else if(!empty($data1)&& empty($data2)){
            return 0;
        }else if(empty($data1)&& !empty($data2)){
            return 0;
        }else {
            return ($data1)/abs($data2)*100;
        }
    }
    public static function DiffDate($date1, $date2) {
        //首次进入将分割开始与结束日期。
        $startDate = explode('-', $date1);
        $endDate = explode('-', $date2);

        $startYear = $startDate[0];
        $endYear = $endDate[0];

        $startMonth = $startDate[1];
        $endMonth = $endDate[1];

        $YmArr = [];
        //同一年和跨年分开处理。
        if($startYear != $endYear){
            //找出跨年所有的月份
            for ($year=$startYear; $year < $endYear; $year++) {
                for ($month=$startMonth; $month <= 12; $month++) {
                    $lenth = strlen($month);
                    if($lenth != 2){
                        $month = '0'.$month;
                    }
                    $Ym = $year.'-'.$month;//拼接日期，根据自己需要的格式拼接。
                    $YmArr[] = $Ym;

                }
                $startMonth = 1; //跨年的时候重置月份

            }
            //找出本年到现在的所有月份
            for ($nowMonth=1; $nowMonth <= $endMonth; $nowMonth++) {
                $lenth = strlen($nowMonth);
                if($lenth != 2){
                    $nowMonth = '0'.$nowMonth;
                }
                $nowYm = $endYear.'-'.$nowMonth;
                $YmArr[] = $nowYm;
            }
        }else{
            for ($nowMonth=$startMonth; $nowMonth <= $endMonth; $nowMonth++) {
                $lenth = strlen($nowMonth);
                if($lenth != 2){
                    $nowMonth = '0'.$nowMonth;
                }
                $nowYm = $endYear.'-'.$nowMonth;
                $YmArr[] = $nowYm;
            }
        }
        return $YmArr;
    }

    public static function timeDiff($stime,$etime='',$format='Y-m-d H:i:s'){
        $etime=empty($etime)?time():$etime;
        $d=$etime-$stime;
        if($d<0){
            return $stime;
        }else{
            if($d<60){
                return $d.'秒前';
            }else{
                if($d<3600){
                    return floor($d/60).'分钟前';
                }else{
                    if($d<86400){
                        return floor($d/3600).'小时前';
                    }else{
                        if($d<259200){//3天内
                            return floor($d/86400).'天前';
                        }else{
                            return date($format,$stime);
                        }
                    }
                }
            }
        }
    }
    public static function list_to_Tree2(&$cate_info_sort,$data,$pk='id',$pid_name="pid",$name='name',$pid=0,$pidName=""){
        foreach($data as $k=> $v){
            if($v[$pid_name]==$pid){
                foreach ($v as $ke=>$vl){
                    $cate_info_sort[$k][$ke] = $vl;
                }
                $cate_info_sort[$k]["pidName"] = $pidName;
                self::cate_sort_child2($cate_info_sort,$data ,$pk,$pid_name,$name,$v[$pk],$v[$name]);
            }
        }
    }
    private static function cate_sort_child2(&$cate_info_sort,$data,$pk='id',$pid_name="role_pid",$name='name',$pid=0,$pidName=""){
        foreach($data as $k=> $v){
            if($v[$pid_name]==$pid){
                foreach ($v as $ke=>$vl){
                    $cate_info_sort[$k][$ke] = $vl;
                }
                $cate_info_sort[$k]["pidName"] = $pidName;
                self::cate_sort_child2($cate_info_sort,$data ,$pk,$pid_name,$name,$v[$pk],$pidName.'->'.$v[$name]);
            }
        }
    }


}