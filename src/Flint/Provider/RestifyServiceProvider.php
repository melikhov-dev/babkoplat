<?php
namespace Flint\Provider;

use OAuth\Common\Exception\Exception;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class RestifyServiceProvider implements ServiceProviderInterface
{
    protected $isJson;

    public function isJson(Request $request)
    {
        if (null === $this->isJson) {
            $this->isJson = 0 === strpos($request->headers->get('Content-Type'), 'application/json')
                || substr($request->getPathInfo(), - strlen('.json')) === '.json'
                ;
        }

        return $this->isJson;
    }

    protected function getData($result, Request $request, Application $app)
    {

        if ($this->isJson($request)) {
            if (is_array($result)) {
                unset($result['_template']);
                if(isset($result['_layout'])){
                    unset($result['_layout']);
                }
            }
            $result = $result ? json_encode($result) : 'null';
        } else {
            if (is_array($result)) {
                if (isset($result['_template'])) {
                    $template = $result['_template'];
                } else {
                    $template = $request->get('_route');
                    $pos = strpos($template, '_');
                    if ($pos !== false) {
                        $template = substr($template, $pos + 1);
                    }
                    $template = str_replace('_', DIRECTORY_SEPARATOR, $template);
                }

                $content   = $app['template']->render($template, $result);

            }else{
                $content = $result;
                return $content;
            }


            $subLayouts = [$app['restify.layout.template']];
            $data = $result;

            if (isset($result['_layout'])) {
                $r = $result['_layout'];
                $subLayouts[] = $r['_layout'];
                $data = $r;
                unset($data['_layout']);
            }

            $data = array_merge($app[$app['restify.layout.data_provider']], $data, ['content' => $content]) + ['messages' => ''];

            foreach(array_reverse($subLayouts) as $l) {


                $data['content'] =  $app['template']->render($l, $data);
            }
            $result = $data['content'];
        }

        return $result;
    }

    public function register(Application $app)
    {

        $app['restify'] = $app->share(function(){
            return $this;
        });

        $app->before(function (Request $request) use ($app) {
            if(!$app['restify']->isJson($request)){
                $request->layout = [
                    '_template' => $app['restify.layout'],
                ];
            }else{
                $request->layout = false;
            }
        });

        $app->before(function (Request $request) use ($app) {
            if ($this->isJson($request)) {
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : array());
            }
        });

        $app->on(KernelEvents::VIEW, function (GetResponseForControllerResultEvent $event) use ($app) {
            if (null !== $event->getControllerResult()) {
                $data = $this->getData($event->getControllerResult(), $event->getRequest(), $app);
                $event->setResponse(new Response($data));
            }
        });

        $app->error(function (\Exception $e, $code) use ($app) {
            $headers = $e instanceof HttpException ? $e->getHeaders() : [];
            if($this->isJson($app['request'])) {
                return new Response($e->getMessage(), $code, $headers);
            }
        });
    }

    public function boot(Application $app) {}
}
