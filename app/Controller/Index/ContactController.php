<?php
declare(strict_types=1);
namespace App\Controller\Index;


use Hyperf\DbConnection\Db;



class ContactController  extends AbstractController
{


    public function index(){

        if($this->request->isMethod("POST")){

            // ---------------  时间戳验证码验证 begin  ------------------------------
            $hash_str = $this->request->input("hash_str","");
            if(!$this->cache->has(env('REDIS_PREFIX')."contact_hash_str".$hash_str) || $hash_str == ""){
                return $this->other->error("Please try again.",'/',5);
            }

            // ---------------  时间戳验证码验证 end  ------------------------------



            //数字验证码
            $cptc = $this->request->input("cptc");
            $cptc_number_1 = $this->request->input("cptc_number_1");
            $cptc_number_2 = $this->request->input("cptc_number_2");

            $name = $this->request->input("name");
            $message = $this->request->input("message");
            $email = $this->request->input("email");


            //--------------------黑名单关键词判断 begin-------------------------
            $black_num = 0;//黑名单状态码
            $black_word = env('BLACK_KEYWORD',"");
            $contact_black_word = explode("|",$black_word);
            var_dump($contact_black_word);
            for($x=0;$x<count($contact_black_word);$x++){
                if(preg_match_all('/'.$contact_black_word[$x].'/i',$message) || preg_match_all('/'.$contact_black_word[$x].'/i',$name)  || preg_match_all('/'.$contact_black_word[$x].'/i',$email)){
                    
                    $black_num = 1;
                    break;//跳出当前循环
                }
            }


            if($black_num == 1){
                
               return  $this->other->error("Error, Please try again!",'/',10);
            }

            //--------------------黑名单关键词判断 end -------------------------
            

            $remote_ip = $this->other->get_user_ip();
            $user_county = $this->other->get_country($remote_ip);

            //验证通过就开始插入数据库
            if($cptc_number_1 + $cptc_number_2 == $cptc){
                $insert_data = [
                    "name" => $name,
                    "remote_ip" => $remote_ip,
                    'country' => $user_county,
                    "message" => $message,
                    "email" => $email,
                    "timestamp" => time()
                ];

                if(Db::table("tp_contact")->insert($insert_data)){
                    return $this->other->success("Submit success",'/contact',2);
                }else{
                    return $this->other->error("Unknown error",'/contact',3);
                }

            }else{
                return $this->other->error("Captcha error",'/contact',3);
            }

        }else{
            
            $host_data = $this->other->get_host_data();
    
    

            //随机验证码,验证码的核心逻辑就是客户端与服务端的md5(ua+"-"+timestamp)进行验证,index首页使用timestamp进行伪装
            //verification
            $index_timestamp = time();
            $hash_str = md5($this->request->header('user-agent')."-".$index_timestamp);
            $this->cache->set(env('REDIS_PREFIX')."contact_hash_str".$hash_str, 1, 60*30);
            

            // hash验证 end
            $title = "Contact - ".$host_data[0]->site_name;
            $keywords = "contact";
            $description = "If you have a question or a problem, you can reach our team by using the contact form.";
            
    
            $domain_url = $host_data[0]->http_prefix.$this->other->get_request_url()."/";
    
    
            $cptc_number_1 = mt_rand(0,9);
            $cptc_number_2 = mt_rand(0,9);

            $data = [
                "index_timestamp"=>$index_timestamp,
                "cptc_number_1"=>$cptc_number_1,
                "cptc_number_2"=>$cptc_number_2,
                "domain_url"=>$domain_url,
                "title"=>$title,
                "keywords"=>$keywords,
                "description"=>$description,
                "year_num"=>env('YEAR_NUM')
            ];
        
            return $this->render->render("/Index/Contact/contact", $data);
        }



    }
}
