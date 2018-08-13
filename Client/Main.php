<?php
/**
 * SOCSSO Client Daemon启动文件
 * 以守护进程运行!
 * @author	Jan.F@隽
 **/
require_once('Client.php');
require_once('Polling_handler.php');
require_once('Socket.php');

//配置
define("CONFIG",[
	//轮询子系统指令时间间隔(ms),该间隔对SSOC的响应速度极为重要
	"polling_time" => 400,
	//CUS socket的host
	"CUS_host" => "127.0.0.1",
	//CUS socket的端口
	"CUS_port" => "23345",
	//缓存数据库路径,如果不存在,Client将自动创建一个,该配置必须要和函数库的配置一样
	"sqlite_db_path" => "SSOC_CLIENT_TEMP.db"
]);

$socket = new Socket();