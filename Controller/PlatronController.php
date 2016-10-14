<?php

namespace Yamilovs\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Yamilovs\PaymentBundle\Component\HttpFoundation\XmlResponse;
use Yamilovs\PaymentBundle\Manager\PaymentServicePlatron;

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
     * Url на который перенаправляется пользователь при удачном платеже
     *
     * @param Request $request
     */
    public function successAction(Request $request)
    {
        /** @var PaymentServicePlatron $manager */
        $manager    = $this->get('yamilovs.payment.factory')->get(PaymentServicePlatron::ALIAS);
        $params     = $request->request->all();
        $manager->successPayment($this->generateUrl('yamilovs_payment_platron_success'), $params);
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