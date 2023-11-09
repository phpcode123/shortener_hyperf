<?php
namespace App\Controller\Admin;



class LoginController extends AbstractController
{

    public function index(){
        if($this->request->isMethod("GET")){

            return $this->render->render('/Admin/Login/login',$this->template_data);
        }

        if($this->request->isMethod("POST")){
            $username = $this->request->input('username');
            $password = $this->request->input('password');

            
            if($username == env('ADMIN_USERNAME') && $password == env('ADMIN_PASSWORD')){
                $this->session->set('username',$username); 
                return $this->other->success("Login Success.",'/'.env('ADMIN_PATH').'/index',1);
            }else{
                return $this->other->error("Login Fail,Please check parameter!",$_SERVER["HTTP_REFERER"],2);
            }
        }
        
    }

    public function logout(){
        if(!$this->session->has('username')){
            return $this->other->error("Unauthorized, please login first",'/'.env('ADMIN_PATH').'/login/login', 2);
        }else{
            $this->session->forget('username');
            return $this->other->success("Logout success.",'/'.env('ADMIN_PATH').'/login/login', 1);
        }

    }
}