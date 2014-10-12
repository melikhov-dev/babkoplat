<?php
namespace Babka\Controller;

use Flint\ControllerExtender\Controller\AppAbstractControllerInterface;
use Parse\ParseQuery;
use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use YandexMoney\API;
use YandexMoney\ExternalPayment;
use Symfony\Component\HttpFoundation\Session\Session;
use Parse\ParseClient;

class BabkaController implements AppAbstractControllerInterface
{
    private $app;

    /**
     * @var SessionInterface
     */
    protected $session;

    public function initialize(Application $app)
    {
        $this->app = $app;
        $this->session     = $app['session'];

    //    $this->base_path = 'http://hipstoplat.dev';
        $this->base_path = 'http://' . $_SERVER['SERVER_NAME'];
    }

    public function index(Request $request)
    {
        $message = false;
        if ($number = $request->get('number'))
        {
            if ($order = $this->_getOrder($number)) {
                /** @var $session Session */
                $session = $this->app['session'];
                $session->set('paymentId',$number);
                $session->set('sum',$order->get('amount'));
                $session->set('market',$order->get('market'));
                $session->set('comment', $order->get('comment'));
                $session->set('accountId', $order->get('accountId'));
                $session->set('objectId', $order->getObjectId());
                $session->set('complited', $order->get('complited'));
                if ($order->get('complited')){
                    return new RedirectResponse('/done');
                } else {
                    return new RedirectResponse('/submit');
                }
            } else {
                $message = 'Такой заказ не найден';
            }
        }
        $return = [
            'message' => $message,
            '_template' => 'main'
        ];
        return $return;
    }

    private function _getOrder($paymentId)
    {
        ParseClient::initialize('D55dHuZ3ZGAy5oSW3SEGcgWMaYHz7LY6JuBzicCI', 'A6uwYQPOE99rABeA7OZxI46xWSjcaIXQmtBknuOC', '4pSslc73pwIVulZWHiFpp9MjVrKOJl5q84JhdZob');
        $query = new ParseQuery("Payments");
        $query->equalTo("paymentId", (string)$paymentId);
        $results = $query->first();
        return $results;
    }

    private function _setComplited($objectId)
    {
        ParseClient::initialize('D55dHuZ3ZGAy5oSW3SEGcgWMaYHz7LY6JuBzicCI', 'A6uwYQPOE99rABeA7OZxI46xWSjcaIXQmtBknuOC', '4pSslc73pwIVulZWHiFpp9MjVrKOJl5q84JhdZob');
        $query = new ParseQuery("Payments");
        //print_r($objectId); die;
        $results = $query->get($objectId);
        //$results = $query->first();
        $results->set('complited',true);
        return $results->save();
    }

    public function submit(Request $request)
    {
        $session = $this->app['session'];
        if ($sum = $session->get('sum'))
        {
            $market = $session->get('market');
            $message = $session->get('comment');
            $ymId = $session->get('accountId');
            $client_id = '46AF88141E42C1ABB0B351AC7141FB637D80E8E36936C429A79F0F018866C6F3';

            $response = ExternalPayment::getInstanceId($client_id);
            if($response->status == "success") {
                $instance_id = $response->instance_id;
            }
            else {
                return new RedirectResponse('/error');
            }

            // make instance
            $external_payment = new ExternalPayment($instance_id);

            $payment_options = array(
                "pattern_id" => "p2p",
                "to" => $ymId,
                "amount" => $sum,
                "comment" => $message,
                "message" => $message,
                "label" => "testPayment",
                "test_payment" => true
            );
            $response = $external_payment->request($payment_options);
            if($response->status == "success") {
                $request_id = $response->request_id;
            }
            else {
                $return = [
                    'message' => $response->error_description,
                    '_template' => 'error'
                ];
                return $return;
            }

            $process_options = array(
                "request_id" => $request_id,
                "instance_id" => $instance_id,
                "ext_auth_success_uri" => $this->base_path . '/done',
                "ext_auth_fail_uri" => $this->base_path. '/error'
            );

            $session->set("request_id", $request_id);
            $session->set("instance_id", $instance_id);

            $result = $external_payment->process($process_options);

            $url = sprintf("%s?%s", $result->acs_uri,
                http_build_query($result->acs_params));
            //return new RedirectResponse($url);
            $return = [
                'market'   => $market,
                'message'   => $message,
                'sum'   => $sum,
                'url'   => $url,
                '_template' => 'submit'
            ];
            return $return;

        } else {
            return new RedirectResponse('/');
        }
    }

    public function done(Request $request)
    {
        $session = $this->app['session'];
        $request_id = $session->get("request_id");
        $instance_id = $session->get("instance_id");
        $payment_id = $session->get('paymentId');

        $api = new ExternalPayment($instance_id);

        do {
            $result = $api->process(array(
                "request_id" => $request_id,
                "ext_auth_success_uri" => $this->base_path . "/done",
                "ext_auth_fail_uri" => $this->base_path . "/error"
            ));
            if($result->status == "in_progress") {
                sleep(1);
            }
        } while ($result->status == "in_progress");

        if ($session->get('complited') || $result->status == 'success'){

            $this->_setComplited($session->get('objectId'));

            $return = [
                'paymentId'  => $payment_id,
                '_template' => 'done'
            ];

        } else {
            $return = [
                '_template' => 'error'
            ];
        }
        return $return;
    }

    public function error(Request $request)
    {
        $return = [
            'message' => '',
            '_template' => 'error'
        ];
        return $return;
    }

    public function about(Request $request)
    {
        $return = [
            '_template' => 'about'
        ];
        return $return;
    }

}
