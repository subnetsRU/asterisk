<?php
/*
    Project: bot.SUBNETS.RU
    AJAM.PHP version: 0.1
    Last modify: 31.05.2017

    (c) 2017 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>

    http://bot.subnets.ru/telegram/
    https://github.com/subnetsRU/asterisk

    =======
    INSTALL
    =======
    * Edit /usr/local/etc/asterisk/manager.conf:
	- set in [general] section:
	    -- enabled=yes
	    -- webenabled=yes
	- add account login + password and don`t forget deny/permit, ex:
	    [ajam]
	    deny=0.0.0.0/0.0.0.0
	    permit=127.0.0.1/255.255.255.255
	    permit=XXX.XXX.XXX.XXX/255.255.255.255
	    secret = SECRET_PASSWORD
	    read = all
	    write = all

    * Edit /usr/local/etc/asterisk/http.conf and set in [general] section:
	- enabled=yes
	- bindaddr=0.0.0.0
	- prefix=<PREFIX param from config file>
	- enablestatic=yes

    * Edit ajam_config.php settings
*/

$err=array();
$param=array();
$ret=array();
$configFile = dirname(__FILE__)."/ajam_config.php";

if (isset($configFile) && $configFile && is_file($configFile)){
    if (is_readable($configFile)){
	if (!@include $configFile){
	    $err[]=sprintf("config file not found at %s",$configFile);
	}
    }else{
	$err[]=sprintf("config file %s not readable",$configFile);
    }
}else{
    $err[]=sprintf("config file %s don`t exists",$configFile);
}

if (count($err)==0){
    if (!isset($config) || !is_array($config) || !count($config)){
	$err[]="Check configuration file";
    }
}

if (count($err)==0){
    if (isset($config['log']) && isset($config['log']['switch'])){
	if ($config['log']['switch'] > 0){
	    if (isset($config['log']['dir']) && $config['log']['dir'] && is_dir($config['log']['dir'])){
		if (isset($config['log']['file']) && $config['log']['file']){
		    $logFile = @fopen($config['log']['dir']."/".date("Y-m-d",time())."_".$config['log']['file'], 'a+');
		}
	    }
	}
    }else{
	$config['log']['switch']=0;
    }
}

if (isset($_GET) && $_GET){
    $param=$_GET;
}elseif (isset($_POST) && $_POST){
    $param=$_POST;
}

/////////// FOR TEST PURPOSE ONLY /////////////
/*
    $param['action']="COMMAND";
    $param['command']="core show calls";
    $param['sign']=ajam_generate_sign($config,$param);
*/
//////////////////////////////////////////////

$remoteIP=getRemoteAddr();
if ( $config['log']['switch'] ){
    rawman_log(sprintf("REQUEST from %s: %s",$remoteIP,json_encode($param)));
}

if (isset($config['restrictIP']) && is_array($config['restrictIP'])){
    if (count($config['restrictIP']) > 0){
	if ($remoteIP){
	    $ipPassed=0;
	    foreach ($config['restrictIP'] as $ip){
		if ($ip == $remoteIP){
		    $ipPassed=1;
		    break;
		}
	    }
	    if (!$ipPassed){
		$ret['error']=99;
		$err[]="Access denied";
	    }
	}else{
	    $ret['error']=99;
	    $err[]="Access denied";
	}
    }
}else{
    $ret['error']=99;
    $err[]="Access denied";
}

if (count($err)==0){
    if (!isset($config['rawman']) || !is_array($config['rawman']) || !count($config['rawman'])){
	$err[]="check rawman configuration";
    }else{
	if (!isset($config['rawman']['host']) || !$config['rawman']['host']){
	    $err[]="rawman host unknown";
	}else{
	    if (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/",$config['rawman']['host'])){
		$err[]="rawman host IP format error";
	    }
	}
	if (!isset($config['rawman']['port']) || $config['rawman']['port'] == ""){
	    $err[]="rawman port unknown";
	}else{
	    if (!preg_match("/^\d{1,5}$/",$config['rawman']['port'])){
		$err[]="rawman port format error";
	    }
	}
	if (!isset($config['rawman']['user']) || !$config['rawman']['user']){
	    $err[]="rawman user unknown";
	}
	if (!isset($config['rawman']['pass']) || !$config['rawman']['pass']){
	    $err[]="rawman password unknown";
	}
	if (!isset($config['ajam']['sing']) || !$config['ajam']['sing']){
	    $err[]="sign not set";
	}
    }
    
    if (!isset($config['policy']) || !preg_match("/^(allow|deny)$/",$config['policy'])){
	$config['policy']="deny";
    }
    if (!isset($config['commands']) || !is_array($config['commands'])){
	$config['commands']=array();
    }
}

if (count($err)==0){
    if (isset($param) && $param && is_array($param)){
	$cmd=array();
	foreach ($param as $pk=>$pv){
	    $cmd[$pk]=$pv;
	}
	if (!isset($cmd['action'])){
	    $err[]="Action unknown";
	}else{
	    $actions=array("command","originate");
	    if (!in_array(strtolower($cmd['action']),$actions)){
		$ret['error']=98;
		$err[]="Access denied";
	    }else{
		if (isset($cmd['command'])){
		    $access=access($cmd['command'],$config);
		    if (!$access){
			$ret['error']=97;
			$err[]="Access denied";
		    }
		}
	    }
	}
    }else{
	$err[]="No params";
    }
}

if (count($err)==0){
    if (!ajam_validate_sign($cmd,$config)){
	$ret['error']=6;
	$err[]="Signature is not valid";
    }
}

if (count($err)==0){
    if (is_array($cmd)){
	$conn=rawman_connect($config);
	if (isset($conn['session_id']) && $conn['session_id'] && $conn['error']==0){
	    $cmd['session_id']=$conn['session_id'];
	    $ret=rawman_request($config,$cmd);
	    rawman_disconnect($config,$conn['session_id']);
	}else{
	    $ret=$conn;
	}
    }else{
	$ret['error']=7;
	$ret['description']="Command parse error";
    }
}

if (count($err)>0){
    if (!isset($ret['error'])){
	$ret['error']=90;
    }
    $ret['description']="Asterisk client: ".implode("; ",$err);
}

header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json; charset=UTF-8');
$rawmanReply=$ret;
$rawmanReply=jsonEncode($rawmanReply);
print $rawmanReply;

if ( $config['log']['switch'] ){
    rawman_log("REPLY: ".$rawmanReply."\n==============================================================\n");
    if (is_resource($logFile)){
	@fclose($logFile);
    }
}

function rawman_connect($config = array() ){
    $rawman=$config['rawman'];
    $cmd['action']="login";
    $cmd['username']=sprintf("%s",$rawman['user']);
    $cmd['secret']=sprintf("%s",$rawman['pass']);
    $cmd['sign']=ajam_generate_sign($config,$cmd);
    $ret=rawman_request($config, $cmd);
 return $ret;
}

function rawman_disconnect($config,$sess_id){
    $cmd['action']="Logoff";
    $cmd['session_id']=$sess_id;
    $cmd['sign']=ajam_generate_sign($config,$cmd);
    $ret=rawman_request($config,$cmd);
}

function rawman_generate_request_id($length="6"){
    $id=sprintf("%d%d",rand(1,9),rand(1,9));
    $chars = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ123456789";
    srand((double)microtime()*1000000);
    $i = 0;
    while ($i <= $length-2){
	$num = rand() % 33;
	$tmp = substr($chars, $num, 1);
	$id = $id . $tmp;
	$i++;
     }
 return $id;
}

function rawman_request($config = array(), $cmd = array()){
    $rawman=$config['rawman'];
    $ret=array(
	'request_id' => rawman_generate_request_id(),
	'error' => 0,
	'description' => "",
	'data' => "",
    );

    if ( !is_array($cmd) || !isset($cmd['action']) ){
	$ret['error']=7;
	$ret['description']="Action unknown";
	return $ret;
    }

    if ($cmd['action']!="login"){
	if (!$cmd['session_id']){
	    $ret['error']=9;
	    $ret['description']="You must login first";
	    return $ret;
	}
    }

    $loged_in=isset($cmd['session_id']) ? $cmd['session_id'] : "";
    unset($cmd['session_id']);

    $ctmp=array();
    foreach ($cmd as $k=>$v){
	$ctmp[]=sprintf("%s=%s",strtolower($k),urlencode(strtolower($v)));
    }
    $command = implode("&",$ctmp);
    unset($ctmp);

    if (!$command){
	$ret['error']=8;
	$ret['description']="Command is empty";
	return $ret;
    }

    $fp = @stream_socket_client(sprintf("tcp://%s:%s",$rawman['host'],$rawman['port']), $errno, $errstr, isset($rawman['timeout']) ? $rawman['timeout'] : 10);
    if (!$fp){
	$ret['error']=1;
	$ret['description']="Can`t connect: ".$errno." ".$errstr;
    }else{
	@stream_set_timeout($fp,$rawman['timeout']);
	$info = stream_get_meta_data($fp);
	$http_query=array();
	$http_query[]=sprintf("GET /%s/rawman?%s HTTP/1.0",$rawman['prefix'],$command);
	$http_query[]=sprintf("Host: %s:%s",$rawman['host'],$rawman['port']);
	$http_query[]=sprintf("User-Agent: BOT.SUBNETS.RU AJAM v%s",defined('VERSION') ? VERSION : "N/A");
	$http_query[]="Accept: */*";
	if ($loged_in){
	    $http_query[]=sprintf("Cookie: mansession_id=\"%s\"",$loged_in);
	}

	$query="";
	if (is_array($http_query)){
	    foreach ($http_query as $k=>$v){
		$query.=sprintf("%s\r\n",$v);
	    }
	    $query.="\r\n";
	}

	if ($query){
	    @fwrite($fp, $query);
	    while (!@feof($fp) && !$info['timed_out']) {
		$ret['data'].=@fgets($fp, 1024);
		$info = @stream_get_meta_data($fp);
	    }
	    @fclose($fp);
	    if ($info['timed_out']){
		$ret['error']=2;
		$ret['description']="Connection timed out";
	    }else{
		if (strlen($ret['data'])>0){
		    $data=explode("\n",preg_replace("/\r\n/","\n",$ret['data']));
		    if (preg_match("/^HTTP\/1.\d\s(\d+)/",$data[0],$tmp)){
			//HTTP/1.1 200 OK
			//HTTP/1.1 404 Not Found
			if ($tmp[1]==200){
			    foreach ($data as $k=>$v){
				if (preg_match("/^Response:\s(.*)$/",$v,$tmp)){
				    //Response: Success
				    //Message: Authentication accepted
				    if ($tmp[1]!="Success" && $tmp[1]!="Goodbye" && $tmp[1]!="Follows"){
					$ret['error']=13;
					$ret['description']=sprintf("Service response: %s => %s",$tmp[1],$data[$k+1]);
				    }
				    break;
				}
			    }
			    if ($ret['error'] == 0){
				if ($cmd['action']=="login"){
				    //Ловим куку сессии для последующего запроса:
				    //Set-Cookie: mansession_id="3b9f43dd"; Version=1; Max-Age=60
				    foreach ($data as $k=>$v){
					if (preg_match("/^Set-Cookie:\smansession_id=\"(\w+)\";/",$v,$tmp)){
					    $ret['session_id']=$tmp[1];
					    break;
					}
				    }
				}
			    }
			}elseif($tmp[1]==403){
			    $ret['error']=12;
			    $ret['description']="Forbidden";
			}elseif($tmp[1]==404){
			    $ret['error']=12;
			    $ret['description']="404 Not found";
			}elseif($tmp[1]==500){
			    $ret['error']=12;
			    $ret['description']="Service unavaliable";
			}else{
			    $ret['error']=12;
			    $ret['description']=$data[0];
			}
		    }else{
			$ret['error']=11;
			$ret['description']="AJAM reply unknown: ".$data[0];
		    }
		}else{
		    $ret['error']=19;
		    $ret['description']="No data received";
		}
	    }
	}else{
	    $ret['error']=10;
	    $ret['description']="Query is empty";
	}
    }

    if (isset($query) && $query){
	$requestLog=sprintf("AJAM REQUEST ID %s:",$ret['request_id']);
	$requestLog.=$query;
	rawman_log($requestLog);
	$requestLog=sprintf("AJAM REPLY ID %s:",$ret['request_id']);
	$requestLog.="\n".print_r($ret,true);
	rawman_log($requestLog);
    }

    if ($ret['error'] == 0 && $ret['data']){
	/////////////// PARSE DATA ////////////////////////////
	$ret['data'] = substr($ret['data'], strrpos($ret['data'], 'Response:'));
	if ($cmd['action']!="originate"){
	    $tmp=preg_replace("/\r/","",$ret['data']);
	    $tmp=explode("\n",$tmp);
	    unset($tmp[0],$tmp[1]);
	    if ($tmp[2]==""){
		unset($tmp[2]);
	    }
	    $data="";
	    foreach ($tmp as $k=>$v){
		if ($v && $v != "--END COMMAND--"){
		    $v=preg_replace("/</","",$v);
		    $v=preg_replace("/>/","",$v);
		    $data.=sprintf("%s\n",$v);
		}
	    }
	    $ret['data']=$data;
	    if (preg_match("/^No such command/",$ret['data'])){
		$ret['error']=100;
		$ret['description']="No such command in * CLI";
	    }
	}

	if ($ret['error']>0){
	    $ret['data']="";
	}
    }
 return $ret;
}

function ajam_generate_sign($config, $cmd){
    $ret="";
    $tmp="";
    if (isset($cmd) && $cmd && is_array($cmd)){
	$ajam_sing_request=isset($config['ajam']['sing']) ? $config['ajam']['sing'] : "ajamSingNotSet";
	ksort($cmd);
	foreach ($cmd as $k=>$v){
	    $tmp.=sprintf("%s=%s;",strtolower($k),strtolower($v));
	}
	$tmp.=$ajam_sing_request;
	$ret=md5($tmp);
    }
 return $ret;
}

function ajam_validate_sign(&$cmd,$config){
    $ajam_sing_request=isset($config['ajam']['sing']) ? $config['ajam']['sing'] : "";
    $tmp="";
    $sign="";
    if ( is_array($cmd) && (isset($cmd['sign']) && $cmd['sign']) ){
	$sign=$cmd['sign'];
	unset($cmd['sign']);
	ksort($cmd);
	foreach ($cmd as $k=>$v){
	    $tmp.=sprintf("%s=%s;",strtolower($k),strtolower($v));
	}
	$tmp.=$ajam_sing_request;
    }

    if ($sign === md5($tmp)){
	return 1;
    }else{
	return 0;
    }
}

function access($cmd,$config){
    $ret=0;
    if (!isset($config['policy']) || !$config['policy']){
	$config['policy']="deny";
    }
    if (!isset($config['commands']) || !is_array($config['commands'])){
	$config['commands']=array();
    }

    if ($config['policy']=="deny"){
	$ret=0;
	foreach ($config['commands'] as $k=>$v){
	    if (preg_match(sprintf("/^%s/",preg_replace("/\*/",".*",$v)),$cmd)){
		$ret=1;
		break;
	    }
	}
    }elseif($config['policy']=="allow"){
	$ret=1;
	foreach ($config['commands'] as $k=>$v){
	    if (preg_match(sprintf("/^%s/",preg_replace("/\*/",".*",$v)),$cmd)){
		$ret=0;
		break;
	    }
	}
    }
 return $ret;
}

function getRemoteAddr(){
    $remoteIP=isset($_SERVER['X-Real-IP']) ? $_SERVER['X-Real-IP'] : (isset($_SERVER['X-Forwarded-For']) ? $_SERVER['X-Forwarded-For'] : '');
    if (!$remoteIP){
	$remoteIP=isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : (isset($_SERVER['TCPREMOTEIP']) ? $_SERVER['TCPREMOTEIP'] : '');
    }
    if (!$remoteIP){
	$remoteIP=getenv('TCPREMOTEIP');
    }
 return $remoteIP;
}

function rawman_log($text){
    global $logFile;
    if (isset($logFile) && is_resource($logFile)){
	if (is_array($text)){
	    $tmp="";
	    foreach ($text as $k=>$v){
		$tmp.=sprintf("%s => %s\n",$k,$v);
	    }
	    $text=$tmp;
	}
	fputs($logFile, sprintf("[%s] %s\n",date("d.m.Y H:i:s",time()),$text));
    }
}

function jsonEncode( $array ){
 return json_encode(safedata2json($array),JSON_FORCE_OBJECT);
}

function safedata2json($data){
 return array2json($data, 'safedata2jsonCallback',true);
}

function safedata2jsonCallback($text){
    $entities = array( "/\\\\/", "/\r/", "/\t/", "/\n/", "/\"/", "/\//", "/\f/", "/\'/" );
    $replacements = array( "\\\\\\\\\\\\\\", "", "\\\\\\t", "\\\\\\n", "\\\\\\\"", "\\\\\\/", "\\\\\\f", "\\\\'" );
 return $text;
}

function array2json($input, $callback = null, $recurse = false){
    if (is_array($input)) foreach ($input as &$item) $item = array2json($item, $callback, true);
 return (!is_array($input) && is_callable($callback))? call_user_func($callback, $input): $input;
}

?>