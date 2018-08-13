<?php
/**
 * SOCSSO Client端轮询类
 * @author	Jan.F@隽
 **/

class Polling_handler extends Client{
	function __construct($db,$pipe){
		$this -> db = $db;
		$this -> pipe = $pipe;
	}

	function query_task(){
		//获取最后一次监听的任务id
		$ex_id = $this -> db -> query("SELECT value FROM sys WHERE variables = 'last_query_id'");
		$ex_id = $ex_id -> fetchColumn();
		//获取新任务的行数 注意:$count是本次轮询任务的关键指针,表示只处理指令到下行sql查询到的范围
		$count = $this -> db -> query("SELECT COUNT(*) FROM task_queue WHERE id > '{$ex_id}'");
		$count = $count -> fetchColumn();
		if ($count == 0) return false;
		//如果count >=1 有新任务 先写入lastid指针到sys表
		$cur_id = $ex_id + $count; 
		$this -> db -> exec("UPDATE sys SET value = '{$cur_id}' WHERE variables = 'last_query_id'");
		//正式获取新任务
		$task_queue = $this -> db -> query("SELECT * FROM task_queue WHERE id > '{$ex_id}'");
		$task_queue = $task_queue -> fetchall(PDO::FETCH_ASSOC);
		//处理指令内容
		for ($x = 0;$x <= ($count-1) ; $x++){
			if (!($this -> is_json($task_queue[$x]['task']))) {
				$this -> log_error("[Polling_handler]捕捉到指令格式错误,废弃指令,详情:".print_r($task,true));
				return false;
			}
			$task = json_decode($task_queue[$x]['task'],true);
			$call_function = $task['type'];
			$this -> log_info("[Polling_handler]处理指令,调用:{$call_function}");
			$this -> $call_function($task);
		}
	}

}