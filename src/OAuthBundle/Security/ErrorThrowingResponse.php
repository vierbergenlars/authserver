<?php
namespace OAuthBundle\Security;

use OAuth2\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorThrowingResponse extends Response
{

    private $code;

    public function __construct($code = 401)
    {
        $this->code = $code;
    }

    public function throwIfError()
    {
        if ($this->getHttpHeader('WWW-Authenticate')) {
            $ex = new HttpException($this->code, $this->getParameter('error_description'));
            $ex->setHeaders($this->getHttpHeaders());
        }
    }
}