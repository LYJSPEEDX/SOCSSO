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
	"sqlite_db_path" => "SSOC_CLINT_TEMP.db",
	"polling_time" => "1000",
];
$ssoc = new SSOC();
class SSOC{
	function __construct(){
		if (!file_exists($config['sqlite_db_path'])) throw new Exception("SQLite缓存数据库不存在,检查路径及Client运行情况!");
		try{
			$this -> db = new PDO('sqlite:'.$config['sqlite_db_path']);
			$this -> db -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}catch(PDOException $e) {
			throw new Exception("SQLite缓存数据库打开失败:".$e->getMessage());
		}
	}

	public function login($username,$password){

	} 
}