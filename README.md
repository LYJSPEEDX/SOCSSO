# SOCSSO
### A socket based SSO solution designed for ITRClub Tech ORG

这是一款后端间基于socket通信的中心化指令集的异步实时SSO用户管理侧系统，为ITRClub技术集团开发

## 这是为ITRCLUB开发的特性版本，详情见wiki

## 功能与特征
* **统一生态群内所有系统的所有用户状态**(登陆/注销/覆盖登陆/超时登出等)
* **统一生态群内的所有用户基本资料特性**(注册/密码/昵称/电话邮箱/消费信息等)
* 基于swoole异步通信框架，双向socket通信管道
* 多线程、多进程，稳健、低占用的进程架构
* 中心化用户服务器，依托中心化且全局的检验机制，规避风险
* 低耦合子系统调用方式，**开发者只需要考虑调用函数，无需关心实现方式**，简单接入，功能强大

## 原理图
![](https://s1.ax1x.com/2018/08/11/PcVtbj.jpg)

如果你想知道原理，那你要看看WIKI  
如果你只想尽快接入，那就往下看，照做就好了  

## 目录&模块
> **SOCSSO(SSOC)包含四大模块，包括CUS、Client、接口函数库与EasiPanel[基本介绍](http://dev.itrclub.com/LYJSpeedX/SOCSSO/wiki/%5BBefore+ALL%5DSOCSSO%E5%9F%BA%E6%9C%AC%E4%BB%8B%E7%BB%8D)**


* Central_server 为用户中心服务器**CUS模块**，管理用户核心资料和生态群用户状态监听、广播等事务，只需要运行于php-cli，配置好数据库即可轻松创建CUS进程
* Client 为客户端**Client模块**文件，管理与CUS的通信和指令的处理，子系统只需要运行于php-cli并简易配置就可轻松接入SOCSSO
* Interface_libraries 为各语言的**接口函数库模块**文件，为各子系统与SSOC的直接接口，提供了大量而具有普适性的接口函数
* SOCSSO—EasiPanel 为SOCSSO控制面板**EasiPanel模块**，掌管SSOC的运行情况和用户的宏观调控

## 接入&运行必读

### 在开始之前，我希望你知道SOCSSO的原则性问题
* **[请一定在尝试使用SOCSSO前阅读我](http://dev.itrclub.com/LYJSpeedX/SOCSSO/wiki/1+%E2%98%85+%5BBefore+ALL%5DSOCSSO%E5%9F%BA%E6%9C%AC%E4%BB%8B%E7%BB%8D)**


### 必须先运行CUS中心服务器
```
1. clone
2. chmod 赋予目录权限
3. 下载数据库结构文件，导入，并在Main.php中填写必要信息
4. php Main.php 即可以守护进程方式运行，进程会自主维护
5. 日志：生成在运行目录的CUS.log内
```

### Client端的启动
```
1. clone  
2. chmod 赋予目录权限
3. 在Main.php中填写必要信息
4. php Main.php 即可以守护进程方式运行，进程会自主维护
5. 日志：生成在运行目录的Client.log内
```
**如果数据库文件不存在，Client端会自动创建**

### 加载函数库
```
1. clone  
2. 打开文件，进行配置
3. 复制该文件至相关文件夹，include/import并对其实例化即可调用
```

### 接入开发者只需要且必须熟悉Client端的函数库用法
* SOCSSO的接入开发者只需要调用函数库内的函数，即可对所有接入了SOCSSO的系统的用户数据统一操作，无需考虑实现方式  
* **详细用法，[请见WIKI](http://dev.itrclub.com/LYJSpeedX/SOCSSO/wiki/1+%E2%98%85+%5BBefore+ALL%5DSOCSSO%E5%9F%BA%E6%9C%AC%E4%BB%8B%E7%BB%8D)**

### SOCSSO_EasiPanel中心管理面板
> SOCSSO_EasiPanel将SOCSSO的使用和易用度提升到另一个里程碑境界，该面板可以为运营者提供全览SOCSSO生态群系统运行状况的新入口并处理诸多意外情况，**SOCSSO_Panel运行在CUS端**

##### 计划中功能
* CUS的运行、进程管理
* SOCSSO系统运行状态
* 用户状态的全览，宏观调控，诊断异常
* 发送特定指令，解决意外情况

##### 处理紧急情况
SOCSSO保留了一个指令，可以在紧急错误时，对全网用户登录状态token进行删除，删除冗余及异常用户状态，可以由管理面板执行

### SOCSSO系统的默认定时功能
出于生态群的稳定和安全，SOCSSO默认配备了以下定时任务，运营者可以加以配置或保持默认

##### 用户超时注销，系统共识机制

##### 定时全网token重置任务

-----


## 二次开发必读

### 后端间指令集[重要]
“SOCSSO的指令集”可以理解为**SOCSSO模块间的间接口**，SSOC模块依靠于双方认同的指令集进行所有操作，指令集包括：
* [CUS与Client用户状态增删改(登陆/注销/覆盖登陆/超时登出等)](http://dev.itrclub.com/LYJSpeedX/SOCSSO/wiki/%E6%8C%87%E4%BB%A4%E9%9B%86-%E7%94%A8%E6%88%B7%E7%8A%B6%E6%80%81%E7%9B%B8%E5%85%B3)
* [CUS与Client用户信息增删改查](http://dev.itrclub.com/LYJSpeedX/SOCSSO/wiki/%E6%8C%87%E4%BB%A4%E9%9B%86-%E7%94%A8%E6%88%B7%E4%BF%A1%E6%81%AF%E7%9B%B8%E5%85%B3) 
* [Client与接口函数库](http://dev.itrclub.com/LYJSpeedX/SOCSSO/wiki/%E6%8E%A5%E5%8F%A3%E5%87%BD%E6%95%B0%E5%BA%93%E6%8C%87%E4%BB%A4%E6%A0%BC%E5%BC%8F)
* [EasiPanel与CUS]()
