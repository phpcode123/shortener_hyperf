<?php
declare(strict_types=1);
namespace App\Controller\Index;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\View\RenderInterface;
use App\Service\OtherService;
use Hyperf\Cache\Cache;
use Psr\Container\ContainerInterface;


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
}