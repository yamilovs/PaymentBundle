<?php

namespace ProfiTravel\PaymentBundle\Manager;

use anlutro\cURL\cURL;
use ProfiTravel\PaymentBundle\Entity\Payment;
use ProfiTravel\PaymentBundle\Event\PaymentCheckEvent;
use ProfiTravel\PaymentBundle\Event\PaymentRefundEvent;
use ProfiTravel\PaymentBundle\Event\PaymentResultFailureEvent;
use ProfiTravel\PaymentBundle\Event\PaymentResultSuccessEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentServicePlatron extends PaymentServiceAbstract implements PaymentServiceInterface
{
    const DELIMITER = ';';
    const ALIAS = 'platron';
    const PAYMENT_TIMEOUT = 300;

    protected $hostname;
    protected $merchantId;
    protected $secretKey;
    protected $salt;
    protected $apiUrlInit;

    protected $paramsMapping = [
        'sum'               => 'pg_amount',
        'purchase_id'       => 'pg_order_id',
        'user_phone'        => 'pg_user_phone',
        'user_mail'         => 'pg_user_mail',
        'description'       => 'pg_description',
    ];

    public function __construct($hostname, $merchantId, $secretKey, $salt, $apiUrlInit)
    {
        $this->hostname     = $hostname;
        $this->merchantId   = $merchantId;
        $this->secretKey    = $secretKey;
        $this->salt         = $salt;
        $this->apiUrlInit   = $apiUrlInit;
    }

    public function getAlias()
    {
        return self::ALIAS;
    }

    public function getPayUrl(array $params)
    {
        try {
            $normalizedParams = parent::getPayUrl($params);
            $data = $this->makeRequest($this->apiUrlInit, $normalizedParams);
            $this->checkSignature($this->apiUrlInit, $data);
            $this->setPayment($params['sum'], $data['pg_payment_id'], $params['purchase_id']);
            $this->logger->info('platron getPayUrl', array_merge($data, $params));
            return $data['pg_redirect_url'];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $params);
        }
    }

    public function rejectPay(array $params)
    {
        // TODO: Implement rejectPay() method.
    }

    /**
     * Make signature for platron api request
     *
     * @param $url
     * @param array $params
     * @return string
     */
    private final function generateSignature($url, array $params)
    {
        if (strpos($url, '/') !== false) {
            $url = substr($url, strrpos($url, '/') + 1);
        }
        return md5($url . self::DELIMITER . $this->implodeNestedArray($params) . self::DELIMITER . $this->secretKey );
    }

    /**
     * @param array $params
     * @return string
     */
    private function generateSalt(array $params)
    {
        return substr(md5($this->implodeNestedArray($params) . $this->salt), 0, 10);
    }

    /**
     * Join nested array
     *
     * @param array $params
     * @param string $delimiter
     * @return string
     */
    private function implodeNestedArray(array $params, $delimiter = self::DELIMITER)
    {
        ksort($params);
        $values = [];
        foreach( $params as $param ) {
            $values[] = ( is_array($param) ? $this->implodeNestedArray($param) : $param );
        }

        return implode($values, $delimiter);
    }

    /**
     * Make request to platron server and convert response to associative array
     *
     * @param $url
     * @param array $params
     * @return array
     */
    private function makeRequest($url, array $params)
    {
        $params['pg_merchant_id'] = $this->merchantId;
        $params = array_merge(['pg_salt' => $this->generateSalt($params)], $params);
        $params = array_merge(['pg_sig' => $this->generateSignature($url, $params)], $params);
        $curl = new cURL;
        $serverResponse = $curl->post("https://{$this->hostname}/{$url}", $params);
        $response = json_decode(json_encode((array) simplexml_load_string($serverResponse->body)), true);
        if (!is_array($response)) {
            throw new PaymentServiceInvalidArgumentException(
                "platron server invalid response: " . $serverResponse->body
            );
        }
        return $response;
    }

    private function checkSignature($url, array $params)
    {
        if (!array_key_exists('pg_sig', $params)) {
            throw new PaymentServiceInvalidArgumentException(
                "platron server response signature field don't exists."
            );
        }
        $signature = $params['pg_sig'];
        unset($params['pg_sig']);
        if ( $signature != $this->generateSignature($url, $params) ) {
            throw new PaymentServiceInvalidArgumentException(
                "platron server response invalid signature."
            );
        }
    }

    /**
     *
     *
     * @param  string  $url
     * @param  array   $params
     * @return array   array
     */
    public function checkPayment($url, $params)
    {
        $response = [
            'pg_status' => 'ok',
            'pg_timeout' => self::PAYMENT_TIMEOUT,
        ];

        $requiredParams = ['pg_payment_id', 'pg_order_id', 'pg_amount'];
        try {
             $this->checkSignature($url, $params);
            if ( array_diff($requiredParams, array_keys($params)) ) {
                throw new PaymentServiceInvalidArgumentException(
                    "required request param does not exists"
                );
            }
            $repo = $this->entityManager->getRepository('PaymentBundle:Payment');
            $payment = $repo->findOneBy(['paymentId' => $params['pg_payment_id']]);
            if ( !$payment ) {
                throw new PaymentServiceInvalidArgumentException(
                    "requested payment do not exists"
                );
            }
            if ( $payment->getPurchase()->getId() !== (int) $params['pg_order_id'] ) {
                throw new PaymentServiceInvalidArgumentException(
                    "requested payment has another purchase"
                );
            }
            if ( $payment->getInvoiceSum() != $params['pg_amount'] ) {
                throw new PaymentServiceInvalidArgumentException(
                    "does not match the payment amount"
                );
            }
            $event = new PaymentCheckEvent($payment, $params);
            $this->eventDispatcher->dispatch(PaymentCheckEvent::NAME, $event);
            if ( $event->getPayment()->getStatus() !== Payment::STATUS_NEW ) {
                throw new PaymentServiceInvalidArgumentException(
                    $event->getMessage() ?: "payment rejected from client"
                );
            }
            // change payment status
            $payment->setStatus(Payment::STATUS_WAIT_PAID);
            $this->entityManager->persist($payment);
            $this->entityManager->flush();
            $this->logger->info( 'checkPayment', $params);
        } catch (\Exception $e) {
            $this->logger->error( 'checkPayment: ' . $e->getMessage(), $params);
            $response = [
                'pg_status' => 'rejected',
                'pg_description' => $e->getMessage(),
            ];
        }
        return $this->makeResponse($url, $response);
    }

    public function resultPayment($url, $params)
    {
        $payment = null;
        $response = [];
        try {
            $this->checkSignature($url, $params);
            $requiredParams = ['pg_payment_id', 'pg_order_id', 'pg_amount', 'pg_result', 'pg_can_reject'];
            if ( array_diff($requiredParams, array_keys($params)) ) {
                throw new PaymentServiceInvalidArgumentException(
                    "required request param does not exists"
                );
            }
            $repo = $this->entityManager->getRepository('PaymentBundle:Payment');
            $payment = $repo->findOneBy(['paymentId' => $params['pg_payment_id']]);
            if ( !$payment ) {
                throw new PaymentServiceInvalidArgumentException(
                    "requested payment do not exists"
                );
            }
            if ( $payment->getInvoiceSum() != $params['pg_amount'] ) {
                throw new PaymentServiceInvalidArgumentException(
                    "does not match the payment amount"
                );
            }
            if ( (int) $params['pg_result'] !== 1 ) {
                throw new PaymentServiceInvalidArgumentException("payment response failure status");
            }
            $event = new PaymentResultSuccessEvent($payment, $params);
            $this->eventDispatcher->dispatch(PaymentResultSuccessEvent::NAME, $event);
            if ( $event->getPayment()->getStatus() !== Payment::STATUS_ERROR ) {
                throw new PaymentServiceInvalidArgumentException(
                    $event->getMessage() ?: "payment failure"
                );
            }
            // update payment status
            $payment
                ->setPaidSum($params['pg_amount'])
                ->setStatus(Payment::STATUS_PAID)
            ;
            $this->entityManager->persist($payment);
            $this->entityManager->flush();
            $this->logger->info( 'resultPayment', $params);
            $response = [
                'pg_status' => 'ok',
                'pg_description' =>'payment is accepted',
            ];
        } catch (\Exception $e) {
            $response['pg_status'] = 'error';
            if ($payment) {
                $event = new PaymentResultFailureEvent($payment, $params);
                $this->eventDispatcher->dispatch(PaymentResultFailureEvent::NAME, $event);
                $paymentStatus = Payment::STATUS_ERROR;
                if ( $params['pg_can_reject'] ) {
                    $response['pg_status'] = 'reject';
                    $paymentStatus = Payment::STATUS_WAIT_REJECT;
                }
                $payment->setStatus($paymentStatus);
                $this->entityManager->persist($payment);
                $this->entityManager->flush();
            }
            $response['pg_error_description'] = $e->getMessage();
            $this->logger->error( 'resultPayment: ' . $e->getMessage(), $params);
        }
        return $this->makeResponse($url, $response);
    }

    public function refundAction($url, $params)
    {
        $response = ['pg_status' => 'ok'];
        try {
            $this->checkSignature($url, $params);
            $requiredParams = ['pg_payment_id', 'pg_order_id', 'pg_amount', 'pg_net_amount', 'pg_refund_id'];
            if ( array_diff($requiredParams, array_keys($params)) ) {
                throw new PaymentServiceInvalidArgumentException(
                    "required request param does not exists"
                );
            }
            $repo = $this->entityManager->getRepository('PaymentBundle:Payment');
            $payment = $repo->findOneBy(['paymentId' => $params['pg_payment_id']]);
            if ( !$payment ) {
                throw new PaymentServiceInvalidArgumentException(
                    "requested payment do not exists"
                );
            }
            $sysInfoArr = explode(':', $payment->getSysInfo());
            if ( !in_array($params['pg_refund_id'], $sysInfoArr) ) {
                $payment->setPaidSum($payment->getPaidSum() - $params['pg_net_amount']);
                $payment->setStatus(
                    $payment->getPaidSum() > 0 ? Payment::STATUS_PARTIAL_REFUND : Payment::STATUS_REFUND
                );
                $this->entityManager->persist($payment);
                $this->entityManager->flush();
                $event = new PaymentRefundEvent($payment, $params);
                $this->eventDispatcher->dispatch(PaymentRefundEvent::NAME, $event);
            }
            $this->logger->info( 'refundPayment', $params);
        } catch (\Exception $e) {
            $response = [
                'pg_status' => 'error',
                'pg_error_description' => $e->getMessage(),
            ];
            $this->logger->error( 'refundPayment: ' . $e->getMessage(), $params);
        }
        return $this->makeResponse($url, $response);
    }

    public function successPayment($url, $params)
    {
        try {
            $this->checkSignature($url, $params);
            $requiredParams = ['pg_payment_id', 'pg_order_id'];
            if ( array_diff($requiredParams, array_keys($params)) ) {
                throw new PaymentServiceInvalidArgumentException(
                    "required request param does not exists"
                );
            }
            $repo = $this->entityManager->getRepository('PaymentBundle:Payment');
            $payment = $repo->findOneBy(['paymentId' => $params['pg_payment_id']]);
            if ( !$payment ) {
                throw new PaymentServiceInvalidArgumentException(
                    "requested payment do not exists"
                );
            }
            $event = new PaymentResultSuccessEvent($payment, $params);
            $this->eventDispatcher->dispatch(PaymentResultSuccessEvent::NAME, $event);
        } catch (\Exception $e) {
            $this->logger->error( 'successPayment: ' . $e->getMessage(), $params);
            throw new NotFoundHttpException("Page not found");
        }
    }

    public function failurePayment($url, $params)
    {
        try {
            $this->checkSignature($url, $params);
            $requiredParams = ['pg_payment_id', 'pg_order_id', 'pg_failure_code', 'pg_failure_description'];
            if ( array_diff($requiredParams, array_keys($params)) ) {
                throw new PaymentServiceInvalidArgumentException(
                    "required request param does not exists"
                );
            }
            $repo = $this->entityManager->getRepository('PaymentBundle:Payment');
            $payment = $repo->findOneBy(['paymentId' => $params['pg_payment_id']]);
            if ( !$payment ) {
                throw new PaymentServiceInvalidArgumentException(
                    "requested payment do not exists"
                );
            }
            $event = new PaymentResultFailureEvent($payment, $params);
            $this->eventDispatcher->dispatch(PaymentResultSuccessEvent::NAME, $event);
        } catch (\Exception $e) {
            $this->logger->error( 'failurePayment: ' . $e->getMessage(), $params);
            throw new NotFoundHttpException("Page not found");
        }
    }

    private function makeResponse($url, $params)
    {
        $params = array_merge(['pg_salt' => $this->generateSalt($params)], $params);
        $params = array_merge(['pg_sig' => $this->generateSignature($url, $params)], $params);
        return $params;
    }
}