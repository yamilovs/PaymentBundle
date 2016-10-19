<?php

namespace Yamilovs\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Yamilovs\PaymentBundle\Component\HttpFoundation\XmlResponse;
use Yamilovs\PaymentBundle\Event\PaymentControllerFailureEvent;
use Yamilovs\PaymentBundle\Event\PaymentControllerSuccessEvent;
use Yamilovs\PaymentBundle\Manager\PaymentServicePlatron;

class PlatronController extends Controller
{
    /**
     * Return xml response for platron check action
     * @param Request $request
     * @return XmlResponse
     */
    public function checkAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $data       = $manager->getCheckPaymentResponseData($this->generateUrl('yamilovs_payment_platron_check'), $params);
        return new XmlResponse($data);
    }

    /**
     * Return xml response for platron result action
     * @param Request $request
     * @return XmlResponse
     */
    public function resultAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $data       = $manager->getResultPaymentResponseData($this->generateUrl('yamilovs_payment_platron_result'), $params);
        return new XmlResponse($data);
    }

    /**
     * Return xml response for platron refund action
     * @param Request $request
     * @return XmlResponse
     */
    public function refundAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $data       = $manager->getRefundResponseData($this->generateUrl('yamilovs_payment_platron_refund'), $params);
        return new XmlResponse($data);
    }

    /**
     * Action that will be shown after clicking on "return to store" link in platron service after successful payment
     * @param Request $request
     * @return mixed
     */
    public function successAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $payment    = $manager->getSuccessPayment($this->generateUrl('yamilovs_payment_platron_success'), $params);

        if (!$payment) {
            return $this->createNotFoundException();
        }

        $event = new PaymentControllerSuccessEvent($payment);
        $this->get('event_dispatcher')->dispatch(PaymentControllerSuccessEvent::NAME, $event);

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        return $this->render(
            $event->getTemplate() ?: "YamilovsPaymentBundle:Platron:success.html.twig",
            $event->getTemplateParameters() ?: array('payment' => $payment)
        );
    }

    /**
     * Action that will be shown after clicking on "return to store" link in platron service after unsuccessful payment
     * @param Request $request
     * @return mixed
     */
    public function failureAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $payment    = $manager->getFailurePayment($this->generateUrl('yamilovs_payment_platron_failure'), $params);

        if (!$payment) {
            return $this->createNotFoundException();
        }

        $event = new PaymentControllerFailureEvent($payment);
        $this->get('event_dispatcher')->dispatch(PaymentControllerFailureEvent::NAME, $event);

        if ($event->getResponse() !== null) {
            return $event->getResponse();
        }

        return $this->render(
            $event->getTemplate() ?: "YamilovsPaymentBundle:Platron:failure.html.twig",
            $event->getTemplateParameters() ?: array('payment' => $payment)
        );
    }
}