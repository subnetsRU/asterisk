<?php
/*
    Project: bot.SUBNETS.RU
    AJAM configuration file
    
    http://bot.subnets.ru/telegram/
    https://github.com/subnetsRU/asterisk
    
    (c) 2017 SUBNETS.RU project (Moscow, Russia)
    Authors: Nikolaev Dmitry <virus@subnets.ru>, Panfilov Alexey <lehis@subnets.ru>
*/

date_default_timezone_set('Europe/Moscow');
setlocale(LC_ALL, array("ru_RU.UTF-8"));
ini_set('default_charset','UTF-8');
ini_set('mbstring.internal_encoding','UTF-8');
error_reporting(E_ALL);
ini_set('display_errors', 'on');

define('VERSION','0.2');

$config=array();
//Logging
$config['log']=array(
    'switch' => 0,				//0: отключено; 1: включено;
    'dir'=> dirname(__FILE__)."/logs",		//не забываем создать директорию и разрешить запить полозователю, от которого запущен HTTP: chown -R www:www /full/path/to/logs
    'file'=>"ajam.log",
);

$config['ajam']=array('sing'=>"my_SECRET_sign");

$config['rawman']=array(
    'host'=>"127.0.0.1",
    'port'=>"8088",
    'user'=>"AJAM user in managers.conf",
    'pass'=>"ajam user password in managers.conf",
    'prefix'=>"prefix in http.conf",
    'timeout'=>10,
);

$config['restrictIP']=array();			//Ограничение доступа по IP-адресу. Если не заполнено - разрешено всем, а иначе только указанные IP-адреса получат доступ
//$config['restrictIP'][]="127.0.0.1";
//$config['restrictIP'][]="1.1.1.1";
//$config['restrictIP'][]="2.2.2.2";

$config['policy']="deny";			//allow: запрещены только те команды, что указаны в $config['commands'],все остальные команды разрешены
						//deny: разрешены только те команды, что указаны в $config['commands'], все остальные команды запрещены (режим по умолчанию)
$config['commands']=array(			
    'core show *',
    'sip show *',
    'dialplan show *',
    'database show *',
    'queue show *',
);

?>