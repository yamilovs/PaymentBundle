<?php

namespace Yamilovs\PaymentBundle\Manager;

use anlutro\cURL\cURL;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Yamilovs\PaymentBundle\Entity\Payment;
use Yamilovs\PaymentBundle\Event\PaymentCheckEvent;
use Yamilovs\PaymentBundle\Event\PaymentRefundEvent;
use Yamilovs\PaymentBundle\Event\PaymentResultFailureEvent;
use Yamilovs\PaymentBundle\Event\PaymentResultSuccessEvent;

class PaymentServicePlatron extends PaymentServiceAbstract implements PaymentServiceInterface
{
    const ALIAS     = 'platron';
    const DELIMITER = ';';

    protected $hostname;    // yamilovs_payment.services.platron.hostname
    protected $merchantId;  // yamilovs_payment.services.platron.merchant_id
    protected $secretKey;   // yamilovs_payment.services.platron.secret_key
    protected $salt;        // yamilovs_payment.services.platron.salt
    protected $apiUrlInit;  // yamilovs_payment.services.platron.api_url_init

    public function __construct($hostname, $merchantId, $secretKey, $salt, $apiUrlInit)
    {
        $this->hostname     = $hostname;
        $this->merchantId   = $merchantId;
        $this->secretKey    = $secretKey;
        $this->salt         = $salt;
        $this->apiUrlInit   = $apiUrlInit;
    }
    
    /**
     * Return an alias of this payment service
     * @return string
     */
    public function getAlias()
    {
        return self::ALIAS;
    }

    /**
     * Check that payment invoice amount is equal to expected invoice amount
     * @param Payment $payment
     * @param $invoiceSum
     * @throws PaymentServiceInvalidArgumentException
     */
    private function checkPaymentInvoiceSum(Payment $payment, $invoiceSum)
    {
        if ($payment->getInvoiceSum() != $invoiceSum) {
            throw new PaymentServiceInvalidArgumentException(
                "Payment invoice amount not equal to the expected amount"
            );
        }
    }

    /**
     * Return payment object from database by it's id
     * @param $paymentId
     * @throws PaymentServiceInvalidArgumentException
     * @return Payment
     */
    private function getPaymentById($paymentId)
    {
        $repo = $this->entityManager->getRepository('YamilovsPaymentBundle:Payment');
        $payment = $repo->findOneBy(['paymentId' => $paymentId]);
        if (!$payment) {
            throw new PaymentServiceInvalidArgumentException(
                "Payment with id '$paymentId' does not exists in database"
            );
        }
        return $payment;
    }

    /**
     * Create request salt based on request parameters
     * @param array $parameters
     * @return string
     */
    private function generateSalt(array $parameters)
    {
        return substr(md5($this->implodeNestedArray($parameters).$this->salt), 0, 10);
    }
    
    /**
     * Return platron signature for response based on request parameters
     * More info on http://www.platron.ru/integration/Merchant_Platron_API_EN.pdf
     * @param $url
     * @param array $parameters
     * @return string
     */
    private function generateSignature($url, array $parameters)
    {
        if (strpos($url, '/') !== false) {
            $url = substr($url, strrpos($url, '/') + 1);
        }
        return md5($url.self::DELIMITER.$this->implodeNestedArray($parameters).self::DELIMITER.$this->secretKey);
    }

    /**
     * Recursively implode arrays with delimiter
     * @param array $parameters
     * @param string $delimiter
     * @return string
     */
    private function implodeNestedArray(array $parameters, $delimiter = self::DELIMITER)
    {
        $values = [];
        ksort($parameters);
        foreach($parameters as $param) {
            $values[] = is_array($param) ? $this->implodeNestedArray($param) : $param;
        }
        return implode($values, $delimiter);
    }

    /**
     * Return array of parameters for response
     * @param $url
     * @param $parameters
     * @return array
     */
    private function makeResponse($url, array $parameters)
    {
        $parameters = array_merge(['pg_salt' => $this->generateSalt($parameters)], $parameters);
        $parameters = array_merge(['pg_sig' => $this->generateSignature($url, $parameters)], $parameters);
        return $parameters;
    }

    /**
     * Check that signature of platron server respones exists and equal our generated one
     * @param $url
     * @param array $parameters
     */
    private function checkSignature($url, array $parameters)
    {
        if (!array_key_exists('pg_sig', $parameters)) {
            throw new PaymentServiceInvalidArgumentException(
                "Platron response does not contain any signature value ('pg_sig')"
            );
        }
        $signature = $parameters['pg_sig'];
        unset($parameters['pg_sig']);
        if ($signature != $this->generateSignature($url, $parameters)) {
            throw new PaymentServiceInvalidArgumentException(
                "Platron responsed signature does not equal generated signature"
            );
        }
    }




















    const PAYMENT_TIMEOUT = 300;


    protected $paramsMapping = [
        'sum'               => 'pg_amount',
        'purchase_id'       => 'pg_order_id',
        'user_phone'        => 'pg_user_phone',
        'user_mail'         => 'pg_user_mail',
        'description'       => 'pg_description',
    ];

    



    public function getPayUrl(array $params)
    {
        try {
            $normalizedParams = parent::getPayUrl($params);
            $data = $this->makeRequest($this->apiUrlInit, $normalizedParams);
            $this->checkSignature($this->apiUrlInit, $data);
            $this->setPayment($params['sum'], $data['pg_payment_id'], $params['purchase_id']);
            $this->writeInfoLog("platron getPayUrl", array_merge($data, $params));
            return $data['pg_redirect_url'];
        } catch (\Exception $e) {
            $this->writeErrorLog($e->getMessage(), $params);
        }
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

        try {
            $this->checkSignature($url, $params);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id', 'pg_amount'), $params);
            $payment = $this->getPaymentById($params['pg_payment_id']);




            if ( $payment->getPurchase()->getId() !== (int) $params['pg_order_id'] ) {
                throw new PaymentServiceInvalidArgumentException(
                    "requested payment has another purchase"
                );
            }
            $this->checkPaymentInvoiceSum($payment, $params['pg_amount']);

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
            $this->writeInfoLog("checkPayment", $params);
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
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id', 'pg_amount', 'pg_result', 'pg_can_reject'), $params);
            $payment = $this->getPaymentById($params['pg_payment_id']);
            $this->checkPaymentInvoiceSum($payment, $params['pg_amount']);





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
            $this->writeInfoLog("resultPayment", $params);
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
            $this->writeErrorLog("resultPayment: ".$e->getMessage(), $params);
        }
        return $this->makeResponse($url, $response);
    }

    public function refundAction($url, $params)
    {
        $response = ['pg_status' => 'ok'];
        try {
            $this->checkSignature($url, $params);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id', 'pg_amount', 'pg_net_amount', 'pg_refund_id'), $params);
            $payment = $this->getPaymentById($params['pg_payment_id']);




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
            $this->writeInfoLog("refundPayment", $params);
        } catch (\Exception $e) {
            $response = [
                'pg_status' => 'error',
                'pg_error_description' => $e->getMessage(),
            ];
            $this->writeErrorLog("refundPayment: ".$e->getMessage(), $params);
        }
        return $this->makeResponse($url, $response);
    }

    public function checkPaymentSuccess($url, $params)
    {
        try {
            $this->checkSignature($url, $params);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id'), $params);
            $payment = $this->getPaymentById($params['pg_payment_id']);
            if ( $payment->getStatus() != Payment::STATUS_PAID ) {
                $payment->setStatus(Payment::STATUS_PAID);
                $this->entityManager->flush();
            }
            $event = new PaymentResultSuccessEvent($payment, $params);
            $this->eventDispatcher->dispatch(PaymentResultSuccessEvent::NAME, $event);
        } catch (\Exception $e) {
            $this->writeErrorLog("checkPaymentSuccess ".$e->getMessage(), $params);
            throw new PaymentServiceInvalidArgumentException("requested payment had invalid params");
        }
    }

    public function checkPaymentFailure($url, $params)
    {
        try {
            $this->checkSignature($url, $params);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id', 'pg_failure_code', 'pg_failure_description'), $params);
            $payment = $this->getPaymentById($params['pg_payment_id']);
            if ( $payment->getStatus() != Payment::STATUS_ERROR ) {
                $payment->setStatus(Payment::STATUS_ERROR);
                $this->entityManager->flush();
            }
            $event = new PaymentResultFailureEvent($payment, $params);
            $this->eventDispatcher->dispatch(PaymentResultFailureEvent::NAME, $event);
        } catch (\Exception $e) {
            $this->writeErrorLog("checkPaymentFailure ".$e->getMessage(), $params);
            throw new PaymentServiceInvalidArgumentException("requested payment had invalid params");
        }
    }

    public function getPaymentByParams(array $params)
    {
        return $this->getPaymentById($params['pg_payment_id']);
    }
}