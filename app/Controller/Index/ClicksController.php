<?php
declare(strict_types=1);
namespace App\Controller\Index;


use Hyperf\DbConnection\Db;

class ClicksController  extends AbstractController
{



    public function index(){
        
       
        $host_data = $this->other->get_host_data();

        $title = "URL Click Counter";
        $keywords = "URL Click Counter,URL Click,counter";
        $description = "Click counter shows in real time how many clicks your shortened URL received so far.";




        //获取当前访问的url
        $domain_url = $host_data[0]->http_prefix.$this->other->get_request_url()."/";


        $data = [
            "domain_url"=>$domain_url,
            "title"=>$title,
            "keywords"=>$keywords,
            "description"=>$description,
            "year_num"=>env('YEAR_NUM')
        ];


        
        return $this->render->render("/Index/Clicks/total-clicks-index",$data);

    }





    public function total(){


        $remote_ip = $this->other->get_user_ip();
        $country = $this->other->get_country($remote_ip);

        $url = $this->request->input("url","");

        //echo $url;


        //当url为空时就直接展示模板，否则就启用查询url
        if($url == ""){
            $this->other->error("URL error",'/url-click-counter',3);
        }
        //当url不为空时
        //将url开始分割，将short_str分割出来
        $url_array = preg_split("/\..*?\//", $url);
        
        $short_str = "";

        if(count($url_array) == 2){
            $short_str = $url_array[1];
        }else{
            //如果1没有分割好说明是用户输入的数据有问题，采用数组下标0即可  此时可匹配用户直接输入short_str进行查询
            $short_str = $url_array[0];
        }



        //当short_str长度为0时，说明short_str传递参数有问题，提示错误
        if(strlen($short_str)==0){
            $this->other->error("URL error",'/url-click-counter',3);
        }

        $data = Db::table("tp_shortener")->where("short_url","=",$short_str)->get();
        //var_dump($data);


        //当返回的数据长度大于0时说明有匹配到结果，否则就点击数就是0
        if(count($data) > 0){
            $hits_num = $data[0]->hits;
        }else{
            $hits_num = 0;
        }



        //日志
        try{
            $this->other->log($remote_ip." - {$country} - ".$url." - Hits: {$hits_num}","counter_file_u6e.log");

        }catch(\Exception $e){
            //
        }





        //获取当前主url的数据
        
        $host_data = $this->other->get_host_data();

        $title = "Total URL Clicks - ".$host_data[0]->site_name;
        $keywords = "Total URL Clicks";
        $description = "The number of clicks that your shortened URL received.";
        //获取当前访问的url
        $domain_url = $host_data[0]->http_prefix.$this->other->get_request_url()."/";



        $data = [
            "domain_url"=>$domain_url,
            "title"=>$title,
            "keywords"=>$keywords,
            "description"=>$description,
            "hits_num"=>$hits_num,
            "year_num"=>env('YEAR_NUM'),
        ];


        return $this->render->render("/Index/Clicks/total-clicks-index-post",$data);

        
    }

}