<?php
/**
 * SOCSSO PHP接口函数库
 * 该函数库不使用任何非原生实现方法,可放心使用
 * @version V1.0_Dev
 * @link http://dev.itrclub.com/LYJSpeedX/SOCSSO 接入前务必通读文档,规避风险
 * @author	Jan.F@隽
 **/

//函数库基本配置
$config=[
	//sqlite缓存数据库文件路径,必须为已创建的数据库文件,注意权限
	"sqlite_db_path" => "../Client/SSOC_CLIENT_TEMP.db",
	//轮询回调时间(ms)
	"polling_time" => "200",
	/* /default_identifier/
	该函数库的默认用户标识类型,可填入username或uid.即传参用户时,其用户的标识可为这两种类型的其中一种
	该偏好设定只与开发者习惯有关,如果在调用时想使用默认值另外的类型,传递该参数即可
	填入uid,函数执行时将另外调用get_username()获取username
	该设定为方便开发者接入而设,对系统并无影响
	*/
	"default_identifier" => "username"
];
//调试
$ssoc = new SSOC();
class SSOC{
	/**
	* 这只是一个构造函数,创建独立的sqlite连接
	**/
	function __construct(){
		global $config;
		if (!file_exists($config['sqlite_db_path'])) throw new Exception("SQLite缓存数据库不存在,检查路径及Client运行情况!");
		try{
			$this -> db = new PDO('sqlite:'.$config['sqlite_db_path']);
			$this -> db -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch(PDOException $e) {
			throw new Exception("SQLite缓存数据库打开失败:".$e->getMessage());
		}
	}

	/**
	* 用户鉴权,判断是否已登入
	* @param string $user_param username/uid类型
	* @param [string] $type=$config['default_identifier'] 用户标识类型
	* @return boolean 若已登入,返回true,反之false
	**/
	public function is_login($user_param,$type=$config['default_identifier']){

	}

	/**
	* login登入指令构造函数
	* @param string $username 唯一用户名
	* @param string $password 32位小写md5处理后的密码
	* @return boolean 登入结果,根据CUS的callback判断
	**/
	public function login($username,$password){

	}

	/**
	* logout登出指令构造函数
	* @param string $user_param username/uid类型
	* @param [string] $type=$config['default_identifier'] 用户标识类型
	* @return boolean 登出结果,根据CUS的callback判断
	**/
	public function logout($user_param,$type =$config['default_identifier']){

	}

	/**
	* 获取用户所有信息,返回多维数组
	* @param string $user_param username/uid类型
	* @param [boolean] $is_request=false 假若用户没登录(缓存数据库user表找不到数据),是否允许向CUS请求
	* @param [string] $type=$config['default_identifier'] 用户标识类型
	* @return array/boolean(false) 查询信息结果,若成功,返回一个包含全部信息的多维数组,若失败(用户没登入),返回false
	**/
	public function get_userinfo_all($user_param,$is_request=false,$type=$config['default_identifier']){

	}

	/**
	* 获取用户特定信息,返回string或array
	* @param string $user_param username/uid类型
	* @param string $data 需要特定获取的字段,有username/uid/credit/options
	* @param [boolean] $is_request=false 假若用户没登录(缓存数据库user表找不到数据),是否允许向CUS请求
	* @param [string] $type=$config['default_identifier'] 用户标识类型
	* @return string/array/boolean(false) 查询信息结果,若成功,按情况返回数据,若失败(用户没登入),返回false
	**/
	public function get_userinfo($user_param,$column,$is_request=false,$type=$config['default_identifier']){

	}

	/**
	* 修改用户信息
	* @param string $user_param username/uid类型
	* @param array $data 需要修改的信息详情
	* @link http://dev.itrclub.com/LYJSpeedX/SOCSSO $data的结构务必看文档
	* @param [string] $type=$config['default_identifier'] 用户标识类型
	* @return boolean 执行结果
	**/
	public function edit_userinfo($user_param,$data,$type = $config['default_identifier']){

	}
}