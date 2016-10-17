<?php

namespace Yamilovs\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Yamilovs\PaymentBundle\Component\HttpFoundation\XmlResponse;
use Yamilovs\PaymentBundle\Event\PaymentControllerResultSuccessEvent;
use Yamilovs\PaymentBundle\Manager\PaymentServicePlatron;
use Yamilovs\PaymentBundle\Event\PaymentResultSuccessEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PlatronController extends Controller
{
    /**
     * Проверка возможности платежа
     *
     * @param Request $request
     * @return XmlResponse
     */
    public function checkAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $data       = $manager->checkPayment($this->generateUrl('yamilovs_payment_platron_check'), $params);
        return new XmlResponse($data);
    }

    /**
     * Сообщение о результате платежа
     *
     * @param Request $request
     * @return XmlResponse
     */
    public function resultAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $data       = $manager->resultPayment($this->generateUrl('yamilovs_payment_platron_result'), $params);
        return new XmlResponse($data);
    }

    /**
     * Сообщение о результате платежа
     *
     * @param Request $request
     * @return XmlResponse
     */
    public function refundAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $data       = $manager->resultPayment($this->generateUrl('yamilovs_payment_platron_refund'), $params);
        return new XmlResponse($data);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function successAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $view = 'PaymentBundle:Platron:success.html.twig';
        try {
            $manager->checkPaymentSuccess($this->generateUrl('yamilovs_payment_platron_success'), $params);
            $payment = $manager->getPaymentByParams($params);
            $data = [ 'payment' => $payment ];
            $event = new PaymentControllerResultSuccessEvent($payment);
            $this->get('event_dispatcher')->dispatch(PaymentResultSuccessEvent::NAME, $event);
            if ( $event->getResponse() ) {
                return $event->getResponse();
            }
        } catch (\Exception $e) {
            throw new NotFoundHttpException("Page not found");
        }
        $view = $event->getView() ?: $view;
        $data = $event->getData() ?: $data;
        return $this->render($view, $data);
    }

    /**
     * Url на который перенаправляется пользователь при неудачном платеже
     *
     * @param Request $request
     */
    public function failureAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params = $request->request->all();
        $manager->failurePayment($this->generateUrl('yamilovs_payment_platron_failure'), $params);
    }
}