<?php
declare(strict_types=1);
namespace App\Controller\Index;


use Hyperf\DbConnection\Db;



class RobloxController  extends AbstractController
{

    public function show(){
        
        $short_url = $this->request->input("short_str",NULL);

        if($short_url == ""){
            $this->other->abort(404,"数据不存在");
        }


        $host_data = $this->other->get_host_data();
        $adsense_host_data = $this->other->get_adsense_host_data($this->other->get_request_url());
        

        $data = Db::table("tp_youtube_url")->where("youtube_short_url",$short_url)->get();
        

     

        //当$data的长度为0时说明在数据库中没有匹配到该数据，跳转首页
        if(count($data) == 0){
            return $this->other->error("URL ERROR!",$host_data[0]->http_prefix.$host_data[0]->domain_url,3);
        }


        $youtube_title = $data[0]->title;
        $youtube_keyword = $data[0]->keyword;
        $youtube_description = $data[0]->description;
        $youtube_url = $data[0]->youtube_url;
        $youtube_short_url = $data[0]->youtube_short_url;

        
        


        //-----------------  获取recommand推荐值 begin ---------------
        $roblox_redis_key = env('REDIS_PREFIX')."reblox_show_recom_{$short_url}";
        
        if($this->cache->get($roblox_redis_key)){
            $recom_itemid_str = $this->cache->get($roblox_redis_key);
        }else{
            $recom_itemid_str = $this->other->roblox_recom_itemid_str(9);
            
            $this->cache->set($roblox_redis_key,$recom_itemid_str,60*60*24);
        }

        $roblox_recom_data = Db::table("tp_youtube_url")->where("itemid","in",$recom_itemid_str)->orderBy("itemid","asc")->get();

        
        //-----------------  获取recommand推荐值 end ---------------

        $template_data = [
            "roblox_recom_data"=>$roblox_recom_data,
            "adsense_host_data"=>$adsense_host_data,
            "youtube_title"=>$youtube_title,
            "youtube_description"=>$youtube_description,
            "youtube_keyword"=>$youtube_keyword,
            "youtube_url"=>$youtube_url,
            "youtube_short_url"=>$youtube_short_url,

        ];

    
        return $this->render->render("/Index/Roblox/roblox_show", $template_data);
    }




    public function contact_us(){

        if($this->request->isMethod("POST")){
            
        

            // ---------------  时间戳验证码验证 begin  ------------------------------
            $hash_str = $this->request->input("hash_str") ? $this->request->input("hash_str") : "";
            if(!$this->cache->get(env('REDIS_PREFIX')."contact_hash_str".$hash_str) || $hash_str == ""){
                return $this->other->error("Please try again.",'/contact-us',5);
            }

            // ---------------  时间戳验证码验证 end  ------------------------------



            //数字验证码
            $cptc = $this->request->input("cptc");
            $cptc_number_1 = $this->request->input("cptc_number_1");
            $cptc_number_2 =  $this->request->input("cptc_number_2");

            $name =  $this->request->input("name");
            $message = $this->request->input("message");
            $email =  $this->request->input("email");


            //--------------------黑名单关键词判断 begin-------------------------
            $black_num = 0;//黑名单状态码
            $contact_black = env('BLACK_KEYWORD',"");
            $contact_black_word = explode("|",$contact_black);
            for($x=0;$x<count($contact_black_word);$x++){
                if(preg_match_all('#'.$contact_black_word[$x].'#i',$message) || preg_match_all('#'.$contact_black_word[$x].'#i',$name)  || preg_match_all('#'.$contact_black_word[$x].'#i',$email)){
                    
                    $black_num = 1;
                    break;
                }
            }


            if($black_num == 1){
                
                return $this->other->error("Error, Please try again!",'/',10);
            }

            //--------------------黑名单关键词判断 end -------------------------


            //验证通过就开始插入数据库
            if($cptc_number_1 + $cptc_number_2 == $cptc){
                $insert_data = [
                    "name" => $name,
                    "remote_ip" => $this->other->get_user_ip(),
                    "message" => $message,
                    "email" => $email,
                    "timestamp" => time()
                ];

                if(Db::table("tp_contact")->strict(false)->insert($insert_data)){
                    $this->other->success("Successfully",'/',5);
                }else{
                    $this->other->error("Unknown error",'/contact-us',5);
                }

            }else{
                $this->other->error("Captcha error",'/contact-us',3);
            }

        }else{
            
            $host_data = $this->other->get_host_data();
    


            //随机验证码,验证码的核心逻辑就是客户端与服务端的md5(ua+"-"+timestamp)进行验证,index首页使用timestamp进行伪装
            //verification
            $index_timestamp = time();
            $hash_str = md5($this->request->header('user-agent')."-".$index_timestamp);
            $this->cache->set(env('REDIS_PREFIX')."contact_hash_str".$hash_str, 1, 60*30);
            

            // hash验证 end



    
            $title = "Contact us - ".$host_data[0]->site_name;
            $keywords = "contact";
            $description = "If you have a question or a problem, you can reach our team by using the contact form.";
            
    
            $domain_url = $host_data[0]['http_prefix'].$this->other->get_request_url()."/";
    
            
            $cptc_number_1 = mt_rand(0,9);
            $cptc_number_2 = mt_rand(0,9);


            $template_data = [
                "index_timestamp"=>$index_timestamp,
                "cptc_number_1"=>$cptc_number_1,
                "cptc_number_2"=>$cptc_number_2,
                "domain_url"=>$domain_url,
                "title"=>$title,
                "keywords"=>$keywords,
                "description"=>$description,
            ];
 
        
           
            return $this->render->render("/Index/Roblox/roblox_contact", $template_data);
        }

    }


    public function roblox_search(){

        if($this->request->isMethod("POST")){
            $keyword = $this->request->input("keyword",NULL);
            
            if(strlen($keyword) < 3){
                $this->other->error("Keyword too short...","/",5);
            }

            $data = Db::table("tp_youtube_url")->where("title","like","%".$keyword."%")->limit(20)->get();
            





            //-----------------  获取recommand推荐值 begin ---------------
            $roblox_redis_key = env('REDIS_PREFIX')."reblox_search_recom";
                    
            if($this->cache->get($roblox_redis_key)){
                $recom_itemid_str = $this->cache->get($roblox_redis_key);
            }else{
                $recom_itemid_str = $this->other->roblox_recom_itemid_str(10);
                
                //搜索结果随机推荐缓存10分钟 ,
                $this->cache->set($roblox_redis_key,$recom_itemid_str,60*10);
            }

            $roblox_recom_data = Db::table("tp_youtube_url")->where("itemid","in",$recom_itemid_str)->orderBy("itemid","asc")->get();

            $template_data = [
                "data"=>$data,
                "roblox_recom_data"=>$roblox_recom_data,
            ];

            //-----------------  获取recommand推荐值 end ---------------
            return $this->render->render("/Index/Roblox/roblox_list", $template_data);
        



        }else{
            $this->other->error("Error","/",3);
        }
    }



    public function landing(){
        $this->other->error("please login...","/user-login",1);
    }



    public function user_login(){


        $host_data = $this->other->get_host_data();

        return $this->render->render("/Index/Roblox/roblox_login", []);
    }

    public function user_login_post(){
        $this->other->error("Password Error!",$this->request->header('HTTP_REFERER'),10);
    }
}
                 

                    