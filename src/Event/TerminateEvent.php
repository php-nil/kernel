<?php
namespace Nil\Kernel\Event;

use Symfony\Component\HttpFoundation\{Response,Request};

/**
 * 输出内容之后(扫尾工作)
 */
class TerminateEvent
{
    protected $response;
    protected $request;

    public function __construct(Request $request, Response $response)
    {
        $this->response = $response;
        $this->request = $request;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequest()
    {
        return $this->request;
    }
}