<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\HttpServer\Router\Router;


#Index Controller 注意有顺序
Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\Index\IndexController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/api', 'App\Controller\Index\IndexController@api');
Router::addRoute(['GET', 'POST', 'HEAD'], '/shortener', 'App\Controller\Index\ShortenerController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/shortener-api', 'App\Controller\Index\ShortenerApiController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/shortener-batch', 'App\Controller\Index\ShortenerBatchController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/url-click-counter', 'App\Controller\Index\ClicksController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/url-total-clicks', 'App\Controller\Index\ClicksController@total');
Router::addRoute(['GET', 'POST', 'HEAD'], '/url-total-clicks-batch', 'App\Controller\Index\ClicksBatchController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/report-malicious-url', 'App\Controller\Index\ReportMaliciousController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/contact', 'App\Controller\Index\ContactController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/error-page', 'App\Controller\Index\ErrorPageController@index');
Router::addRoute(['GET', 'POST', 'HEAD'], '/ads.txt', 'App\Controller\Index\AdstxtController@index');
#Goto
Router::addRoute(['GET', 'POST', 'HEAD'], '/{short_str:\w+}', 'App\Controller\Index\GotoController@index');







#Admin Controller 后台登录地址入口请在根目录下的.env配置文件中修改
Router::addRoute(['GET','POST','HEAD'], '/'.env('ADMIN_PATH').'/login/login', 'App\Controller\Admin\LoginController@index');
//Router::addRoute(['GET','POST','HEAD'], '/'.env('ADMIN_PATH').'/login/login', 'App\Controller\Admin\LoginController@index');
Router::addGroup('/'.env('ADMIN_PATH')."/",function(){
    Router::get("index",'App\Controller\Admin\AnalysisController@list');
    Router::get("logout",'App\Controller\Admin\LoginController@logout');
},
[
    'middleware' => [
        \App\Middleware\Auth\CheckLoginMiddleware::class,
    ]
]

);




