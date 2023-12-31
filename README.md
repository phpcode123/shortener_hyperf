
## 简介

1、此项目为Hyperf+Swoole框架重写Shortener，控制器部分逻辑基本与TP版本的Shortener一致。

2、前台控制器全部重写，后台部分只是测试Hyperf的中间件、权限控制、分页器原理，并未全部重写。

3、测试Hyperf+Swoole、ThinkPHP与ThinkPHP+Swoole之间的性能差距。  
  

## 环境相关

* 配置：I5-10500(6核12线程 3.1GHz) + 16G DDR4(2666 MT/S)
* 环境：Ubuntu22.04 + Nginx1.22 + MySQL8.1 + PHP8.1 + Redis7.0
* 框架：Hyperf+Swoole、ThinkPHP6.1 、ThinkPHP6.1+Swoole（两套程序控制器逻辑流程基本一致）

## 结论

* View视图性能：Hyperf+Swoole(986.31RPS)\ThinkPHP(146.15RPS)\ThinkPHP+Swoole(1827.32QPS)
* API接口性能：Hyperf+Swoole(3537.38RPS)\ThinkPHP(173.55RPS)\ThinkPHP+Swoole(3527.20RPS)



## 性能测试

* 程序VIEW视图性能测试对比  (模板引擎均为ThinkTemplate)
* 程序API接口性能测试对比（随机从数据库读取20条数据，数据样本20W）



### VIEW视图性能对比（只是简单的展示视图，以及简单的请求数据库）
~~~
## Hyperf+Swoole

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

## CPU占用在70%左右浮动
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

## CPU占用几乎100%
--------------------------------------------------------------
## Thinkphp+Swoole

> ab -n 20000 -c 1000 http://192.168.0.5:8088/

Server Software:        swoole-http-server
Server Hostname:        192.168.0.5
Server Port:            8088

Document Path:          /
Document Length:        20164 bytes

Concurrency Level:      1000
Time taken for tests:   10.945 seconds
Complete requests:      20000
Failed requests:        0
Total transferred:      409820000 bytes
HTML transferred:       403280000 bytes
Requests per second:    1827.32 [#/sec] (mean)
Time per request:       547.249 [ms] (mean)
Time per request:       0.547 [ms] (mean, across all concurrent requests)
Transfer rate:          36566.04 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.9      0       8
Processing:    12  522 328.4    468    1568
Waiting:        8  519 328.9    464    1564
Total:         12  523 328.4    468    1568

Percentage of the requests served within a certain time (ms)
  50%    468
  66%    585
  75%    672
  80%    783
  90%    972
  95%   1172
  98%   1445
  99%   1495
 100%   1568 (longest request)


## CPU占用在70%-80%左右浮动
~~~

### API接口性能测试
1、数库表结构  
2、API部分代码  
3、性能测试结果  

#### 数据表结构
~~~

mysql> desc tp_shortener;
+------------------------+------------------+------+-----+---------+----------------+
| Field                  | Type             | Null | Key | Default | Extra          |
+------------------------+------------------+------+-----+---------+----------------+
| itemid                 | bigint unsigned  | NO   | PRI | NULL    | auto_increment |
| user_name              | varchar(255)     | NO   |     |         |                |
| site_id                | int unsigned     | NO   |     | 0       |                |
| access_url             | varchar(255)     | NO   |     |         |                |
| short_url              | varchar(100)     | NO   | MUL |         |                |
| short_url_7            | varchar(100)     | NO   |     |         |                |
| short_url_8            | varchar(100)     | NO   |     |         |                |
| url                    | varchar(10000)   | NO   |     |         |                |
| short_from             | tinyint unsigned | NO   |     | 0       |                |
| hits                   | bigint unsigned  | NO   | MUL | 0       |                |
| remote_ip              | varchar(100)     | NO   |     |         |                |
| country                | varchar(255)     | NO   |     |         |                |
| timestamp              | int unsigned     | NO   |     | 0       |                |
| last_access_timestamp  | int unsigned     | NO   |     | 0       |                |
| middle_page            | tinyint unsigned | NO   |     | 1       |                |
| is_pc                  | tinyint unsigned | NO   |     | 0       |                |
| user_agent             | varchar(255)     | NO   |     |         |                |
| accept_language        | varchar(255)     | NO   |     |         |                |
| allow_spider_jump      | tinyint unsigned | NO   |     | 1       |                |
| is_404                 | tinyint unsigned | NO   |     | 0       |                |
| display_ad             | tinyint unsigned | NO   |     | 1       |                |
| status                 | tinyint unsigned | NO   |     | 1       |                |
| redis_index            | tinyint unsigned | NO   | MUL | 0       |                |
| check_malicious_status | tinyint unsigned | NO   | MUL | 0       |                |
| youtube_url_itemid     | int unsigned     | NO   |     | 0       |                |
+------------------------+------------------+------+-----+---------+----------------+
25 rows in set (0.00 sec)
~~~

#### API部分代码
~~~
## Hyperf API \App\Controller\Index\IndexController\api

public function api(){
    $itemid_ = [];
    while(count($itemid_)<20){
        $rand_num = mt_rand(1,200000);
        if(!in_array($rand_num, $itemid_)){
            array_push($itemid_, $rand_num);
        }
    }

    $data = Db::table("tp_shortener")->whereIn("itemid",$itemid_)->get();
    return $data;
}
-----------------------------------
## ThinkPHP API \app\index\controller\index\api


public function api(){
  $itemid_ = [];
  while(count($itemid_)<20){
      $rand_num = mt_rand(1,200000);
      if(!in_array($rand_num, $itemid_)){
          array_push($itemid_, $rand_num);
      }
  }
  $itemid_str = join(",",$itemid_);

  $data = Db::table("tp_shortener")->where("itemid","in",$itemid_str)->select();
  return $data;
}
~~~

#### API性能测试结果
~~~
## Hyperf+Swoole
>>  ab -n 20000 -c 1000 http://192.168.0.5:82/api

Server Software:        nginx
Server Hostname:        192.168.0.5
Server Port:            82

Document Path:          /api
Document Length:        15182 bytes

Concurrency Level:      1000
Time taken for tests:   5.654 seconds
Complete requests:      20000
Failed requests:        19988
   (Connect: 0, Receive: 0, Length: 19988, Exceptions: 0)
Total transferred:      299939961 bytes
HTML transferred:       293479961 bytes
Requests per second:    3537.38 [#/sec] (mean)
Time per request:       282.695 [ms] (mean)
Time per request:       0.283 [ms] (mean, across all concurrent requests)
Transfer rate:          51806.74 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.4      0       3
Processing:    28  275  37.7    275     487
Waiting:       19  141  45.4    140     320
Total:         28  275  37.7    276     487

Percentage of the requests served within a certain time (ms)
  50%    276
  66%    280
  75%    284
  80%    286
  90%    295
  95%    318
  98%    351
  99%    370
 100%    487 (longest request)

## CPU占用70%左右
---------------------------------------
## ThinkPHP 
>>  ab -n 20000 -c 1000 http://192.168.0.5:83/api

Server Software:        nginx
Server Hostname:        192.168.0.5
Server Port:            83

Document Path:          /api
Document Length:        15110 bytes

Concurrency Level:      1000
Time taken for tests:   115.241 seconds
Complete requests:      20000
Failed requests:        19991
   (Connect: 0, Receive: 0, Length: 19991, Exceptions: 0)
Total transferred:      299282079 bytes
HTML transferred:       293102079 bytes
Requests per second:    173.55 [#/sec] (mean)
Time per request:       5762.045 [ms] (mean)
Time per request:       5.762 [ms] (mean, across all concurrent requests)
Transfer rate:          2536.15 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.7      0       4
Processing:   178 5632 803.8   5817    6688
Waiting:       45 5631 803.9   5815    6688
Total:        178 5632 803.8   5817    6688

Percentage of the requests served within a certain time (ms)
  50%   5817
  66%   5920
  75%   5978
  80%   6015
  90%   6108
  95%   6184
  98%   6266
  99%   6332
 100%   6688 (longest request)

## CPU占用几乎100%

---------------------------------------
## ThinkPHP+Swoole
> ab -n 20000 -c 1000 http://192.168.0.5:8088/index.php/index/api

Benchmarking 192.168.0.5 (be patient)
Completed 2000 requests
Completed 6000 requests
Completed 8000 requests
Completed 10000 requests
Completed 12000 requests
Completed 14000 requests
Completed 16000 requests
Completed 18000 requests
Completed 20000 requests
Finished 20000 requests


Server Software:        swoole-http-server
Server Hostname:        192.168.0.5
Server Port:            8088

Document Path:          /index.php/index/api
Document Length:        14520 bytes

Concurrency Level:      1000
Time taken for tests:   5.670 seconds
Complete requests:      20000
Failed requests:        19989
   (Connect: 0, Receive: 0, Length: 19989, Exceptions: 0)
Total transferred:      300129254 bytes
HTML transferred:       293589254 bytes
Requests per second:    3527.20 [#/sec] (mean)
Time per request:       283.511 [ms] (mean)
Time per request:       0.284 [ms] (mean, across all concurrent requests)
Transfer rate:          51690.17 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.9      0       4
Processing:    24  276  38.5    277     525
Waiting:       12  139  50.5    140     408
Total:         24  276  38.5    277     525


## CPU占用70%-80%左右
~~~

## 结论

* View视图性能：Hyperf+Swoole(986.31RPS)\ThinkPHP(146.15RPS)\ThinkPHP+Swoole(1827.32QPS)
* API接口性能：Hyperf+Swoole(3537.38RPS)\ThinkPHP(173.55RPS)\ThinkPHP+Swoole(3527.20RPS)

基于Swoole扩展下，框架的性能差距不大。专注业务逻辑，勿要总是关注框架或技术，用ThinkPHP即可。

  
  
  

## 官方文档

* Hyperf: [https://hyperf.wiki/3.0/#/README](https://hyperf.wiki/3.0/#/README "Document")

* Swoole: [https://wiki.swoole.com/#/](https://wiki.swoole.com/#/ "Document")