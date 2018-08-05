# SOCSSO
### A socket based SSO solution designed for ITRClub Tech ORG
这是一款后端间基于socket通信的中心化指令集的异步实时SSO用户管理侧系统
## 特征
* 基于swoole异步通信框架
* 多线程、多进程，稳健、低占用的进程架构
* 基于socket，双向实时通信管道

## 目录&程序架构
* Central_server 为用户中心服务器CUS，管理用户核心资料和状态监听、广播实务，守护进程
* Client 为侧系统文件，可加载为函数库，以低耦合加载于旗下项目，守护进程

## 二次开发和程序员解读
### 指令集[重要]
> CUS和Client依靠于双方认同的指令集进行全生态群用户信息的更新等所有操作，任何开发者必须通读该说明

**必须仔细阅读，因为项目系统不配备指令集的检验过程，非法的指令集将可能引发不可恢复的严重错误！**

[WIKI跳转](http://dev.itrclub.com/LYJSpeedX/SOCSSO/wiki/_pages)