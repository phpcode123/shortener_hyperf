<?php
declare(strict_types=1);
namespace App\Controller\Index;


use Hyperf\DbConnection\Db;


class IndexController  extends AbstractController
{

    public function index()
    {
        
        $host_data = $this->other->get_host_data();
        
        $title = $host_data[0]->index_title;
        $keywords = $host_data[0]->index_keyword;
        $description = $host_data[0]->index_description;
        
        if(env('SERVER_UPGRADE_STATUS') == 1){
            return  env('SERVER_UPGRADE_TIPS');
            
        }

        $this->other->get_action();
       //$user_ip = $this->other->get_user_ip();
        

        $domain_url = $host_data[0]->http_prefix.$this->other->get_request_url()."/";




        //首页用户自定义url的数据读取
        $customize_data = Db::table("tp_domain")->where("is_customize",">","0")->orderBy("is_customize","desc")->get();




        //--------------------   获取总点击数统计   Begin  --------------------
        //设置点击数统计key:redis_prefix-shortener-clicks-
        $total_redis_key = env("REDIS_PREFIX")."_total_clicks";
        
        //echo $redis_key;
        //当redis数据库中存在此键值时，直接读取此键值的数据，否则就新建一个
        if($this->cache->has($total_redis_key)){
            $total_clicks_value = $this->cache->get($total_redis_key);

        }else{
            $total_clicks_value = 1;
        }
        $total_clicks = env('INDEX_CLICKS_TODAY')+$total_clicks_value;
        //--------------------   获取总的点击数统计   End  --------------------
        



        //--------------------   获取总的链接生成统计   Begin  --------------------
        $total_links_data = Db::table("tp_shortener")->orderBy("itemid","desc")->limit(1)->get();

        $total_links = env('INDEX_LINKS_TODAY')+$total_links_data[0]->itemid;
        //--------------------   获取总的链接生成统计   End  --------------------






        //--------------------   统计当天的生成数量 此只为设置首页上的漂亮数字   Begin  --------------------
        //设置点击数统计key:redis_prefix-shortener-clicks-
        $links_today_key = env('REDIS_PREFIX')."_links_today_".date("Y-m-d", time());


        //当redis数据库中存在此键值时，直接读取此键值的数据，否则就新建一个
        if($this->cache->has($links_today_key)){
            
            $links_today_value = $this->cache->get($links_today_key);

        }else{
            //如果数据库中没有此键值就将此值重置为0
            $index_links_rand_num = preg_split("#,#i",env('INDEX_LINKS_RAND_NUM'));
            $links_today_value = mt_rand((int)$index_links_rand_num[0], (int)$index_links_rand_num[1]);
            
            $this->cache->set($links_today_key, $links_today_value, 60*60*24*env('CLICK_ANALYSIS_DAYS'));
        }
        //echo $links_today_value;
        //获取当前生成的url数量，并和随机生成的值相加
        $links_today = $links_today_value + $this->cache->get(env("REDIS_PREFIX")."_shortener_short_url_".date("Y-m-d", time()));
        //--------------------   统计当天的生成数量 此只为设置首页上的漂亮数字    End  --------------------



        //读取cookie
        //----------------- 读取cookies Begin ---------------------------- 
        $cookie_name = env("REDIS_PREFIX")."_shortener_short_url";

        if($this->request->hasCookie($cookie_name)){
            $cookie_value = $this->request->cookie($cookie_name);

        }else{
            $cookie_value = 0;
        }
        
        //从数据库中读取数据
        $cookie_data = Db::table("tp_shortener")->where("short_url","in",$cookie_value)->orderBy("itemid","desc")->get();
        $cookie_data_count_num = count($cookie_data);

        //统计cookie_data中的点击数量
        $cookie_data_total_clicks = 0;
        if($cookie_data_count_num > 2){
            for($i=0; $i < $cookie_data_count_num; $i++){
                $cookie_data_total_clicks += $cookie_data[$i]['hits'];
            }
        }

        
        
        //-----------------  读取cookies End  ---------------------------- 




        //直接在首页上展示short_url是下策，最好是生成一个sitemap文件，提交给google
        // //------------------------判断是否是蜘蛛或者爬虫，如果是就直接跳转到目标站点 begin    ------------------------
        // //获取用户UA，如果长度大于254，就只截取254的长度
        // $user_agent = Request::header('User-Agent') ? Request::header('User-Agent') : "none";
        // if(strlen($user_agent) > 254){
        //     $user_agent = substr($user_agent,0,254);
        // }


        // $spider_status = $this->other->getSpiderStatus($user_agent);
        // //echo $spider_status;
        // $index_short_url_data = array();
        // if($spider_status == 1 ){
        //     $index_short_url_data = Db::table("tp_shortener")->orderBy("itemid","desc")->limit(30);
        // }
        // View::assign("index_short_url_data",$index_short_url_data);
        // View::assign("spider_status",$spider_status);
        // //------------------------判断是否是蜘蛛或者爬虫，如果是就直接跳转到目标站点 end    ------------------------



        //首页随机验证码,验证码的核心逻辑就是客户端与服务端的md5(ua+"-"+timestamp)进行验证,index首页使用timestamp进行伪装
        $index_timestamp = time();
        $hash_str = md5($this->request->header('user-agent')."-".$index_timestamp);
        $this->cache->set(env("REDIS_PREFIX")."index_hash_str".$hash_str, 1, 60*30);
        
        // 首页hash验证 end

        $domain_data = Db::table("tp_domain")->orderBy("itemid","asc")->get();

        $data = [
            "index_timestamp"=>$index_timestamp,
            "domain_data"=>$domain_data,
            "cookie_data_total_clicks"=> $cookie_data_total_clicks,
            "total_clicks"=>$total_clicks,
            "total_links"=>$total_links,
            "links_today"=>$links_today,
            "index_display_user_cookies_data"=>env('INDEX_USER_DATA'),
            "cookie_data"=>$cookie_data,
            "cookie_data_count_num"=>$cookie_data_count_num,
            "domain_url"=>$domain_url,
            "title"=>$title,
            "keywords"=>$keywords,
            "description"=>$description,
            "year_num"=>env('YEAR_NUM'),
            "customize_data"=>$customize_data,
        ];

        if($host_data[0]->itemid == 14 || $host_data[0]->itemid == 7){
            return $this->render->render("Index/Index/new_domain",$data);
        }else{
            return $this->render->render("Index/Index/index",$data);
        }
        
    }







    public function shorten(){
        $url = $this->request->query("url");
        $check_site_id = $this->request->query("check_site_id") ? $this->request->query("check_site_id") : 1;

        $check_site_id_data = Db::table("tp_domain")->where("itemid","=",$check_site_id)->get();



        if(strlen($url) > 10000){
            $json_data_array = array(
                "error" => 1,
                "msg" => "Error,Url length exceeds limit !"
            ); 
    
            return json_encode($json_data_array);
        }

        $remote_ip = $_SERVER['REMOTE_ADDR'];
        

        $host_data = $this->other->get_host_data();
        $site_id = $host_data[0]->itemid;


        $urlMd5Hash =env('REDIS_URL_CATCH_PREFIX').md5($url);
       
        
        if($this->cache->has($urlMd5Hash)){
            $url_data = Db::table("tp_shortener")->where("itemid",$this->cache->get($urlMd5Hash))->get();
            
            $short_url = $url_data[0]->short_url;

            
            $error_num = 0;

        }else{

            $short_url = $this->other->get_short_str();

            $data = [
                'site_id' => $site_id,
                'short_url' => $short_url,
                'url' => $url,
                'remote_ip' => $remote_ip,
                'timestamp' => time()
            ];

            $insertItemid = Db::table('tp_shortener')->insertGetId($data);

        
            //设置redis
            if($this->cache->set($short_url, $insertItemid)){
                Db::table("tp_shortener")->where("itemid",$insertItemid)->update(["redis_index" => 1]);

                //将url-md5($url)储存至redis
                $this->cache->set($urlMd5Hash, $insertItemid);
                $error_num = 0;

            }else{
                $error_num = 1;    
            }
        }

        $main_domain_url = $check_site_id_data[0]['http_prefix'].$check_site_id_data[0]['domain_url']."/";


        $json_data_array = array(
            "error" => $error_num,
            "short" => $main_domain_url.$short_url
        ); 

        return json_encode($json_data_array);
    }


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


}
