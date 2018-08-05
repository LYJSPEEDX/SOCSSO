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

#### 实现
指令集格式为json，使用jsonp技术在各后端间进行指令集的发送，实现用户状态在全网的统一
指令由CUS统一监听和广播，封装在socket管道内进行通信

#### 用户状态相关
##### Client客户端
###### 登陆
```
{"type":"login","username":"USERNAME","password":"PASSWORD(MD5 32位小写)"}
```
###### 登出
token为登录时CUS生成的token
```
{"type":"logout","token":"TOKEN（32位小写）"}
```
##### CUS服务端
###### 登陆
在收到客户端的指令后，CUS会进行一系列的验证，其中会广播三种结果
新登录成功：
```
{
    "type":"token_add",
    "cur_token":"CUR_TOKEN",  //CUS生成的token
    "options":{               //保存在CUS的用户个性化、非关键信息
        "isvip":false,
        "sex":"male",
        "email":"xx@socsso.com",
        "phone":"00000000000"
    }
}
```
同一用户，需要覆盖登陆：
```
{
    "type":"token_overwrite",
    "ex_token":"EX_TOKEN",    //需要覆盖的token
    "cur_token":"CUR_TOKEN",  //CUS新生成的token
    "detail":{                //保存在CUS的用户个性化、非关键信息
        "isvip":false,
        "sex":"male",
        "email":"xx@socsso.com",
        "phone":"00000000000"
    }
}
```
用户名或密码检验失败：
其中username为提交的username
```
{
    "type":"user_rejection",
    "username":"USERNAME"          //返回的是提交登陆时的username
}
```
###### 登出
在收到客户端的指令后，CUS会进行一系列的验证，其中会广播两种结果
成功登出
```

```
提交了没有登陆的token值，登出失败：
其中返回的token为提交的token
```
{"type":"logout_rejection","token":"TOKEN"}
```
