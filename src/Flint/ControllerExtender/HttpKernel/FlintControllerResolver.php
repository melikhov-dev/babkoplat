<?php
namespace Flint\ControllerExtender\HttpKernel;

use Flint\ControllerExtender\Controller\AppAbstractControllerInterface;
use Silex\ControllerResolver;
use Symfony\Component\HttpFoundation\Request;

class FlintControllerResolver extends ControllerResolver
{
    /**
     * @param Request $request
     * @param object|array $controller
     * @param \ReflectionParameter[] $parameters
     * @return array
     *
     * @throws \RuntimeException
     */
    protected function doGetArguments(Request $request, $controller, array $parameters)
    {
        if (
            (is_object($controller) && $controller instanceof AppAbstractControllerInterface)
            || (is_array($controller) && $controller[0] instanceof AppAbstractControllerInterface)
        ) {
            /** @var \ReflectionParameter[] $parameters */
            foreach ($parameters as $param) {
                if (!$param->isArray()) {
                    continue;
                }
                $name = $param->getName();
                switch ($name) {
                    case 'input':
                        $data = array_merge($request->attributes->get('_route_params', []), $request->query->all(), $request->request->all(), $request->files->all());
                        $request->attributes->set($name, $data);
                        break;

                    case 'request':
                    case 'files':
                    case 'query':
                        $data = $request->$name->all();
                        $request->attributes->set($name, $data);
                        break;

                    case 'attributes':
                        $request->attributes->set($name, $request->attributes->get('_route_params', []));
                        break;
                }
            }
        }

        return parent::doGetArguments($request, $controller, $parameters);
    }
}
