<?php
/**
 * SOCSSO CUS启动文件
 * 以守护进程运行!
 * @author	Jan.F@隽
 **/
require_once('CUS.php');
require_once('Polling_handler.php');
require_once('Socket.php');

//运行配置
define("CONFIG",[
	//CUS轮询外部指令的时间间隔(ms),无需太短,因为外部指令大多为维护指令
	"polling_time" => 3000,
	//socket的端口
	"socketport" => 23345,
	//数据库host
	"dbhost" => "127.0.0.1",
	//数据库用户名
	"dbusername" => "socsso",
	//数据库密码
	"dbpassword" => "socsso",
	//默认数据库名
	"dbname" => "itrsso_cus"
]);

$socket = new Socket();
