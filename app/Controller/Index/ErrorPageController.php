<?php
declare(strict_types=1);
namespace App\Controller\Index;




class ErrorPageController  extends AbstractController
{

    
    public function index(){
        
        $host_data = $this->other->get_host_data();

       return $this->other->error("It's used for phishing, URL blocked!",$host_data[0]->http_prefix.$host_data[0]->domain_url,5);
    }

}