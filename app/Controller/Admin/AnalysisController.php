<?php
namespace App\Controller\Admin;

use Hyperf\DbConnection\Db;
use Hyperf\Paginator\LengthAwarePaginator;


class AnalysisController  extends AbstractController
{   

    public function list(){
        //获取模板数据容器
        $template_data = $this->template_data;

        $page_num = $this->request->input('page',1);

        $list = array();
        for($i=0; $i< (int)env('CLICK_ANALYSIS_DAYS'); $i++){
            $new_array_ = array();
            $date_time = date("Y-m-d", time() - 60*60*24*$i);

            //总点击数量
            $redis_clicks_key = env('REDIS_PREFIX')."_shortener_clicks_".date("Y-m-d", time() - 60*60*24*$i);
            //pc端点击
            $redis_pc_clicks_key = env('REDIS_PREFIX')."_shortener_pc_clicks_".date("Y-m-d", time() - 60*60*24*$i);
            //m端点击
            $redis_m_clicks_key = env('REDIS_PREFIX')."_shortener_m_clicks_".date("Y-m-d", time() - 60*60*24*$i);
            //广告中间页面点击
            $redis_middle_page_clicks_key = env('REDIS_PREFIX')."_shortener_middle_page_clicks_".date("Y-m-d", time() - 60*60*24*$i);



            $redis_short_url_key = env('REDIS_PREFIX')."_shortener_short_url_".date("Y-m-d", time() - 60*60*24*$i);

            //获取总点击数
            if($this->cache->has($redis_clicks_key)){
                $total_clicks = $this->cache->get($redis_clicks_key);
            }else{
                $total_clicks = 0;
            }


            //获取pc点击数
            if($this->cache->has($redis_pc_clicks_key)){
                $total_pc_clicks = $this->cache->get($redis_pc_clicks_key);
            }else{
                $total_pc_clicks = 0;
            }


            //获取m端点击数
            if($this->cache->has($redis_m_clicks_key)){
                $total_m_clicks = $this->cache->get($redis_m_clicks_key);
            }else{
                $total_m_clicks = 0;
            }


            //获取中间页点击数
            if($this->cache->has($redis_middle_page_clicks_key)){
                $total_middle_page_clicks = $this->cache->get($redis_middle_page_clicks_key);
            }else{
                $total_middle_page_clicks = 0;
            }

            //获取当日生成的short_url数量
            if($this->cache->has($redis_short_url_key)){
                $total_short_url = $this->cache->get($redis_short_url_key);
            }else{
                $total_short_url = 0;
            }

            //将数据增加到new_array()容器中
            $new_array_["date_time"] = $date_time;
            $new_array_["all_clicks"] = $total_clicks;
            $new_array_["pc_clicks"] = $total_pc_clicks;
            $new_array_["m_clicks"] = $total_m_clicks;
            $new_array_["middle_page_clicks"] = $total_middle_page_clicks;
            $new_array_["short_url"] = $total_short_url;


            array_push($list, $new_array_);
        
            
        }
        //var_dump($list);

        //------------  将读取的数据储存在数据库中 begin ---------------------
        $insert_click_analysis_data = array();

        //将数组使用日期作为键，然后再对其进行升序，这样使用日期插入到数据库中的值就是按照最新日期排序的
        for($i=0; $i < count($list); $i++){
            $insert_click_analysis_data[$list[$i]['date_time']] = $list[$i];
        }
        
        asort($insert_click_analysis_data);
        foreach($insert_click_analysis_data as $key => $value){
            //当天最新的数据不要插入到数据库中（当天最新的数据值还未积累完）
            if($key != date("Y-m-d", time())){
                $analysis_data = Db::table("tp_click_analysis")->where("date_time","=",$key)->get();
                //如果返回的数据值大于0，则说明数据库中已经有此项数据，否则就将此项数据插入数据库
                if(count($analysis_data) == 0){
                    //var_dump($value);
                    Db::table("tp_click_analysis")->insert($value);
                }
            }
        }
        //------------  将读取的数据储存在数据库中 end ----------------------


        $data_list = Db::table('tp_click_analysis')->orderBy('itemid', 'desc')->paginate((int)env('ADMIN_PAGE_NUM')); 


        $paginator = new LengthAwarePaginator($data_list, env('ADMIN_PAGE_NUM'), $page_num);
        $data_list = $paginator->toArray();



        $data_list_array = [];
        foreach($data_list["data"]["data"] as $item){
            array_push($data_list_array, $item);
        }



        $today_datetime = date("Y-m-d", time());

        



        $malicious_data = Db::table("tp_report_malicious_url")->where("status","0")->get();
        $contact_data = Db::table("tp_contact")->where("status","0")->get();
        $shortener_last_data = Db::table("tp_shortener")->orderBy("itemid","desc")->limit(1)->get();
        


        //malicious url status 
        $malicious_url_key = env('REDIS_PREFIX')."malicisou_url_status";
        $malicious_url_status_time = $this->cache->get($malicious_url_key ,"0");
        //echo $malicious_url_status_time."   ";
        //echo time();
        $template_data["malicious_url_status_time"] = $malicious_url_status_time;
        $template_data["now_timestamp"] = time();


        //check_malicious_2_local 运行监控
        $check_malicious_2_local = env('REDIS_PREFIX')."check_malicious_2_post";
        $check_malicious_2_local_status_time = $this->cache->get($check_malicious_2_local ,"0");

        $template_data['check_malicious_2_local_status_time'] = $check_malicious_2_local_status_time;


        //抓取异常和嫌疑链接数据监控统计
        //8为监控脚本访问异常和需要逐条待审核的链接
        //9为中了监控脚本黑名单关键词的链接
        $check_malicious_8_data = Db::table("tp_shortener")->where("check_malicious_status","8")->get();
        $check_malicious_9_data = Db::table("tp_shortener")->where("check_malicious_status","9")->get();

        $template_data["check_malicious_8_count"] = count($check_malicious_8_data);
        $template_data["check_malicious_9_count"] = count($check_malicious_9_data);
        $template_data["malicious_data_num"] = count($malicious_data);
        $template_data["contact_data_num"] = count($contact_data);
        $template_data["shortener_last_data"] = $shortener_last_data;
        $template_data["data_list_array"] = $data_list_array;
        $template_data["data_list"] = $data_list;
        $template_data["today_datetime"] = $today_datetime;
        $template_data["page_num"] = $page_num;
        $template_data["list"] = $list;



        return $this->render->render('/Admin/Analysis/list', $template_data);       

    }

    public function edit(){
        $itemid = $this->request->input("itemid");
        if(empty($itemid)){
            return $this->other->error("Itemid is empty.",$_SERVER["HTTP_REFERER"],1);
        }

        $data = Db::table('tp_click_analysis')->where('itemid','=',$itemid)->get();
        
        $template_data = $this->template_data;
        $template_data['data'] = $data;
        return $this->render->render('/Admin/Analysis/edit', $template_data);      
    }

    public function editPost(){

        $itemid = $this->request->input('itemid');
        $ad_income = $this->request->input('ad_income');
        $ad_views = $this->request->input('ad_views');
        $ad_display = $this->request->input('ad_display');
        $ad_hits = $this->request->input('ad_hits');
        $ad_cpc = $this->request->input('ad_cpc');
        //分母不能为0
        try{
            $ad_rpm = $ad_income/$ad_views*1000;
            $ad_ctr = $ad_hits/$ad_views*100;
        }catch(\Exception $e){
            return $this->other->error("AD_VIEWS can not be 0.","/".env('ADMIN_PATH')."/analysis/list",2);
        }


        $insert_data = [
            "ad_views" => $ad_views,
            "ad_display" => $ad_display,
            "ad_hits" => $ad_hits,
            "ad_rpm" => $ad_rpm,
            "ad_income" => $ad_income,
            "ad_ctr" => $ad_ctr,
            "ad_cpc" => $ad_cpc
        ];

    

        if(Db::table('tp_click_analysis')->where('itemid',"=",$itemid)->update($insert_data)){
            return $this->other->success("Data edit success.","/".env('ADMIN_PATH')."/analysis/list",1);
        }else{
            return $this->other->error("Data edit fail.","/".env('ADMIN_PATH')."/analysis/list",2);
        }
    }
}
