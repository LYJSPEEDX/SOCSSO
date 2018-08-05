<?php
/**
 * ITRSSO CUS启动文件
 * 以守护进程运行!
 * @author	Jan.F@隽
 **/

require_once('Log.php');
require_once('Db.php');
require_once('Socket.php');

//请填入必要参数
$config = [
	"socketport" => 23345,
	"dbhost" => "127.0.0.1",
	"dbusername" => "root",
	"dbpassword" => "81082936Jun",
	"dbname" => "itrsso_cus"
];

$socket = new Socket($config);
