<?php

namespace Yamilovs\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Yamilovs\PaymentBundle\Manager\PaymentServicePlatron;
use Yamilovs\PaymentBundle\Component\HttpFoundation\XmlResponse;
use Yamilovs\PaymentBundle\Event\PaymentControllerFailureEvent;
use Yamilovs\PaymentBundle\Event\PaymentControllerSuccessEvent;

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
        $view = 'YamilovsPaymentBundle:Platron:success.html.twig';
        try {
            $manager->checkPaymentSuccess($this->generateUrl('yamilovs_payment_platron_success'), $params);
            $payment = $manager->getPaymentByParams($params);
            $data = [ 'payment' => $payment ];
            $event = new PaymentControllerSuccessEvent($payment);
            $this->get('event_dispatcher')->dispatch(PaymentControllerSuccessEvent::NAME, $event);
            if ( $event->getResponse() ) {
                return $event->getResponse();
            }
        } catch (\Exception $e) {
            throw new NotFoundHttpException("Page not found");
        }
        $view = $event->getResponseView() ?: $view;
        $data = $event->getResponseParameters() ?: $data;
        return $this->render($view, $data);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function failureAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $view = 'YamilovsPaymentBundle:Platron:failure.html.twig';
        try {
            $manager->checkPaymentFailure($this->generateUrl('yamilovs_payment_platron_failure'), $params);
            $payment = $manager->getPaymentByParams($params);
            $data = [ 'payment' => $payment ];
            $event = new PaymentControllerFailureEvent($payment);
            $this->get('event_dispatcher')->dispatch(PaymentControllerFailureEvent::NAME, $event);
            if ( $event->getResponse() ) {
                return $event->getResponse();
            }
        } catch (\Exception $e) {
            throw new NotFoundHttpException("Page not found");
        }
        $view = $event->getResponseView() ?: $view;
        $data = $event->getResponseParameters() ?: $data;
        return $this->render($view, $data);
    }
}