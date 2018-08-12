<?php
/**
 * ITRSSO Client超级类
 * @abstract 不可实例化
 * @var $this -> db 已创建的pdo数据库对象
 * @var $this -> pipe swoole服务器对象
 * @author	Jan.F@隽
 **/
abstract class Client{
	/**
	 * 判断json格式是否有错
	 * @param string $string 需要检测的字符串
	 * @return Boolean
	 **/
	function is_json($string) {
		json_decode($string);
		if (json_last_error() != JSON_ERROR_NONE) $this -> log_error('[JSON检验失败]'.json_last_error().':'.$string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * 返回格式化后的当前时间,精确到毫秒
	 * @return 年月日时分秒毫秒格式字符串
	 **/
	function timenow(){
		date_default_timezone_set('Asia/Shanghai');   //CST时区校准
		$microsec = substr(microtime(),2,6);
		$time = date('Y-m-d H:i:s',time()).':'.$microsec;
		return $time;
	}

	/**
	 * 信息级日志写入
	 * @param string $msg 信息
	 **/
	function log_info($msg){
		$msg = '[INFO '.$this -> timenow().']'.$msg.PHP_EOL;
		file_put_contents('Client.log', $msg, FILE_APPEND);
	}

	/**
	* 故障级日志写入
	* @param string $msg 信息
	**/
	function log_error($msg){
		$msg = '[ERROR '.$this -> timenow().']'.$msg.'!'.PHP_EOL;
		file_put_contents('Client.log',$msg,FILE_APPEND);
	}

	/**
	* 发送数据到CUS
	* 必须统一使用该函数处理发送,添加结束符
	* @param string $data 需要发送的数据
	**/
	function send($data){
		$data = $data . "\r\n";
		$this -> pipe -> send($data);
		$this -> log_info("[Socket通信]发送指令到CUS,内容:{$data}");
	}

	/**
	* 处理callback_handle指令
	* 该函数负责处理CUS单独返回的回调指令,配合接入函数库使用
	* @param array $task 解码后的json指令
	**/
	function callback_handle($task){
		if (!isset($task['data'])) {
			$this -> db -> exec("INSERT INTO task_callback (username,result) VALUES ('{$task['username']}','{$task['result']}')");
		}else{
			//若data为数组,则要将其转为json格式存储
			if (is_array($task['data'])) $data = json_encode($task['data']); else $data = $task['data'];
			$this -> db -> exec("INSERT INTO task_callback(username,result,data) VALUES ('{$task['username']}','{$task['result']}','{$data}')");
		}
	}

	/**
	* 处理token_add指令
	* @param array $task 解码后的json指令
	**/
	function token_add($task){
		$json_options = json_encode($task['options']);
		$this -> db -> exec("INSERT INTO user (token,username,nickname,credit,create_time,update_time,last_login,options) VALUES ('{$task['cur_token']}','{$task['username']}','{$task['nickname']}','{$task['credit']}','{$task['create_time']}','{$task['update_time']}','{$task['last_login']}','{$json_options}')");
	}

	/**
	* 处理token_overwrite指令
	* @param array $task 解码后的json指令
	**/
	function token_overwrite($task){
		$this -> db -> exec("UPDATE user SET token = '{$task['cur_token']}' WHERE token = '{$task['ex_token']}'");
	}

	/**
	* 处理token_delete指令
	* @param array $task 解码后的json指令
	**/
	function token_delete($task){
		$this -> db -> exec("DELETE  FROM user WHERE username = '{$task['username']}'");
	}

	/**
	* 用户注册
	* @param array $task 解码后的json指令
	**/
	function register($task){
		if (!($this ->is_json(json_encode($task))) || !isset($task['username']) || !isset($task['nickname']) || !isset($task['password']) || !isset($task['credit']) || !isset($task['options'])){
			$task['result'] = 'register_fail';
			$task['data'] = 'syntax_error';
			$this -> callback_handle($task);
			$this -> log_error("[Polling_handler]捕捉到指令格式错误,废弃指令".print_r($task,true));
			return false;
		}
		//options可能为一个空数组,强制编码为json_object
		$data = json_encode($task,JSON_FORCE_OBJECT);
		$this -> send($data);
	}

	/**
	* 处理login请求,此处只负责简易检验及转发
	* @param array $task 解码后的json指令
	**/
	function login($task){
		if (!isset($task['username']) || !isset($task['password']) || strlen($task['password']) != 32) {
			$task['result'] = 'login_fail';
			$this -> callback_handle($task);
			$this -> log_error("[Polling_handler]捕捉到指令格式错误,废弃指令".print_r($task,true));
			return false;
		}
		$data = json_encode($task);
		$this -> send($data);
	}

	/**
	* 处理logout请求,此处只负责简易检验及转发
	* @param array $task 解码后的json指令
	**/
	function logout($task){
		if (!isset($task['username'])){
			$task['result'] = 'logout_fail';
			$this -> callback_handle($task);
			$this -> log_error("[Polling_handler]捕捉到指令格式错误,废弃指令,详情:".print_r($task,true));
			return false;
		}
		$data = json_encode($task);
		$this -> send($data);
	}

	/**
	* 处理edit_userinfo请求,提请用户信息修改请求
	* @param array $task 解码后的json指令
	**/
	function edit_userinfo($task){
		//简易检验逻辑
		$status = false;
		switch ($task['attribute']) {
			case 'nickname':
				if (!isset($task['data']['nickname']) || $task['data']['nickname'] == "") break;
				$status = true;
				break;
			case 'password':
				if ((!isset($task['data']['ex_password'])) || (!isset($task['data']['cur_password'])) || ($task['data']['cur_password'] == "") ||  ($task['data']['ex_password'] == "")) break;
				$status = true;
				break;
			case 'credit':
				if (!isset($task['data']['credit'])) break;
				$status = true;
				break;
			case 'options':
				if ((!isset($task['data']['column'])) || (!isset($task['data']['value'])) || $task['data']['column'] == "" ||  $task['data']['value'] == "") break;
				$status = true;
				break;
		}
		if ($status == false){
				$task['result'] = 'edit_userinfo_fail';
				$this -> callback_handle($task);
				$this -> log_error("[Polling_handler]捕捉到指令格式错误,废弃指令,详情:".print_r($task,true));
				return false;
			}

		$data = json_encode($task);
		$this -> send($data);
	}

	/**
	* 处理adjust_userinfo用户信息修改
	* @param array $task 解码后的json指令
	**/
	function adjust_userinfo($task){
		switch ($task['attribute']) {
			case 'nickname':
				$this -> db -> exec("UPDATE user SET nickname = '{$task['data']['nickname']}' WHERE username = '{$task['username']}'");
				break;
			case 'credit':
				$this -> db -> exec("UPDATE user SET credit = '{$task['data']['credit']}' WHERE username = '{$task['username']}'");
				break;
			case 'options':
				$dboptions = $this -> db -> query("SELECT options FROM user WHERE username = '{$task['username']}'");
				$dboptions = $dboptions -> fetchColumn();
				$dboptions = json_decode($dboptions,true);

				$dboptions[$task['data']['column']] = $task['data']['value'];
				$dboptions = json_encode($dboptions);
				$this -> db -> exec("UPDATE user SET options = '{$dboptions}' WHERE username = '{$task['username']}'");		
				break;
		}
	}

	/**
	* 用户离线情况,向CUS请求用户信息
	* @param array $task 解码后的json指令
	**/
	function get_userinfo_all($task){
		if (!isset($task['username'])){
			$task['result'] = 'get_userinfo_all_fail';
			$this -> callback_handle($task);
			$this -> log_error("[Polling_handler]捕捉到指令格式错误,废弃指令,详情:".print_r($task,true));
			return false;
		}
		$data = json_encode($task);
		$this -> send($data);
	}
}