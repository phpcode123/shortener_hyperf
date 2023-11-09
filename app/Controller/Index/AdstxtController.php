<?php
declare(strict_types=1);
namespace App\Controller\Index;


use Hyperf\DbConnection\Db;


class AdstxtController  extends AbstractController
{



    public function index(){
        $adsense_data = Db::table("tp_adsense")->where("adsense_domain",$this->other->get_request_url())->get();

        if(count($adsense_data) == 1){
            return $adsense_data[0]->adsense_txt;
        }else{
            
            $ads_file = fopen(BASE_PATH."/public/ads.txt","r+");
            $txt = fgets($ads_file);
            fclose($ads_file);
            return $txt;
        }

    }
}