<?php

declare(strict_types=1);

namespace App\Middleware\Auth;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\View\RenderInterface;
use App\Service\OtherService;
use Hyperf\Cache\Cache;
use Hyperf\Contract\SessionInterface;



class CheckLoginMiddleware implements MiddlewareInterface
{
    #[Inject]
    protected RequestInterface $request;

    #[Inject]
    protected RenderInterface $render;

    #[Inject]
    protected Cache $cache;

    #[Inject]
    protected OtherService $other;

    #[Inject]
    protected SessionInterface $session;

    protected  $template_data;


    public function __construct(protected ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        
        //如果没有session授权就跳转到提示登录
        if(!$this->session->get('username')){  
            return $this->other->error("Please login.",'/'.env('ADMIN_PATH').'/login/login',3);
        }
    
        return $handler->handle($request);

    }
}
