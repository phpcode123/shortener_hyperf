<?php
declare(strict_types=1);
namespace App\Controller\Admin;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\View\RenderInterface;
use App\Service\OtherService;
use Hyperf\Cache\Cache;
use Psr\Container\ContainerInterface;
use Hyperf\Contract\SessionInterface;


abstract class AbstractController
{
    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected RequestInterface $request;

    #[Inject]
    protected ResponseInterface $response;

    #[Inject]
    protected RenderInterface $render;

    #[Inject]
    protected Cache $cache;

    #[Inject]
    protected OtherService $other;

    #[Inject]
    protected SessionInterface $session;

    //模板参数数组容器
    protected array $template_data;
    

    //注册模板变量
    public function __construct()
    {
        $template_ = [
            "app_name"=>env('APP_NAME'),
            "controller_name"=> $this->other->get_action(),
            "admin_path"=>env('ADMIN_PATH'),
            "admin_url"=>env('ADMIN_URL'),
            "username"=>$this->session->get('username','')
        ];
        $this->template_data = $template_;
    }
}