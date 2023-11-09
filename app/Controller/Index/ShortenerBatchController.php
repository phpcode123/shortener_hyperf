<?php
declare(strict_types=1);
namespace App\Controller\Index;


use Hyperf\DbConnection\Db;
use Hyperf\HttpMessage\Cookie\Cookie;


class ShortenerBatchController  extends AbstractController
{

    public function index(){

        
        if($this->request->isMethod("GET")){

            $host_data = $this->other->get_host_data();

            //首页随机验证码,验证码的核心逻辑就是客户端与服务端的md5(ua+"-"+timestamp) 进行验证,index首页使用timestamp进行伪装
            $index_timestamp = time();
            $hash_str = md5($this->request->header('user-agent')."-".$index_timestamp);
            $this->cache->set(env('REDIS_PREFIX')."index_hash_str".$hash_str, 1, 60*30);
            
            // 首页hash验证 end
            $title = $host_data[0]->index_title;
            $keywords = $host_data[0]->index_keyword;
            $description = $host_data[0]->index_description;


            $domain_url = $host_data[0]->http_prefix.$this->other->get_request_url()."/";

            $customize_data = Db::table("tp_domain")->where("is_customize",">","0")->orderBy("is_customize","desc")->get();

            $data=[
                "index_timestamp"=>$index_timestamp,
                "customize_data"=>$customize_data,
                "domain_url"=>$domain_url,
                "title"=>$title,
                "keywords"=>$keywords,
                "description"=>$description,
                "year_num"=>env('YEAR_NUM'),
            ];


            return $this->render->render("/Index/ShortenerBatch/index", $data);

        }else{

            //----------------- 读取cookies Begin ------------------------------------ 
            $cookie_name = env('REDIS_PREFIX')."_shortener_short_url";
            $cookie_value = "";

            if($this->request->hasCookie($cookie_name)){
                $cookie_value = $this->request->cookie($cookie_name);
            }
            
            //后面还有$cookie_value值和设置$cookie_value
            //-----------------  读取cookies End  ------------------------------------ 


            //验证码验证 begin
            $hash_str = $this->request->input("hash_str") ? $this->request->input("hash_str") : "";

            if(!$this->cache->has(env('REDIS_PREFIX')."index_hash_str".$hash_str) || $hash_str == ""){
                $this->other->error("Please try again.",'/', 5);
            }
            //验证码验证 end




            $batch_url = $this->request->input("batch_url");
            $check_site_id = $this->request->input("check_site_id") ? $this->request->input("check_site_id") : 1;


            if(preg_match("/Paste up to 10 long urls|One URL per line/i",$batch_url)){
                $this->other->error("URL error, Please input URL!", $_SERVER["HTTP_REFERER"], 5);
              
            }


            //get country name
            $remote_ip = $this->other->get_user_ip();
            $country = $this->other->get_country($remote_ip);


            
            //黑名单国家-----------begin
            $black_country_arr = explode("|",env('BLACK_COUNTRY',""));

            if(in_array($country,$black_country_arr)){
                $this->other->error("Server error, Please try again later.",'/',5);
            }
            //黑名单国家-----------end
            





            // if($country == "South Korea"){
            //     //记录日志
            //     $this->log($remote_ip." is_pc:{$is_pc} - {$country} - ".$url." - URL banned -- South Korea country.","error_url_u9s.log");

            //     $this->other->error("URL banned!",'/',5);
            // }


            //将字符串中\n回车键给清理掉，方便后面的替换
            preg_replace("/\r/","\n",$batch_url);
            preg_replace("/\n\n/","\n",$batch_url);
            $batch_url_array = preg_split("/\n/",$batch_url);
            //var_dump($batch_url_array);


            $batch_short_url_array = array();



            //获取自定义的site_id域名数据
            $check_site_id_data = Db::table("tp_domain")->where("itemid","=",$check_site_id)->get();


            //校验is_customize是否为0，为0则说明当前url非自定义url
            if($check_site_id_data[0]->is_customize == 0){
                $this->other->error("Customize URL error.",'/', 5);
            }



            //获取用户UA，如果长度大于254，就只截取254的长度
            $user_agent = $this->request->header('User-Agent') ? $this->request->header('User-Agent') : "none";
            $accept_language = $this->request->header('accept-language') ? substr($this->request->header('accept-language'), 0 ,100) : "none";
            if(strlen($user_agent) > 254){
                $user_agent = substr($user_agent,0,254);
            }
            //end 




            //---------------------- 防止前端用户恶意生成url 根据ua和客户端语言来判断 begin --------------------------
            //1小时内如果超过5次，就直接提示恶意使用
            $ua_al_key = env('REDIS_URL_CATCH_PREFIX')."-batch-verification-".md5($user_agent.$accept_language);
            if(!$this->cache->has($ua_al_key)){
                $this->cache->set($ua_al_key, 1 , 60*60);
            }else{
                $ua_al_key_value = $this->cache->get($ua_al_key);
                if($ua_al_key_value > 5){

                    $this->other->error("Do not use it maliciously",'/', 5);
                }else{
                    $this->cache->set($ua_al_key, $ua_al_key_value+1 , 60*60);
                }
            }

            //---------------------- 防止前端用户恶意生成url 根据ua和客户端语言来判断 end --------------------------







            //判断当前用户使用batch_shortener次数,如果超过2次，后面每间隔5分钟才能再使用一次 begin
            $batch_shortener_key = $user_agent."-bacth_shortener_times_limits";
            //echo $batch_shortener_key;
            if($this->cache->get($batch_shortener_key)){
                $batch_shortener_key_value = $this->cache->get($batch_shortener_key);
                $cache_value_array = explode(",",$batch_shortener_key_value);
                //var_dump($cache_value_array);
                $cache_times = $cache_value_array[0]; //缓存次数
                $cache_timestamp = $cache_value_array[1]; //缓存时间戳

                //根据次数匹配时间,此时间是根据次数去暂停时间
                switch($cache_times){
                    case 1:
                        $minutes_times= 1;
                        break;
                    case 2:
                        $minutes_times= 2;
                        break;
                    case 3:
                        $minutes_times= 3;
                        break;
                    case 4:
                        $minutes_times= 4;
                        break;
                    case 5:
                        $minutes_times= 5;
                        break;
                    default:
                        $minutes_times= 30;
                        
                }


                if($cache_times > 0 && (time()-$cache_timestamp) < 60 * $minutes_times){
                    
                    $this->other->error("Too fast, please try again after ".$minutes_times." minutes.",'/shortener-batch', 5);
                }else{
                    $cache_value = ($cache_times+1).",".time();
                    $this->cache->set($batch_shortener_key, $cache_value, 3600 * 12);
                }
            }else{
                $cache_value = "1,".time();
                $this->cache->set($batch_shortener_key, $cache_value, 3600 * 12);
            }
            //  判断当前用户使用batch_shortener次数,如果超过2次，后面每间隔5分钟才能再使用一次 end








            for($i=0;$i<count($batch_url_array);$i++){

                // 最大只能生成10个URL
                if($i > 10){
                    break;
                }
                
                $url = $batch_url_array[$i];


                //------------------- 移除字符串两边的空格和不可见字符串 begin-------------------------
                //会自动移除不可见的空白字符，如空格、tab、制表、回车、换行字符串等
                $url = trim($url);
                //------------------- 移除字符串两边的空格和不可见字符串 end-------------------------

        

                // //如果url长度小于3或者url中为匹配到.，就提示错误
                // if(strlen($url) < 3  || !preg_match("/\./",$url)){
                //     $url = "Error url.";
                    
                //     continue;
                // }



                //--------------------黑名单关键词判断 begin-------------------------
       
                //黑名单关键词判断
                $black_num = 0;//黑名单状态码
                $black_url_array = Db::table("tp_black_url")->where("success",0)->get();
                for($x=0;$x<count($black_url_array);$x++){
                    if(preg_match_all('#'.$black_url_array[$x]->pattern.'#i',$url)){
                        
                        $black_num = 1;
                        break;//跳出当前循环
                    }
                }


                if($black_num == 1){
                    $url = "Black URL!";
                    $batch_short_url_array["URL banned, Code num ".$i] = $url;
                    continue;
                }





                //过滤url长度，如果长度大于10000就直接报错，避免客户端向数据库中灌垃圾数据
                if(strlen($url) > 10000){
                    $url = "URL length over limit";
                    $batch_short_url_array["URL length over limit, Code num ".$i] = $url;
                    continue;
                }


                //判断是否是合法的url
                if(!$this->other->is_url($url)){
                    //将数据储存在关系数组中
                    $batch_short_url_array["Error URL, Please check your URL, Code num ".$i] = $url;
                    continue;
                }



                
                
                //获取当前url的参数
                
                $host_data = $this->other->get_host_data();

                $title = $host_data[0]->index_title;
                $keywords = $host_data[0]->index_keyword;
                $description = $host_data[0]->index_description;


                $urlMd5Hash = env('REDIS_URL_CATCH_PREFIX').md5($url);
            
                //判断当前url是否已经储存在redis中，如果已储存就直接读取数据，如果没有储存就将数据插入数据库，同时也将数据插入到redis中
                if($this->cache->has($urlMd5Hash)){
                    $url_data = Db::table("tp_shortener")->where("itemid",$this->cache->get($urlMd5Hash))->get();
                    
                    $short_url = $url_data[0]->short_url;

            

                }else{



                    //----------------------------   统计当天的short_url生成数量   Begin  ----------------------------------------------
                    //设置点击数统计key:redis_prefix-shortener-clicks-
                    $redis_key = env('REDIS_PREFIX')."_shortener_short_url_".date("Y-m-d", time());


                    //当redis数据库中存在此键值时，直接读取此键值的数据，否则就新建一个
                    if($this->cache->has($redis_key)){
                        
                        $total_value = $this->cache->get($redis_key);
                        //点击数自增1
                        $total_value  += 1;

                        $this->cache->set($redis_key, $total_value,60*60*24*env('CLICK_ANALYSIS_DAYS'));

                    }else{
                        //如果数据库中没有此键值就将此值重置为0
                        $this->cache->set($redis_key, 1, 60*60*24*env('CLICK_ANALYSIS_DAYS'));
                    }

                    //----------------------------   统计当天的short_url生成数量   End  ----------------------------------------------





                    //三层跳转
                    $short_url = "";
                    $short_url_7 = "";
                    $short_url_8 = "";

                    
                    while(true){
                        $short_url = $this->other->get_short_str();
                        $short_url_7 = $this->other->get_short_str(7);
                        $short_url_8 = $this->other->get_short_str(8);
    
    
                        //echo "short:".$short_url_7." ".$short_url_8;
                        //如果都不为空，则跳出当前循环
                        if($short_url !="" && $short_url_7 != "" && $short_url_8 !=""){
                            break;
                        }
    
                    }


                    //判断是否是PC端


                    //---------------- 判断当前是否是pc端  begin ----------------
                    if(!preg_match("/".env('MOBILE_USER_AGENT')."/i", $user_agent)){
                        $is_pc = 1;
                    }else{
                        $is_pc = 0;
                    }



                    $data = [
                        'site_id' => $check_site_id,
                        'access_url'  => $this->other->get_request_url(),
                        'short_url' => $short_url,
                        'short_url_7' => $short_url_7,
                        'short_url_8' => $short_url_8,
                        'url' => $url,
                        'short_from' => 2,  //1为首页 ，2为批量添加页面， 3为api接口
                        'remote_ip' => $remote_ip,
                        'country' => $country,
                        'timestamp' => time(),
                        'user_agent' => $user_agent,
                        'accept_language' => $accept_language,
                        'is_pc' => $is_pc
                    ];


                    
                    $insertItemid = Db::table('tp_shortener')->insertGetId($data);

                
                    //设置redis
                    if($this->cache->set($short_url, $insertItemid) && $this->cache->set($short_url_7, $insertItemid) && $this->cache->set($short_url_8, $insertItemid)){
                        Db::table("tp_shortener")->where("itemid",$insertItemid)->update(["redis_index" => 1]);
                        //将url-md5($url)储存至redis
                        $this->cache->set($urlMd5Hash, $insertItemid);

                    }
                }



                //设置cookie_value
                //将新生成的短链拼接到cookie_Str里，并且储存新的cookie值
                $cookie_value = $short_url.",".$cookie_value;
                

                //$main_domain_url = $check_site_id_data[0]['http_prefix'].$check_site_id_data[0]['domain_url']."/";
                $main_domain_url = $check_site_id_data[0]->http_prefix.$check_site_id_data[0]->domain_url."/";



                $short_url = $main_domain_url.$short_url;


                //将数据储存在关系数组中
                $batch_short_url_array[$short_url] = $url;



                //限定数量为30，当超过30就跳出当前循环
                if($i>30){
                    break;
                }
            }

            
            //将cookie值储存起来
            $cookie = new Cookie($cookie_name,$cookie_value);
            $this->response->withCookie($cookie);
    
            $return_str = "";
            foreach($batch_short_url_array as $key=>$value){
                $return_str .= $key."\n";
            }

            if(count($batch_short_url_array) > 0){
                $return_str .= "============================================\n";
            }else{
                $return_str .= "URL error, Please paste your URL!\n";
            }
            

            foreach($batch_short_url_array as $key=>$value){
                $return_str .= $key." => ".$value."\n";
            }
            
            return $return_str;
        }

        
    }

}