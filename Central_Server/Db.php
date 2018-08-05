<?php
/**
 * ITRSSO CUS数据库处理函数库
 * 包含了数据库的访问及任务队列处理函数
 * @author	Jan.F@隽
 **/

class Db{
	function __construct($config){
		$this -> log = new Log();
		try{
			$this -> db = new PDO("mysql:host={$config['dbhost']};dbname={$config['dbname']}", $config['dbusername'], $config['dbpassword'],array(
				PDO::ATTR_PERSISTENT => true
			));
		}catch (PDOException $e) {
			$this -> log -> log_error('Db_CUS 数据库连接失败:' . $e->getMessage());
			$this -> log -> log_error('CUS 启动失败!');
			die();
		}
	}

	function query_listen_task(){
		//获取最后一次监听的任务id
		$ex_id = $this -> db -> query("SELECT value FROM sys WHERE variables = 'last_listen_id'");
		$ex_id = $ex_id -> fetchColumn();
		//获取新任务的行数 注意:$count是本次轮询任务的关键指针,表示只处理指令到下行sql查询到的范围
		$count = $this -> db -> query("SELECT COUNT(*) FROM listen_task_queue WHERE id > '{$ex_id}'");
		$count = $count -> fetchColumn();
		if ($count == 0) return false;
		//如果count >=1 有新任务 先写入lastid指针到sys表
		$cur_id = $ex_id + $count; 
		$this -> db -> exec("UPDATE sys SET value = '{$cur_id}' WHERE variables = 'last_listen_id'");
		//正式获取新任务
		$task_queue = $this -> db -> query("SELECT * FROM listen_task_queue WHERE id > '{$ex_id}'");
		$task_queue = $task_queue -> fetchall(PDO::FETCH_ASSOC);
		//处理指令内容
		for ($x = 0;$x <= ($count-1) ; $x++){
			$task = json_decode($task_queue[$x]['command']);
			switch ($task -> type) {
				case 'login':
					$this -> login($task); 
					break;
				case 'logout':
					$this -> logout($task);
					break;
				default:
						
					break;
			}
		}
	}

	function query_broadcast_task(){

	}

	function login($task){ 
		$dbuser = $this -> db -> query("SELECT * FROM user WHERE username = '{$task -> username}'");
		$dbuser = $dbuser -> fetch(PDO::FETCH_ASSOC);
		//status 表示登陆检验状态 0-密码错误 1-覆盖token 2-正常登陆 3-初始值
		$status = 3;
		//检查用户存在&密码校对
		if ($dbuser == false || $task -> password != $dbuser['password']) {
			$status = 0;
		}else{
				//检查是否已经登陆
				$check = $this -> db -> query("SELECT * FROM temp_token WHERE uid = '{$dbuser['uid']}'");
				$check = $check -> fetch();
				//返回false表示没登录,登陆库找不到uid
				if ($check != false) {
					//返回true,用户已登录,删除就token并生成新token
					$ex_token = $check['token'];
					$cur_token = md5($this -> log -> timenow().$dbuser['uid']);
					$status = 1;
				}else{
					//新登录,批准
					$cur_token = md5($this -> log -> timenow().$dbuser['uid']);
					$status = 2;
				}
			}
		//根据status 生成指令广播
		switch ($status) {
			case '0':
				$send_task = json_encode(["type" => "user_rejection","username" => $task -> username]);
				break;
			case '1':
				$this -> db -> exec("UPDATE temp_token SET token = '{$cur_token}' WHERE uid = '{$dbuser['uid']}'");
				$send_task = json_encode(["type" => "token_overwrite","ex_token" => $ex_token,"cur_token" => $cur_token,"detail" => $dbuser['options']]);
				break;
			case '2':
				$this -> db -> exec("INSERT INTO temp_token (token,uid) VALUES ('{$cur_token}','{$dbuser['uid']}')");
				$send_task = json_encode(["type" => "token_add","cur_token" => $cur_token,"options" => json_decode($dbuser['options'])]);
				break;	
			default:
				break;
		}
		//写入广播队列
		$this -> db -> exec("INSERT INTO broadcast_task_queue (command) VALUES ('{$send_task}') ");
	}

	function logout($task){
		$token = $task -> token;
		$check = $this -> db -> query("SELECT COUNT(*) FROM temp_token WHERE token = '{$token}'");
		$check = $check -> fetchColumn();
		
		$status = 3;
		if ($check == 0) {
			$status = 0;
		}else{
			$check = $check -> fetchColumn();
			$status = 1;
		}
		
		switch ($status) {
			case '0':
				$send_task = json_encode(["type" => "logout_rejection","token" => $token]);
				break;
			case '1':
				$send_task = json_encode(["type" => "token_delete","token" => $token]);
				$this -> db -> exec("DELETE FROM temp_token WHERE token = '{$token}'");
				break;
			default:				
				break;
		}

		$this -> db -> exec("INSERT INTO broadcast_task_queue (command) VALUES ('{$send_task}')");
	}
}