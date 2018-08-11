<?php
/**
 * SOCSSO Client端通讯Socket管道类
 * 以守护进程运行!
 * @author	Jan.F@隽
 **/

class Socket extends Client{
	function __construct($config){
		$this -> log_info("[Client]初始化开始,CUS信息:{$config['CUS_host']}:{$config['CUS_port']},SQLite信息:{$config['sqlitefilename']}");
		//==========初始化数据库连接=================
		if (file_exists($config['sqlitefilename'])){
			$isinit = false;
		}else{
			$isinit = true;
		}	
		try{
			$this -> db = new PDO('sqlite:'.$config['sqlitefilename']);
			$this -> db -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch(PDOException $e) {
			$this -> log_error('[Client]数据库连接/创建失败:' . $e->getMessage());
			$this ->  log_error('[Client]启动失败!');
			exit;
		}
		if ($isinit){
			$this -> log_info("[Client]寻找不到数据库文件,按照配置创建数据库");
			$initsql =  <<< SQL
CREATE TABLE 'sys' ('id' integer,'variables' text,'value' text,PRIMARY KEY ('id'));
INSERT INTO sys (variables,value) VALUES ('last_query_id',0);
INSERT INTO sys (variables,value) VALUES ('last_callback_id',0);
CREATE TABLE 'task_queue' ('id' integer,'task' text,PRIMARY KEY ('id'));
CREATE TABLE 'user' ('id' integer,'token' text,'username' text,'nickname' text,'credit' text,'create_time' text,'update_time' text,'last_login' text,'options' text,PRIMARY KEY ('id'));
CREATE TABLE 'task_callback' ('id' integer,'username' text,'result' text,'detail' text,PRIMARY KEY('id'));
SQL;
			$this -> db -> exec($initsql);
		}else $this -> log_info("[Client]数据库完成载入");
		//==========初始化数据库连接完毕=================
		$this -> pipe = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
		$this -> pipe ->set([
			'open_eof_split' => true,
    		'package_eof' => "\r\n",
    	]);
		$this -> ph = new Polling_handler($this -> db,$this -> pipe);

		//定时轮询任务
		swoole_timer_tick(1000, function($timer_id){
			$this -> ph -> query_task();
		});

		$this -> pipe -> on("connect",function(){
			$this -> log_info('[Client]连接至CUS成功!');
		});

		$this -> pipe -> on("receive",function($client,$data){
				$task = json_decode($data,true);
				//根据$task中的type调用同名函数
				$call_function = $task['type'];
				$this -> log_info("[Socket通信]收到指令: {$data} 调用: {$task['type']}");
				$this -> $call_function($task);
		});

		$this -> pipe -> on("close",function(){
			$this -> log_info('[Client]连接关闭');
			exit;
		});

		$this -> pipe -> on("error",function(){
			$this -> log_error('[Client]连接到CUS失败或掉线,进程终止');
			exit;
		});

		$this -> log_info("[Client]初始化操作完成,开始建立套接字连接");
		$this -> pipe -> connect($config['CUS_host'],$config['CUS_port']);
	}
}