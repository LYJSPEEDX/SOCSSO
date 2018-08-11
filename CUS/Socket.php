<?php
/**
 * SOCSSO CUS后端通讯Socket管道类
 * 以守护进程运行!
 * @author	Jan.F@隽
 **/
class Socket extends CUS{

	function __construct($config){
		$this -> log_info('[CUS]开始初始化,开放端口:'.$config['socketport']);
		
		$this -> pipe = new swoole_server("127.0.0.1", $config['socketport']); 
		$this -> pipe -> set([
			'task_worker_num' => 3,
			'worker_num' => 3,
			'open_eof_split' => true,
    		'package_eof' => "\r\n",
		]);

		//监听连接进入事件
		$this -> pipe -> on('connect', function ($pipe, $fd) {  
			$this -> log_info("[新连接][{$fd}]客户端连接");
		});

		//监听数据接收事件 需要带$fd进行分发
		$this -> pipe->on('receive', function ($pipe, $fd, $from_id, $data) {
			$this -> log_info("[Socket通信]接收到[$fd]客户端指令,开始分发到Task线程,内容:{$data}");
			$task = json_decode($data,true);
			//写入fd
			$task['fd'] = $fd;
			$this -> pipe -> task($task);
		});

		//Task进程回调
		$this -> pipe ->on('task',function($pipe,$task_id,$src_worker_id,$task){
			$this -> log_info("[Task]Task任务创建,调用细节[task_id]{$task_id}[src_worker_id]{$src_worker_id}[taskworker_id]{$this -> pipe -> worker_id}[调用]{$task['type']}");
			$call_function = $task['type'];
			$this -> $call_function($task);
		});

		//Task完成回调
		$this -> pipe -> on('finish',function($pipe,$task_id,$data){
		});
		
		// worker|task 进程启动回调
		$this -> pipe -> on('WorkerStart',function($server,$worker_id){
			global $argv;
			global $config;
			$pid = posix_getpid();
			if($worker_id >= $this -> pipe ->setting['worker_num']) {
				$this -> log_info("[CUS]TaskWorker任务进程[ID:{$worker_id},pid:{$pid}]创建");
			} else {
				$this -> log_info("[CUS]EventWorker进程[ID:{$worker_id},pid:{$pid}]创建");
			}
			//在每个进程开始,创建其数据库连接
			try{
				$this -> db = new PDO("mysql:host={$config['dbhost']};dbname={$config['dbname']}", $config['dbusername'], $config['dbpassword'],[PDO::ATTR_PERSISTENT => true,PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
			}catch (PDOException $e) {
				$this -> log_error('[CUS子进程]数据库连接失败,SHUTDOWN服务器:' . $e->getMessage());
				$shutdown = true;
				$this -> pipe -> after(1000,function(){
					$this -> pipe -> shutdown();
				});
			}
			//设定外部轮询定时器
			if($shutdown != true && $worker_id == 0 ){
				$this -> pipe -> tick(1000,function($timer_id){
					$this -> ph = new Polling_handler($this -> db,$this -> pipe);
					$this -> ph -> query_task();
				});
			}
		});

		//监听连接关闭事件
		$this -> pipe->on('close', function ($pipe, $fd) {
			$this -> log_info("[连接断开][{$fd}]客户端断开连接");
		});

		$this -> pipe -> on('Start',function($pipe){
			$this -> log_info('[CUS]Reactor主线程启动,CUS启动完毕');
			//$this ->  db -> exec("UPDATE sys SET value = '{$this -> pipe -> master_pid}' WHERE variables = 'master_pid'");
		});

		
		//启动服务器,循环开始
		$this -> pipe->start();
	}

	
}