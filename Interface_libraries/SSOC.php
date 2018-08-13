<?php
/**
 * SOCSSO PHP接口函数库
 * 该函数库不使用任何非原生实现方法,可放心使用
 * @version V1.0_Dev
 * @link http://dev.itrclub.com/LYJSpeedX/SOCSSO 接入前务必通读文档,规避风险
 * @author	Jan.F@隽
 **/

//函数库基本配置
define("CONFIG",[
	//sqlite缓存数据库文件路径,必须为已创建的数据库文件,注意权限
	"sqlite_db_path" => "../Client/SSOC_CLIENT_TEMP.db",
	//轮询回调时间间隔(ms)
	"polling_time" => 100,
	/*最大轮询次数
	设置轮询的超时时间,其值=轮询间隔x最大次数
	例如polling_time为100,max_time为30,则超时时间为3000ms(3s)
	如果函数在超时时间内没有查询到任务的回调,则判定操作失败,返回false
	*/
	"max_time" => 30,
	/*默认用户标识类型
	该函数库的默认用户标识类型,可填入username或uid.即传参用户时,其用户的标识可为这两种类型的其中一种
	如果在调用时想使用默认值另外的类型,传递该参数即可
	填入uid,函数执行时将另外调用get_username()获取username
	该偏好设定只与开发者习惯有关,方便开发者接入而设,对系统并无影响
	*/
	"default_identifier" => "username"
]);  

//调试
$ssoc = new SSOC();
//var_dump($ssoc -> is_login('jun'));
//var_dump($ssoc -> login('jun','88d55ad283aa400af464c76d713c07ad'));
//var_dump($ssoc -> get_userinfo_all('1',true,'uid'));
//var_dump($ssoc -> get_userinfo('1','options',true,'uid'));
//var_dump($ssoc -> logout('jun'));
//var_dump($ssoc -> logout('1','uid'));
//var_dump($ssoc -> edit_userinfo('jum','nickname',["nickname" => "he"]));
var_dump($ssoc -> edit_userinfo('jun','password',["ex_password" => "88d55ad283aa400af464c76d713c07ad","cur_password" => "25d55ad283aa400af464c76d713c07ad"]));

class SSOC{
	/**
	* 这只是一个构造函数,创建独立的sqlite连接
	**/
	function __construct(){
		if (!file_exists(CONFIG['sqlite_db_path'])) throw new Exception("SQLite缓存数据库不存在,检查路径及Client运行情况!");
		try{
			$this -> db = new PDO('sqlite:'.CONFIG['sqlite_db_path']);
			$this -> db -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch(PDOException $e) {
			throw new Exception("SQLite缓存数据库打开失败:".$e->getMessage());
		}
		if ((CONFIG['default_identifier'] != 'username') &&( CONFIG['default_identifier'] != 'uid')) throw new Exception("默认标识符错误!");
	}

	/**
	* 用户鉴权,判断是否已登入
	* @param string $user_param username/uid类型
	* @param [string] $type=CONFIG['default_identifier'] 用户标识类型
	* @return boolean 若已登入,返回true,反之false
	**/
	public function is_login($user_param,$type=CONFIG['default_identifier']){
		$res = $this -> db -> query("SELECT COUNT(*) FROM user WHERE {$type} = '{$user_param}'");
		$res = $res -> fetchColumn();
		if ($res > 0) {
			return true;
		}else{
			return false;
		}
	}

	/**
	* login登入指令构造函数
	* @param string $username 唯一用户名
	* @param string $password 32位小写md5处理后的密码
	* @return boolean 登入结果,根据CUS的callback判断
	**/
	public function login($username,$password){
		$task = json_encode(["type" => "login","username" => $username,"password" => $password]);
		$this -> db -> exec("INSERT INTO task_queue (task) VALUES ('{$task}')");
		//轮询流程
		//获取最后callback的id
		$ex_id = $this -> db -> query("SELECT value FROM sys WHERE variables = 'last_callback_id'");
		$ex_id = $ex_id -> fetchColumn();
		//准备进入循环
		$time = 0;
		$finish = false;
		while (!$finish){
			//超时失败
			if ($time >= CONFIG['max_time']) {
				return false;
			}
			//寻找callback指令
			$res = $this -> db -> query("SELECT * FROM task_callback WHERE id > '{$ex_id}' AND username = '{$username}' AND SUBSTR(result,1,5) = 'login'");
			$res = $res -> fetch(PDO::FETCH_ASSOC);
			//找到目标callback,分析result
			if ($res != false) {
				$this -> db -> exec("UPDATE sys SET value = '{$res['id']}' WHERE variables = 'last_callback_id'");
				if ($res['result'] == 'login_success'){
					return true;
				}else{
					return false;
				}
			}
			//轮询次数+1
			$time ++;
			//循环延时
			usleep((CONFIG['polling_time'] * 1000));
		}
	}

	/**
	* logout登出指令构造函数
	* @param string $user_param username/uid类型
	* @param [string] $type=CONFIG['default_identifier'] 用户标识类型
	* @return boolean 登出结果,根据CUS的callback判断
	**/
	public function logout($user_param,$type =CONFIG['default_identifier']){
		//username的处理
		if ($type == 'uid'){
			$username = $this -> get_userinfo($user_param,'username',true,'uid');
		}else{
			$username = $user_param;
		}
		$task = json_encode(["type" => "logout","username" => "{$username}"]);
		$this -> db -> exec("INSERT INTO task_queue (task) VALUES ('{$task}')");
		//开始轮询
		$ex_id = $this -> db -> query("SELECT value FROM sys WHERE variables = 'last_callback_id'");
		$ex_id = $ex_id -> fetchColumn();
		//准备进入循环
		$time = 0;
		$finish = false;
		while (!$finish){
			//超时失败
			if ($time >= CONFIG['max_time']) {
				return false;
			}
			//寻找callback指令
			$res = $this -> db -> query("SELECT * FROM task_callback WHERE id > '{$ex_id}' AND username = '{$username}' AND SUBSTR(result,1,6) = 'logout'");
			$res = $res -> fetch(PDO::FETCH_ASSOC);
			//找到目标callback,分析result
			if ($res != false) {
				$this -> db -> exec("UPDATE sys SET value = '{$res['id']}' WHERE variables = 'last_callback_id'");
				if ($res['result'] == 'logout_success'){
					return true;
				}else{							
					return false;
				}
			}
			//轮询次数+1
			$time ++;
			//循环延时
			usleep((CONFIG['polling_time'] * 1000));
		}
	}

	/**
	* 获取用户所有信息,返回多维数组
	* @param string $user_param username/uid类型
	* @param [boolean] $is_request=true 假若用户没登录(缓存数据库user表找不到数据),是否允许向CUS请求
	* @param [string] $type=CONFIG['default_identifier'] 用户标识类型
	* @return array/boolean(false) 查询信息结果,若成功,返回一个包含全部信息的多维数组,若失败(用户没登入且设置了不请求CUS),返回false
	**/
	public function get_userinfo_all($user_param,$is_request=true,$type=CONFIG['default_identifier']){
		$dbuser = $this -> db -> query("SELECT * FROM user WHERE {$type} = '{$user_param}'");
		$dbuser = $dbuser -> fetch(PDO::FETCH_ASSOC);
		//结果不为false,表示数据库已找到用户数据
		if ($dbuser != false) {
			$dbuser['options'] = json_decode($dbuser['options'],true);
			return $dbuser;
		}else{
			//如果为false,则为数据库找不到数据
			if ($is_request == false){
				//不允许向CUS请求,返回false;或者标识符为
				return false;
			}else{
				//允许向CUS请求
				$task = json_encode(["type" => "get_userinfo_all","{$type}" => "{$user_param}"]);
				$this -> db -> exec("INSERT INTO task_queue (task) VALUES ('{$task}')");
				//轮询流程
				//获取最后callback的id
				$ex_id = $this -> db -> query("SELECT value FROM sys WHERE variables = 'last_callback_id'");
				$ex_id = $ex_id -> fetchColumn();
				//准备进入循环
				$time = 0;
				$finish = false;
				while (!$finish){
					//超时失败
					if ($time >= CONFIG['max_time']) {
						return false;
					}
					//寻找callback指令
					$res = $this -> db -> query("SELECT * FROM task_callback WHERE id > '{$ex_id}' AND username = '{$user_param}' AND SUBSTR(result,1,16) = 'get_userinfo_all'");
					$res = $res -> fetch(PDO::FETCH_ASSOC);
					//找到目标callback,分析result
					if ($res != false) {
						$this -> db -> exec("UPDATE sys SET value = '{$res['id']}' WHERE variables = 'last_callback_id'");
						if ($res['result'] == 'get_userinfo_all_success'){
							return json_decode($res['data'],true);
						}else{							
							return false;
						}
					}
					//轮询次数+1
					$time ++;
					//循环延时
					usleep((CONFIG['polling_time'] * 1000));
				}
			}
		}
	}

	/**
	* 获取用户特定信息,返回string或array
	* 该函数只是get_userinfo_all的格式化函数
	* @param string $user_param username/uid类型
	* @param string $data 需要特定获取的字段,有username/uid/credit/options
	* @param [boolean] $is_request=true 假若用户没登录(缓存数据库user表找不到数据),是否允许向CUS请求,默认向CUS请求
	* @param [string] $type=CONFIG['default_identifier'] 用户标识类型
	* @return string/array/boolean(false) 查询信息结果,若成功,按情况返回数据,若失败(用户没登入),返回false
	**/
	public function get_userinfo($user_param,$column,$is_request=true,$type=CONFIG['default_identifier']){
		$res = $this -> get_userinfo_all($user_param,$is_request,$type);
		if ($res !=false){
			return $res[$column];
		}else{
			return false;
		}
	}

	/**
	* 修改用户信息
	* @param string $user_param username/uid类型
	* @param string $column 需要修改的用户属性(nickname,password,cerdit,options)
	* @param array $data 需要修改的信息详情
	* @link http://dev.itrclub.com/LYJSpeedX/SOCSSO $data的结构务必看文档
	* @param [string] $type=CONFIG['default_identifier'] 用户标识类型
	* @return boolean 执行结果
	**/
	public function edit_userinfo($user_param,$column,$data,$type = CONFIG['default_identifier']){
		//username的处理
		if ($type == 'uid'){
			$username = $this -> get_userinfo($user_param,'username',true,'uid');
		}else{
			$username = $user_param;
		}

		switch ($column) {
			case 'nickname':
				$task = json_encode(['type' => 'edit_userinfo','username' => $username,'attribute' => 'nickname','data' => ["nickname" => $data['nickname']]]);
				break;
			case 'password':
				$task = json_encode(['type' => 'edit_userinfo','username' => $username,'attribute' => 'password','data' => ["ex_password" => $data['ex_password'],"cur_password" => $data['cur_password']]]);
				break;
			case 'credit':
				$task = json_encode(['type' => 'edit_userinfo','username' => $username,'attribute' => 'credit','data' => ['credit' => $data['credit']]]);
				break;
			case 'options':
				$task = json_encode(['type' => 'edit_userinfo','username' => $username,'attribute' => 'options','data' => ['column' => $data['column'],'value' => $data['value']]]);
				break;
			default:
				throw new Exception('修改用户信息[字段]参数错误1');
				break;
		}

		$this -> db -> exec("INSERT INTO task_queue (task) VALUES ('{$task}')");
		//轮询流程
		//获取最后callback的id
		$ex_id = $this -> db -> query("SELECT value FROM sys WHERE variables = 'last_callback_id'");
		$ex_id = $ex_id -> fetchColumn();
		//准备进入循环
		$time = 0;
		$finish = false;
		while (!$finish){
			//超时失败
			if ($time >= CONFIG['max_time']) {
				return false;
			}
			//寻找callback指令
			$res = $this -> db -> query("SELECT * FROM task_callback WHERE id > '{$ex_id}' AND username = '{$username}' AND SUBSTR(result,1,13) = 'edit_userinfo'");
			$res = $res -> fetch(PDO::FETCH_ASSOC);
			//找到目标callback,分析result
			if ($res != false) {
				$this -> db -> exec("UPDATE sys SET value = '{$res['id']}' WHERE variables = 'last_callback_id'");
				if ($res['result'] == 'edit_userinfo_success'){
					return true;
				}else{
					return false;
				}
			}
			//轮询次数+1
			$time ++;
			//循环延时
			usleep((CONFIG['polling_time'] * 1000));
		}
	}
}