<?php
/**
 * ITRSSO CUS超级类
 * @abstract 不可实例化
 * 必须具备的对象
 * @var $this -> db 已创建的pdo数据库对象
 * @var $this -> pipe swoole服务器对象
 * @author	Jan.F@隽
 **/
abstract class CUS{

	/**
	 * 判断json格式是否有错
	 * @param string $string 需要检测的字符串
	 * 
	 * @return Boolean
	 **/
	function is_json($string) {
		json_decode($string);
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
		file_put_contents('CUS.log', $msg, FILE_APPEND);
	}

	/**
	* 故障级日志写入
	* @param string $msg 信息
	**/
	function log_error($msg){
		$msg = '[ERROR '.$this -> timenow().']'.$msg.'!'.PHP_EOL;
		file_put_contents('CUS.log',$msg,FILE_APPEND);
	}

	/**
	 * 遍历所有服务器连接,广播指令
	 * @param string $data 广播的字符串
	 **/
	function send($data){
		$data = $data . "\r\n";
		$count = count($this -> pipe -> connections);
		$this -> log_info("[广播]客户端数目:{$count},内容:{$data}");
		foreach($this -> pipe -> connections as $fd){
			$this -> pipe -> send($fd, $data);
		}
	}

	/**
	 * 单独发送callback指令给特定客户端
	 * @WARNING 必须在每个可能影响用户交互的指令处理后,执行该函数,回调给客户端
	 * @param int $fd 需要发送的客户端标识符
	 * @param string $username 需要callback的用户名
	 * @param string $result 结果字符串
	 **/
	function send_callback($fd,$username,$result){
		//当$fd = 0 ,表示该任务是由人工创建的,无需发送回调
		if ($fd != 0){
			$data = json_encode(["type" => "callback_handle","username" => $username,"result" => $result]);
			$data = $data . "\r\n";
			$this -> pipe -> send($fd,$data);
			$this -> log_info("[回调]指令处理完成,发送给[$fd]客户端,内容:{$data}");
		}else{
			$this -> log_info("[回调]指令处理完成,无需回调,细节为{$username} {$result}");
		}
	}

	/**
	 * 登陆检验逻辑
	 * @param string $task 指令字符串
	 **/
	function login($task){ 
		$dbuser = $this -> db -> query("SELECT * FROM user WHERE username = '{$task['username']}'");
		$dbuser = $dbuser -> fetch(PDO::FETCH_ASSOC);
		//status 表示登陆检验状态 0-密码错误 1-覆盖token 2-正常登陆 3-初始值
		$status = 3;
		//检查用户存在&密码校对
		if ($dbuser == false || $task['password'] != $dbuser['password']) {
			$status = 0;
		}else{
				//检查是否已经登陆
				$check = $this -> db -> query("SELECT * FROM temp_token WHERE username = '{$task['username']}'");
				$check = $check -> fetch();
				//返回false表示没登录,登陆库找不到uid
				if ($check != false) {
					//返回true,用户已登录,删除就token并生成新token
					$ex_token = $check['token'];
					$cur_token = md5($this -> timenow().$dbuser['uid']);
					$status = 1;
				}else{
					//新登录,批准
					$cur_token = md5($this -> timenow().$dbuser['uid']);
					$status = 2;
				}
			}
		//根据status 生成指令广播
		switch ($status) {
			case '0':
				$result = 'login_fail';
				//$send_task = json_encode(["type" => $type,"username" => $task['username']]);
				break;
			case '1':
				$result = 'login_success';
				$this -> db -> exec("UPDATE temp_token SET token = '{$cur_token}' WHERE username = '{$dbuser['username']}'");
				$send_task = json_encode(["type" => "token_overwrite","username" => $dbuser['username'],"ex_token" => $ex_token,"cur_token" => $cur_token]);
				break;
			case '2':
				$result = 'login_success';
				$this -> db -> exec("INSERT INTO temp_token (token,username) VALUES ('{$cur_token}','{$dbuser['username']}')");
				$send_task = json_encode(["type" => "token_add","cur_token" => $cur_token,"uid" => $dbuser['uid'],"username" => $dbuser['username'],"nickname" => $dbuser['nickname'],"credit" => $dbuser['credit'],"options" => json_decode($dbuser['options'])]);
				break;	
			default:
				break;
		}

		if ($status != 0) {
			//只有检验无错误,才广播
			$this -> send($send_task);
		}
		$this -> send_callback($task['fd'],$task['username'],$result);
	}

	/**
	 * 登出检验逻辑
	 * @param string $task 指令字符串
	 **/
	function logout($task){
		//检查该用户是否已经登录
		$dbuser = $this -> db -> query("SELECT * FROM temp_token WHERE username = '{$task['username']}'");
		$dbuser = $dbuser -> fetch(PDO::FETCH_ASSOC);
		
		$status = 3;
		if ($dbuser == false) {
			//无登录
			$status = 0;    
		}else{
			$status = 1;
		}
		
		switch ($status) {
			case '0':
				$result = 'logout_fail';
				//$send_task = json_encode(["type" => $type,"username" => $task['username']]);
				break;
			case '1':
				$result = 'logout_success';
				$send_task = json_encode(["type" => "token_delete","username" => $task['username'],"token" => $dbuser['token']]);
				$this -> db -> exec("DELETE FROM temp_token WHERE username = '{$task['username']}'");
				break;
			default:				
				break;
		}

		if ($status != 0) {
			//只有检验无错误,才广播
			$this -> send($send_task);
		}
		$this -> send_callback($task['fd'],$task['username'],$result);
	}

	/**
	 * edit_userinfo修改用户信息逻辑
	 * @param string $task 指令字符串
	 **/
	function edit_userinfo($task){
		switch ($task['attribute']) {
			case 'nickname':
				$this -> db -> exec("UPDATE user SET nickname = '{$task['data']['nickname']}' WHERE username = '{$task['username']}'");
				$send_task = json_encode(['type' => 'adjust_userinfo','username' => $task['username'],'attribute' => 'nickname','data' => ["nickname" => $task['data']['nickname']]]);
				break;
			case 'password':
				$dbpwd = $this -> db -> query("SELECT password FROM user WHERE username = '{$task['username']}'");
				$dbpwd = $dbpwd -> fetchColumn();
				if ($task['data']['ex_password'] != $dbpwd) {
					$result = 'edit_userinfo_fail';
					$this -> send_callback($task['fd'],$task['username'],$result);
					return false;
				}
				$this -> db -> exec("UPDATE user SET password = '{$task['data']['cur_password']}' WHERE username = '{$task['username']}'");
				//修改password,不会广播
				break;
			case 'credit':
				$this -> db -> exec("UPDATE user SET credit = '{$task['data']['credit']}' WHERE username = '{$task['username']}'");
				$send_task = json_encode(['type' => 'adjust_userinfo','username' => $task['username'],'attribute' => 'credit','data' => ['credit' => $task['data']['credit']]]);
				break;
			case 'options':
				$dboptions = $this -> db -> query("SELECT options FROM user WHERE username = '{$task['username']}'");
				$dboptions = $dboptions -> fetchColumn();
				$dboptions = json_decode($dboptions,true);

				$dboptions[$task['data']['column']] = $task['data']['value'];
				$send_task = json_encode(['type' => 'adjust_userinfo','username' => $task['username'],'attribute' => 'options','data' => ['column' => $task['data']['column'],'value' => $task['data']['value']]]);
				$dboptions = json_encode($dboptions);
				$this -> db -> exec("UPDATE user SET options = '{$dboptions}' WHERE username = '{$task['username']}'");		
				break;
		}
		//默认都为成功
		$result = "edit_userinfo_success";
		$this -> send_callback($task['fd'],$task['username'],$result);

		//广播判断
		$checklogin = $this -> db -> query("SELECT COUNT(*) FROM temp_token WHERE username = '{$task['username']}'");
		$checklogin = $checklogin -> fetchColumn();
		if (isset($send_task) && $checklogin > 0) {
			$this -> send($send_task);
		}else{
			$this -> log_info('[Task->广播]用户信息修改指令处理完毕,但用户没有登入,不广播!结果指令内容:'.$send_task);
		}
	}
}