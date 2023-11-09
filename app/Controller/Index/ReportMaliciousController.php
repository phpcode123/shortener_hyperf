<?php
declare(strict_types=1);
namespace App\Controller\Index;


use Hyperf\DbConnection\Db;



class ReportMaliciousController  extends AbstractController
{


    public function index(){

        if($this->request->isMethod("POST")){
    
   
            // ---------------  时间戳验证码验证 begin  ---------------------
            $hash_str = $this->request->input("hash_str") ? $this->request->input("hash_str") : "";
            if(!$this->cache->has(env('REDIS_PREFIX')."reportMaliciousUrl_hash_str".$hash_str) || $hash_str == ""){
                return $this->other->error("Please try again.",'/',5);
            }

            // ---------------  时间戳验证码验证 end  --------------------

            //数字验证码
            $cptc = $this->request->input("cptc");
            $cptc_number_1 = $this->request->input("cptc_number_1");
            $cptc_number_2 = $this->request->input("cptc_number_2");

            $url = $this->request->input("url");
            $comment = $this->request->input("comment");
            $email = $this->request->input("email");

            //--------------- 判断url是否是本站的url begin --------------------------
            $domain_data = Db::table("tp_domain")->orderBy("itemid","desc")->get();
            $domain_num = 0;//状态码
            for($x=0;$x<count($domain_data);$x++){
                if(preg_match_all('/'.$domain_data[$x]->domain_url.'/i',$url)){
                    $domain_num = 1;
                    break;
                }
            }
            //return ;
            if($domain_num == 0){
                return $this->other->error("Report URL is not belong our site.",$_SERVER['HTTP_REFERER'],10);
            }


            //---------------- 判断url是否是本站的url begin -------------------------






            $remote_ip = $this->other->get_user_ip();
            $user_county = $this->other->get_country($remote_ip);

            //验证通过就开始插入数据库
            if($cptc_number_1 + $cptc_number_2 == $cptc){
                $insert_data = [
                    "url" => $url,
                    "remote_ip" => $remote_ip,
                    'country' => $user_county,
                    "comment" => $comment,
                    "email" => $email,
                    "timestamp" => time()
                ];

                if(Db::table("tp_report_malicious_url")->insert($insert_data)){
                    
                    return $this->other->success("Report success",'/report-malicious-url',2);
                }else{
                    return $this->other->error("Unknown error",'/report-malicious-url',3);
                }

            }else{
               return  $this->other->error("Captcha error",'/report-malicious-url',3);
            }

        }else{
    
            $host_data = $this->other->get_host_data();
    


            //随机验证码,验证码的核心逻辑就是客户端与服务端的md5(ua+"-"+timestamp)进行验证,index首页使用timestamp进行伪装
            //verification
            $index_timestamp = time();
            $hash_str = md5($this->request->header('user-agent')."-".$index_timestamp);
            $this->cache->set(env('REDIS_PREFIX')."reportMaliciousUrl_hash_str".$hash_str, 1, 60*30);
            

            // hash验证 end
    
            $title = "Report Malicious URL - ".$host_data[0]->site_name;
            $keywords = "Report Malicious URL";
            $description = "Use the form to report malicious short link to our team.";
            
    
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

        
            return $this->render->render("/Index/ReportMaliciousUrl/report-malicious-url",$data);
        }



    }
}
