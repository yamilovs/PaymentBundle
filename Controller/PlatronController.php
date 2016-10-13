<?php

namespace Yamilovs\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Yamilovs\PaymentBundle\Component\HttpFoundation\XmlResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Yamilovs\PaymentBundle\Manager\PaymentServicePlatron;

/**
 * Class TourCardController
 * @Route("/platron")
 * @package Yamilovs\PaymentBundle\Controller
 */
class PlatronController extends Controller
{
    /**
     * Проверка возможности платежа
     *
     * @Route("/check", name="payment_platron_check")
     * @Method({"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function checkAction(Request $request)
    {
        /** @var PaymentServicePlatron $platron */
        $manager = $this->get('payment.service.factory')->get(PaymentServicePlatron::ALIAS);
        $params = $request->request->all();
        // check signature
        $data = $manager->checkPayment($this->generateUrl('payment_platron_check'), $params);
        return new XmlResponse($data);
    }

    /**
     * Сообщение о результате платежа
     *
     * @Route("/result", name="payment_platron_result")
     * @Method({"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resultAction(Request $request)
    {
        /** @var PaymentServicePlatron $platron */
        $manager = $this->get('payment.service.factory')->get(PaymentServicePlatron::ALIAS);
        $params = $request->request->all();
        // check signature
        $data = $manager->resultPayment($this->generateUrl('payment_platron_result'), $params);
        return new XmlResponse($data);
    }

    /**
     * Сообщение о результате платежа
     *
     * @Route("/result", name="payment_platron_refund")
     * @Method({"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function refundAction(Request $request)
    {
        /** @var PaymentServicePlatron $platron */
        $manager = $this->get('payment.service.factory')->get(PaymentServicePlatron::ALIAS);
        $params = $request->request->all();
        // check signature
        $data = $manager->resultPayment($this->generateUrl('payment_platron_refund'), $params);
        return new XmlResponse($data);
    }

    /**
     * Url на который перенаправляется пользователь при удачном платеже
     *
     * @Route("/success", name="payment_platron_success")
     * @Method({"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function successAction(Request $request)
    {
        /** @var PaymentServicePlatron $platron */
        $manager = $this->get('payment.service.factory')->get(PaymentServicePlatron::ALIAS);
        $params = $request->request->all();
        // check signature
        $manager->successPayment($this->generateUrl('payment_platron_success'), $params);
        return $this->render('PaymentBundle:Default:index.html.twig');
    }

    /**
     * Url на который перенаправляется пользователь при неудачном платеже
     *
     * @Route("/failure", name="payment_platron_failure")
     * @Method({"POST"})
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function failureAction(Request $request)
    {
        $manager = $this->get('payment.service.factory')->get(PaymentServicePlatron::ALIAS);
        $params = $request->request->all();
        // check signature
        $manager->failurePayment($this->generateUrl('payment_platron_failure'), $params);
        return $this->render('PaymentBundle:Default:index.html.twig');
    }

}