<?php
namespace Flint\ControllerExtender\Controller;


use Symfony\Component\HttpFoundation\File\Exception\UploadException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\PreconditionRequiredHttpException;

trait ControllerAssertions
{
    protected function assertUserLoggedIn()
    {
        if (!$this->userId) {
            throw new AccessDeniedHttpException('Not authorized');
        }
    }

    protected function assertUploadFiles($files)
    {
        if (empty($files)) {
            throw new UploadException('Не были добавлены файлы');
        }
    }

    protected function assertKeyExists($key, array $array)
    {
        if (!array_key_exists($key, $array)) {
            throw new PreconditionRequiredHttpException('Invalid parameter count');
        }
    }
}
