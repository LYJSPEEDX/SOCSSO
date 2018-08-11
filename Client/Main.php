<?php
/**
 * SOCSSO Client Daemon启动文件
 * 以守护进程运行!
 * @author	Jan.F@隽
 **/
require_once('Client.php');
require_once('Polling_handler.php');
require_once('Socket.php');

$config = [
	"CUS_host" => "127.0.0.1",
	"CUS_port" => "23345",
	"sqlitefilename" => "SSOC_CLIENT_TEMP.db"
];


$socket = new Socket($config);