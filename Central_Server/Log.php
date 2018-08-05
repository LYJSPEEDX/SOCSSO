<?php
/**
 * ITRSSO 日志通用库
 * 
 * @author	Jan.F@隽
 **/
class Log{

	function __construct(){
		$this -> log = fopen('CUS.log','a');
		$this -> log_info('Log_CUS 日志库成功');
	}

	function timenow(){
		date_default_timezone_set('Asia/Shanghai');   //CST时区校准
		$microsec = substr(microtime(),2,6);
		$time = date('Y-m-d H:i:s',time()).':'.$microsec;
		return $time;
	} 

	function log_info($msg){
		$msg = '[INFO '.$this -> timenow().']'.$msg.PHP_EOL;
		fwrite($this -> log, $msg);
	}

	function log_error($msg){
		$msg = '[ERROR '.$this -> timenow().']'.$msg.'!'.PHP_EOL;
		fwrite($this -> log, $msg);
	}
}