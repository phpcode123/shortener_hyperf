<?php
declare(strict_types=1);
namespace App\Controller\Index;


use Hyperf\DbConnection\Db;



class DevelopController  extends AbstractController
{    

    public function index(){
        $limit_time =30; //单位秒，默认限制速率为30秒

        #  ---------------------- 首页是否支持JS渲染验证 begin ----------------------
        $hash_str = $this->request->input("hash_str") ? $this->request->input("hash_str") : "";

        //echo "hash_str:".$hash_str;

        #如果数据不匹配就提示错误页面
        if(!$this->cache->has(env('REDIS_PREFIX')."index_hash_str".$hash_str) || $hash_str == ""){
            $this->other->error("User-agent error,Please try again.",'/',10);
        }
        #  ---------------------- 首页是否支持JS渲染验证 end ----------------------

        $user_agent = $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : "";
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ? $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] : "";

        if(!$this->cache->has(env('REDIS_PREFIX')."index_hash_str".$hash_str) || $hash_str == ""){
            $this->other->error("User-agent error,Please try again.",'/',3);
        }


        $redis_key = env('REDIS_PREFIX')."-api-token-".md5($user_agent.$lang);


    
        if($this->cache->has($redis_key)){
            $token_str = $this->cache->get($redis_key);
        }else{
            $token_str = substr(md5($user_agent.$lang.time()),0,16);

            $this->cache->set($redis_key,$token_str);

            
            Db::table("tp_api_token")->insert(['token'=>$token_str,"limit_time"=>$limit_time]);
        }

        echo "Method:POST"."    <br/>\n";
        echo "Shortener api url:"."https://m5.gs/shortener-api"."    <br/>\n";
        echo "Your token:".$token_str;
             
    }

}