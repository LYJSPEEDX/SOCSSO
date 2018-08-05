<?php
/**
 * ITRSSO CUS后端通讯Socket管道类
 * 以守护进程运行!
 * @author	Jan.F@隽
 **/
class Socket{

	function __construct($config){
		//初始化通信pipe
		$this -> log = new Log();
		$this -> log -> log_info('CUS 开始初始化,开放端口:'.$config['socketport']);
		$this -> pipe = new swoole_server("127.0.0.1", $config['socketport']); 
		$this -> db = new Db($config);
		$this -> pipe -> set([
			'log_level' => SWOOLE_LOG_NOTICE,
			//'task_worker_num' => 3,
			'worker_num' => 3,
		]);
		$this -> log -> log_info('Socket_CUS swoole版本为:' . swoole_version());

		//监听连接进入事件
		$this -> pipe -> on('connect', function ($pipe, $fd) {  
			echo "Client[{$fd}]: Connect.\n";
		});

		//监听数据接收事件
		$this -> pipe->on('receive', function ($pipe, $fd, $from_id, $data) {
			$this -> pipe->send($fd, "Server: ".$data.'   '.$from_id.'   '.$fd);
		});

		//监听连接关闭事件
		$this -> pipe->on('close', function ($pipe, $fd) {
			echo "Client[{$fd}]: Close.\n";
		});

		// worker|task 进程启动回调
		$this -> pipe -> on('WorkerStart',function($server,$worker_id){
			global $argv;
			$pid = posix_getpid();
			if($worker_id >= $this -> pipe ->setting['worker_num']) {
				$this -> log -> log_info("Socket_CUS TaskWorker任务进程[ID:{$worker_id},pid:{$pid}]创建");
			} else {
				$this -> log -> log_info("Socket_CUS EventWorker进程[ID:{$worker_id},pid:{$pid}]创建");
			}

			if($worker_id == 0 ){
				$this -> pipe -> tick(1000,function($timer_id){
					$this -> db -> query_listen_task();
				});
				$this -> pipe -> tick(1000,function($timer_id){

				});
			}
		});

		$this -> pipe -> on('Start',function($pipe){
			$this -> log -> log_info('Socket_CUS Reactor主线程启动,开始监听');
			$this -> db -> db -> exec("UPDATE sys SET value = '{$this -> pipe -> master_pid}' WHERE variables = 'master_pid'");
		});

		
		//启动服务器,循环开始
		$this -> pipe->start();
	}

	
}