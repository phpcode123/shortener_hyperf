## 避免程序滥用，此项目仅为展示，部分核心代码删除。



## 简介

1、此项目为Swoole+Hyperf框架重写Shortener，控制器部分逻辑基本与TP版本的Shortener一致。

2、前台所有控制器全部重写，后台部分只是测试通了Hyperf的中间件、权限控制、分页器原理，并未全部重写控制器。

3、此项目主要用于学习Swoole+Hyperf框架，以及测试Swoole+Hyperf与ThinkPHP之间的性能差距。  
  

## 环境相关

* 配置：I5-10500(6核12线程 3.1GHz) + 16G DDR4-2666 MT/S
* 环境：Ubuntu22.04 + Nginx1.22 + MySql8.1 + PHP8.1 + Redis7.0
* 框架：Swoole+Hyperf、Thinkphp6.1 （两套程序控制器逻辑流程基本一致）


## 性能测试

* 程序VIEW视图性能测试对比
* 程序API接口性能测试对比（随机从数据库读取10条数据，测试数据样本20W条）
* 程序API接口性能测试对比，增加Redis缓存中间件（以HASH(SQL)为Key储存数据库值，测试数据样本20W条）


1、VIEW视图性能对比（只是简单的展示视图，以及简单的请求数据库）
~~~
## Swoole+HyPerf

>> ab -n 20000 -c 1000 http://192.168.0.5:82/

Server Software:        nginx
Server Hostname:        192.168.0.5
Server Port:            82

Document Path:          /
Document Length:        18893 bytes

Concurrency Level:      1000
Time taken for tests:   20.278 seconds
Complete requests:      20000
Failed requests:        0
Total transferred:      384640000 bytes
HTML transferred:       377860000 bytes
Requests per second:    986.31 [#/sec] (mean)
Time per request:       1013.879 [ms] (mean)
Time per request:       1.014 [ms] (mean, across all concurrent requests)
Transfer rate:          18524.16 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.7      0       8
Processing:   187  961 541.9   1182    2365
Waiting:       25  955 543.3   1175    2361
Total:        187  961 541.9   1182    2365

Percentage of the requests served within a certain time (ms)
  50%   1182
  66%   1260
  75%   1373
  80%   1432
  90%   1540
  95%   1614
  98%   2226
  99%   2252
 100%   2365 (longest request)

------------------------------------------------------------
## ThinkPHP

>> ab -n 20000 -c 1000 http://192.168.0.5:83/

Server Software:        nginx
Server Hostname:        192.168.0.5
Server Port:            83

Document Path:          /
Document Length:        20137 bytes

Concurrency Level:      1000
Time taken for tests:   136.849 seconds
Complete requests:      20000
Failed requests:        0
Total transferred:      408920000 bytes
HTML transferred:       402740000 bytes
Requests per second:    146.15 [#/sec] (mean)
Time per request:       6842.474 [ms] (mean)
Time per request:       6.842 [ms] (mean, across all concurrent requests)
Transfer rate:          2918.07 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.8      0       9
Processing:   204 6687 982.2   6878    7993
Waiting:       52 6684 982.2   6875    7989
Total:        204 6687 982.2   6879    7993

Percentage of the requests served within a certain time (ms)
  50%   6879
  66%   7013
  75%   7097
  80%   7151
  90%   7294
  95%   7420
  98%   7568
  99%   7644
 100%   7993 (longest request)

~~~

* 且换到网站根目录，执行命令：composer upgrade && composer update






* MYSQL:建立空数据库，恢复/file_gd/file_gd_20230924.sql文件，然后配置数据库文件
~~~

/file_gd/config/database.php, 并修改下列行(字母大写部分)
...
'database'        => env('database.database', 'YOUR_DATABASE'),
'username'        => env('database.username', 'YOUR_MYSQL_USERNAME'),
'password'        => env('database.password', 'YOUR_MYSQL_PASSWORD'),
...
~~~

* 伪静态文件目录(只做了Nginx适配)：/file_gd/public/.htaccess  内容复制宝塔配置里即可
* 后台地址：https://yoursite.com/admin.php/login/login  用户名：admin  密码：admin888 (默认用户名和密码)

## 定时清理

* 此命令程序会定时清理超过15天无人访问的文件，节约服务器磁盘，天数可自定义，详情请阅读command控制器逻辑部分：/file_gd/app/command/CleanExpiredFile.php

~~~
cd FILE_GD_PATH    //在linux终端切换到FILE_GD目录
screen -S clean_file   //screen 新建命令行窗口挂载
php think clean_file   // 执行监控程序
CTRL+A一起按，然后再按d键  //退出当前screen窗口，再次进入此窗口查看：screen -r clean_file
~~~


## 其它问题

* 如何更改后台登录账号密码？
~~~
修改网站配置文件：/file_gd/config/app.php    （修改大写字母部分即可）
    'admin_username'         => 'YOUR_ADMIN_USERNAME', //后台用户名
    'admin_password'         => 'YOUR_ADMIN_PASSWORD', //后台密码
~~~


* 如何更改后台登录地址？
~~~
1、先将/file_gd/public/admin.php admin.php文件命名为自己想要的 如：loginasadad.php
2、修改网站配置文件：/file_gd/config/app.php    （admin_path地址必须与步骤1修改的一致）如:

'admin_path'             => 'loginasadad.php',//后台入口文件，防止后台被爆破

后台地址：https://yoursite.com/loginasadad.php/login/login
~~~
  




* CPU占用率

![](/public/image/cpu.png)  

  




## 官方文档

* HyPerf: [https://hyperf.wiki/3.0/#/README](https://hyperf.wiki/3.0/#/README "Document")

* Swoole: [https://wiki.swoole.com/#/](https://wiki.swoole.com/#/ "Document")