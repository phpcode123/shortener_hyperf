<?php
declare(strict_types=1);
namespace App\Service;

use ArrayAccess;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use Hyperf\View\RenderInterface;
use GeoIp2\Database\Reader;
use Hyperf\DbConnection\Db;
use Hyperf\Cache\Cache;
use Hyperf\HttpServer\Router\Dispatched;




class OtherService
{

    #[Inject]
    protected Cache $cache;

    #[Inject]
    protected RequestInterface $request;

    #[Inject]
    protected ResponseInterface $response;

    #[Inject]
    protected RenderInterface $render;


    //返回当前控制器的名称
    /**
     * 
     *    $callback string：App\Controller\Index\IndexController@index
     * 
     *    $name_ result
     *    array(2) {
     *        [0]=>
     *        string(15) "IndexController"
     *        [1]=>
     *        string(5) "Index"
     *    }
     */
    public function get_action(int $full_name=0, bool $convert = false): ?string
    {

        $dispatch = $this->request->getAttribute(Dispatched::class);
        $callback = $dispatch?->handler?->callback;
        
        if(is_array($callback) && count($callback) === 2){
            return $callback[1];
        }

        
        if(is_string($callback)){
            preg_match("/(\w+)Controller/i",$callback,$name_);
            if($full_name == 1){
                $name = $name_[0];
            }else{
                $name = $name_[1];
            }
            return $convert ? strtolower($name) : $name;
        }
        // $httpMethod = $this->request->getMethod();
        // $uri = (string)$this->request->getUri();

        // $mark_base = new MarkBased([[$httpMethod][$uri]],[$httpMethod]);
        // $dispatch = new Dispatched($mark_base->dispatch($httpMethod,$uri));
 
        return null;
    }



    //返回随机字符串
    public  function get_short_str(int $length=6): String
    {

        //获取框架容器，并将\Hyperf\Cache\Driver\RedisDriver注入容器，然后使用redis客户端

        if($length != 6){
            $str_length = $length;
        }else{
            $str_length = (int)env('SHORT_STR_LENGTH');
        }

        $short_str = "";
        
        //随机生成32-50位长度的字符串，然后从0-6开始截取字符串去数据库中查询，如果能匹配到则自动增加1，直到匹配不到数据为止。
        //随机字符串不要太长，会占用cpu性能
        $num = mt_rand(32,32);

        $characters = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $rand_str = ''; 
        for ($i = 0; $i < $num; $i++) { 
            $index = mt_rand(0, strlen($characters) - 1); 
            $rand_str .= $characters[$index]; 
        }

        $timestamp = time();
        //将字符串使用base64加密, 替换掉base64加密后面可能产生的==号
        $rand_str = base64_encode($rand_str.$timestamp);
        $rand_str = preg_replace("#=#i", "", $rand_str);


        $start_num = 0;
        while(true){
            //截取指定长度的字符串
            $short_str = substr($rand_str, $start_num, $str_length);
            $redis_key = env('REDIS_PREFIX').$short_str;

            //如果在redis数据库中未匹配到当前字符串，就说明当前字符串未被使用过，将数据储存在redis中，并且退出当前循环  
            if(!$this->cache->has($redis_key)){
                $this->cache->set($redis_key, 1, 43200); //60*60*12 设置12小时过期，避免生成大量的数据造成消耗redis资源
                break;
            }

            $start_num += 1;
            //当当前循环超过指定次数时会导致$start_num超过一个长度值
            if($start_num > (strlen($rand_str) - $str_length-1)){
                
                //------------    如果所有的数据都匹配完了还是没有匹配到short_str，就将长度+1   begin ------------

                //截取指定长度的字符串
                $short_str = substr($rand_str, $start_num, $str_length+1);
                $redis_key = env('REDIS_PREFIX').$short_str;

                //如果在redis数据库中未匹配到当前字符串，就说明当前字符串未被使用过，将数据储存在redis中，并且退出当前循环  
                if(!$this->cache->has($redis_key)){
                    $this->cache->set($redis_key, 1, 43200); //60*60*12 设置12小时过期，避免生成大量的数据造成消耗redis资源
                    break;
                }
                
                //------------    如果所有的数据都匹配完了还是没有匹配到short_str，就将长度+1   end ------------

                //如果还是超出指定长度还没有匹配到数据，就将shortUrtStr设置为指定值
                if($start_num > strlen($rand_str) * 2){

                    //截取指定长度的字符串
                    $short_str = "errorShortStr".$timestamp."-".time();
                    $redis_key = env('REDIS_PREFIX').$short_str;

                    //如果在redis数据库中未匹配到当前字符串，就说明当前字符串未被使用过，将数据储存在redis中，并且退出当前循环  
                    if(!$this->cache->has($redis_key)){
                        $this->cache->set($redis_key, 1, 43200); //60*60*12 设置12小时过期，避免生成大量的数据造成消耗redis资源
                        break;
                    }
                }

            }
        }

        return $short_str; 
    }




    
    public function url_md5_hash(String $url): string
    {
        $url_md5_hash = env('REDIS_URL_CATCH_PREFIX').md5($url);
        return $url_md5_hash;
    }
    


    public function get_host_data(String $request_url=null): ArrayAccess
    {
        if(!isset($request_url)){
           $request_url = $this->get_request_url();
        }


        $host_data = Db::table("tp_domain")->where("domain_url",$request_url)->orderBy("itemid","asc")->limit(1)->get();

        //让所有的url都可以有数据
        if(count($host_data) == 0){
            $host_data = Db::table("tp_domain")->where("itemid","1")->orderBy("itemid","asc")->limit(1)->get();
        }

        return $host_data;
    }



    public function get_request_url(){
        $request_url = $this->request->url();

        #返回的url会带http://或https://, 替换掉http://和https://，只保留纯域名
        $request_url = preg_replace("#(https|http)://#i","",$request_url);
        return $request_url;
    }

    public function get_adsense_host_data(string $request_url): ArrayAccess
    {

        $host_data = Db::table("tp_adsense")->where("adsense_domain",$request_url)->orderBy("itemid","asc")->limit(1)->get();

        //var_dump($host_data);
        //让所有的url都可以有数据
        if(count($host_data) == 0){
            $this->error("host data length less than 1","/",3);
        }

        return $host_data;
    }


    //返回是否是蜘蛛 1 true ; 0 false
    public  function getSpiderStatus(string $user_agent){
        if(preg_match("#".env('SPIDER_USER_AGENT')."#i", $user_agent)){
            $spider_status = 1;
        }else{
            $spider_status = 0;
        }
        return $spider_status;
    }

    
   
    //需要设置nginx代理：https://hyperf.wiki/3.0/#/zh-cn/tutorial/nginx
    # 将客户端的 Host 和 IP 信息一并转发到对应节点   
    /**
     * proxy_set_header Host $http_host;
      *  proxy_set_header X-Real-IP $remote_addr; 注意此处如果是X-Real-IP，那么hyperf框架也必须使用$request->header("X-Real-IP")去判断，不区分大小写
      *  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
     */
    public  function get_user_ip(){
        $header_ = [
            "X_REAL_IP",
            "X-REAL-IP",
            "REMOTE_ADDR",
            "X_FORWARDED_FOR",
            "X-FORWARDED-FOR",
            "HTTP_CF_CONNECTING_IP",
            "HTTP_CLIENT_IP",
            "HTTP_X_FORWARDED",
            "HTTP_X_FORWARDED_FOR",
            "HTTP_FORWARDED_FOR",
            "HTTP_FORWARDED",
        ];

        foreach($header_ as $item){
            if($this->request->hasHeader($item)){
                return $this->request->header($item);
            }
        }
        return "null";
    }



    public function user_agent(){
        $header_ = [
            "HTTP_USER_AGENT",
            "HTTP_REFERER"
        ];

        foreach($header_ as $item){
            if($this->request->hasHeader($item)){
                return $this->request->header($item);
            }
        }
        return "null";
    }

    public function device(){
        $platform =   "Unknown OS";
        $os       =  [
                    '/windows nt 11.0/i'    =>  'Windows 11',
                    '/windows nt 10.0/i'    =>  'Windows 10',
                    '/windows nt 6.3/i'     =>  'Windows 8.1',
                    '/windows nt 6.2/i'     =>  'Windows 8',
                    '/windows nt 6.1/i'     =>  'Windows 7',
                    '/windows nt 6.0/i'     =>  'Windows Vista',
                    '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                    '/windows nt 5.1/i'     =>  'Windows XP',
                    '/windows xp/i'         =>  'Windows XP',
                    '/windows nt 5.0/i'     =>  'Windows 2000',
                    '/windows me/i'         =>  'Windows ME',
                    '/win98/i'              =>  'Windows 98',
                    '/win95/i'              =>  'Windows 95',
                    '/win16/i'              =>  'Windows 3.11',
                    '/macintosh|mac os x/i' =>  'Mac OS X',
                    '/mac_powerpc/i'        =>  'Mac OS 9',
                    '/linux/i'              =>  'Linux',
                    '/ubuntu/i'             =>  'Ubuntu',
                    '/iphone/i'             =>  'iPhone',
                    '/ipod/i'               =>  'iPod',
                    '/ipad/i'               =>  'iPad',
                    '/android/i'            =>  'Android',
                    '/blackberry/i'         =>  'BlackBerry',
                    '/bb10/i'                 =>  'BlackBerry',
                    '/cros/i'                =>    'Chrome OS',
                    '/webos/i'              =>  'Mobile'
                ];
        foreach ($os as $regex => $value) { 
            if (preg_match($regex, $this->user_agent())) {
                $platform    =   $value;
            }
        }   
        return $platform;    
    }


    public function browser() {
        $matched   =     false;
        $browser   =   "Unknown Browser";
        $browsers  =   [
                        '/safari/i'     =>  'Safari',            
                        '/firefox/i'    =>  'Firefox',
                        '/fxios/i'        =>  'Firefox',                        
                        '/msie/i'       =>  'Internet Explorer',
                        '/Trident\/7.0/i'  =>  'Internet Explorer',
                        '/chrome/i'     =>  'Chrome',
                        '/crios/i'        =>    'Chrome',
                        '/opera/i'      =>  'Opera',
                        '/opr/i'          =>  'Opera',
                        '/netscape/i'   =>  'Netscape',
                        '/maxthon/i'    =>  'Maxthon',
                        '/konqueror/i'  =>  'Konqueror',
                        '/edg/i'       =>  'Edge',
                    ];
        
        foreach ($browsers as $regex => $value) { 
            if (preg_match($regex,  $this->user_agent())) {
                $browser  =  $value;
                $matched = true;
            }
        }
        
        if(!$matched && preg_match('/mobile/i', $this->user_agent())){
            $browser = 'Mobile Browser';
        }

        return $browser;
    } 



    //返回英文国家名称
    //https://github.com/maxmind/GeoIP2-php#city-example
    public function get_country(string $ip){
        try {
            $reader =new Reader(BASE_PATH.'/storage/extends/geoip2/GeoLite2-City.mmdb');
            $record = $reader->city($ip);
            $country = $record->country->name;

            if(empty($country)){
                $country = "None";
            }
        }catch(\Exception $e){
            $country = "None";
        }


        return $country;
    }


    public function is_url(string $url){
        $url = trim($url);
        if(empty($url)) return FALSE;        

        $parsed = parse_url($url);
        
        //$protocol = $parsed['scheme'] ?? 'http://'; 
        //echo $protocol;       
        $schemes =["http", "https", "www"];
       // $schemes = explode(",", config("schemes"));

       // $schemes = array_diff($schemes, ["http", "https", "www"]);
        //dump($schemes);
        // if($protocol){
        //     if(in_array($protocol, $schemes)){
        //         return $url;
        //     }
        // }

        if(preg_match('~^([a-zA-Z0-9+!*(),;?&=$_.-]+(:[a-zA-Z0-9+!*(),;?&=$_.-]+)?@)?([a-zA-Z0-9\-\.]*)\.(([a-zA-Z]{2,4})|([0-9]{1,3}\.([0-9]{1,3})\.([0-9]{1,3})))(:[0-9]{2,5})?(/([a-zA-Z0-9+$_%-]\.?)+)*/?(\?[a-z+&\$_.-][a-zA-Z0-9;:@&%=+/$_.-]*)?(#[a-z_.-][a-zA-Z0-9+$%_.-]*)?~', $url) && !preg_match('(http://|https://)', $url)){
            $url = "http://$url";
        }
        //echo ">> url : ".$url."   ";
        if(!$this->is_url_check($url)) return false;

        if(!filter_var($url, FILTER_VALIDATE_URL)){
            $parsed = parse_url($url);
            if(!isset($parsed["scheme"]) || !$parsed["scheme"]) return false;
            if(!isset($parsed["host"]) || !$parsed["host"]) return false;
        }                    
        return $url;
    }


    //判断是否是url
    public function is_url_check($url){
        if(empty($url)) return FALSE;    

        if (preg_match("#(?i)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))#", $url)){
            return true;
        }else{
            return false;
        }
        
        
    }

    //返回随机的recommand itemid tr list，需要传入最大的itemid值和返回的字符串长度，返回值用逗号隔开
    public function roblox_recom_itemid_str($max_length){
        $arr = [];
        $max_itemid_data = Db::table("tp_youtube_url")->order("itemid","desc")->limit(1)->get();
        while(count($arr) < $max_length){
            $rand_num = mt_rand(0,$max_itemid_data[0]->itemid);
            if(!in_array($rand_num, $arr)){
                array_push($arr,$rand_num);
            }
        }

        $new_list_str = join(",",$arr);

        return $new_list_str;
    }








    /**
     * 操作成功跳转的快捷方法
     * @access public
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return string
     */
    public function success(string $msg = '', string $url = null, int $wait = 3)
    {
        $result = [
            'code' => 1,
            'msg'  => $msg,
            'url'  => $url,
            'wait' => $wait,
        ];

        $html = $this->render->getContents('tpl/jump', $result);
  
        return $this->response->html($html);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access public
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  integer   $wait 跳转等待时间
     * @return string
     */
    public function error(string $msg = '', string $url = null, int $wait = 3)
    {
        $result = [
            'code' => 0,
            'msg'  => $msg,
            'url'  => $url,
            'wait' => $wait,
        ];

        $html = $this->render->getContents('tpl/jump', $result);
        return $this->response->html($html);
    }

        /**
     * URL重定向  自带重定向无效
     * @access public
     * @param  string         $url 跳转的URL表达式
     * @param  array|integer  $params 其它URL参数
     * @param  integer        $code http code
     * @param  array          $with 隐式传参
     * @return void
     */
    public function redirect(string $url, int $code = 302)
    {
        return $this->response->redirect($url, $code);
    }

    /**
     * 日志记录
     * @param  string         $msg log 日志错误信息
     * @param  string         $file_name  要写入的日志文件名称
     */
    public function log($msg, $file_name){
        try{
            $log_file=fopen("./logs/".$file_name,"a+");
            fwrite($log_file,date("Y-m-d H:i:s")." - ".$msg."\n");
            fclose($log_file);
        }catch(\Exception $e){
            //do something...
        }
    }


    public function abort($code, string $message = '')
    {

        throw new \Hyperf\HttpMessage\Exception\HttpException($code, $message);
        
    }




}
