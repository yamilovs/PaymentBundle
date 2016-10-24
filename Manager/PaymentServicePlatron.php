<?php

namespace Yamilovs\PaymentBundle\Manager;

use anlutro\cURL\cURL;
use Yamilovs\PaymentBundle\Entity\Payment;
use Yamilovs\PaymentBundle\Event\PaymentCheckEvent;
use Yamilovs\PaymentBundle\Event\PaymentRefundEvent;
use Yamilovs\PaymentBundle\Event\PaymentResultEvent;

class PaymentServicePlatron extends AbstractPaymentService implements PaymentServiceInterface
{
    const ALIAS = 'platron';
    const DELIMITER = ';';
    const PAYMENT_TIMEOUT = 300;

    protected $hostname;    // yamilovs_payment.services.platron.hostname
    protected $merchantId;  // yamilovs_payment.services.platron.merchant_id
    protected $secretKey;   // yamilovs_payment.services.platron.secret_key
    protected $salt;        // yamilovs_payment.services.platron.salt
    protected $apiUrlInit;  // yamilovs_payment.services.platron.api_url_init

    protected $parametersMapping = [
        'sum' => 'pg_amount',
        'purchase_id' => 'pg_order_id',
        'user_phone' => 'pg_user_phone',
        'user_email' => 'pg_user_mail',
        'description' => 'pg_description',
    ];

    public function __construct($hostname, $merchantId, $secretKey, $salt, $apiUrlInit)
    {
        $this->hostname = $hostname;
        $this->merchantId = $merchantId;
        $this->secretKey = $secretKey;
        $this->salt = $salt;
        $this->apiUrlInit = $apiUrlInit;
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
     * Return payment object from database by it's id
     * @param $paymentId
     * @throws PaymentServiceInvalidArgumentException
     * @return Payment
     */
    private function getPaymentById($paymentId)
    {
        $repo = $this->entityManager->getRepository('YamilovsPaymentBundle:Payment');
        $payment = $repo->findOneBy([
            'paymentId' => $paymentId,
            'paymentType' => self::ALIAS,
        ]);
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
        return substr(md5($this->implodeNestedArray($parameters) . $this->salt), 0, 10);
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
        return md5($url . self::DELIMITER . $this->implodeNestedArray($parameters) . self::DELIMITER . $this->secretKey);
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
        foreach ($parameters as $param) {
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
     * Send a request to platron server and convert platron response to an associative array
     *
     * @param $url
     * @param array $parameters
     * @return array
     */
    private function makeRequest($url, array $parameters)
    {
        $parameters['pg_merchant_id'] = $this->merchantId;
        $parameters = array_merge(['pg_salt' => $this->generateSalt($parameters)], $parameters);
        $parameters = array_merge(['pg_sig' => $this->generateSignature($url, $parameters)], $parameters);

        $curl = new cURL;
        $serverResponse = $curl->post($this->hostname . "/" . $url, $parameters);
        $response = json_decode(json_encode((array)simplexml_load_string($serverResponse->body)), true);

        if (!is_array($response)) {
            throw new PaymentServiceInvalidArgumentException(
                "Invalid response from platron server: " . $serverResponse->body
            );
        }
        return $response;
    }

    /**
     * Check that signature of platron server response exists and equal our generated one
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
                "Platron response signature does not equal generated signature"
            );
        }
    }

    /**
     * Return data for platron refund action
     * @param $url
     * @param $parameters
     * @return array
     */
    public function getRefundResponseData($url, $parameters)
    {
        try {
            $this->checkSignature($url, $parameters);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id', 'pg_amount', 'pg_net_amount', 'pg_refund_id'), $parameters);
            $payment = $this->getPaymentById($parameters['pg_payment_id']);
        } catch (\Exception $e) {
            $this->logger->error("Failed payment refund action. Exception occurs: " . $e->getMessage(), $parameters);
            return $this->makeResponse($url, array('pg_status' => 'error', 'pg_error_description' => $e->getMessage()));
        }

        $sysInfoArr = explode(':', $payment->getSysInfo());
        if (!in_array($parameters['pg_refund_id'], $sysInfoArr)) {
            $payment->setPaidSum($payment->getPaidSum() - $parameters['pg_net_amount']);
            $payment->setStatus($payment->getPaidSum() > 0 ? Payment::STATUS_PARTIAL_REFUND : Payment::STATUS_REFUND);
            $payment->setSysInfo(implode(':', array_merge(array($parameters['pg_refund_id']), $sysInfoArr)));

            $event = new PaymentRefundEvent($payment, $parameters);
            $this->eventDispatcher->dispatch(PaymentRefundEvent::NAME, $event);

            $this->entityManager->flush();
        }

        $this->logger->info("Successfully payment refund action", $parameters);
        return $this->makeResponse($url, array('pg_status' => 'ok'));
    }

    /**
     * Return data for platron result action
     * @param $url
     * @param $parameters
     * @return array
     */
    public function getResultPaymentResponseData($url, $parameters)
    {
        try {
            $this->checkSignature($url, $parameters);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id', 'pg_amount', 'pg_result', 'pg_can_reject'), $parameters);
            $payment = $this->getPaymentById($parameters['pg_payment_id']);
            if ($payment->getInvoiceSum() != $parameters['pg_amount']) {
                throw new PaymentServiceInvalidArgumentException("Payment invoice amount not equal to the expected amount");
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed payment result action. Exception occurs: " . $e->getMessage(), $parameters);
            return $this->makeResponse($url, array(
                'pg_status' => 'error',
                'pg_error_description' => $e->getMessage()
            ));
        }

        $response = array('pg_status' => 'ok', 'pg_description' => 'payment was accepted');
        if ((int)$parameters['pg_result'] === 1) {
            if ($payment->getInvoiceSum() == $parameters['pg_amount']) {
                $payment
                    ->setPaidSum($parameters['pg_amount'])
                    ->setStatus(Payment::STATUS_PAID)
                ;
                $this->logger->info("Successful payment result action", $parameters);
            } else {
                if ((int)$parameters['pg_can_reject'] === 1) {
                    $payment->setStatus(Payment::STATUS_WAIT_REJECT);
                    $response = [
                        'pg_status' => 'reject',
                        'pg_description' => 'payment was rejected'
                    ];
                    $this->logger->error("Payment was rejected", $parameters);
                } else {
                    $payment
                        ->setPaidSum($parameters['pg_amount'])
                        ->setStatus(Payment::STATUS_PARTIAL_PAID)
                    ;
                    $this->logger->error("Failure. Partial paid", $parameters);
                }
            }
        } else {
            $response['pg_description'] = 'payment failure';
            $payment->setStatus(Payment::STATUS_ERROR);
            $this->logger->error("Failed payment result action. Payment failure", $parameters);
        }
        $this->entityManager->flush();
        $event = new PaymentResultEvent($payment, $parameters);
        $this->eventDispatcher->dispatch(PaymentResultEvent::NAME, $event);
        return $this->makeResponse($url, $response);
    }

    /**
     * Return data for platron check action
     * @param $url
     * @param $parameters
     * @return array
     */
    public function getCheckPaymentResponseData($url, $parameters)
    {
        try {
            $this->checkSignature($url, $parameters);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id', 'pg_amount'), $parameters);
            $payment = $this->getPaymentById($parameters['pg_payment_id']);

            if ($payment->getInvoiceSum() != $parameters['pg_amount']) {
                throw new PaymentServiceInvalidArgumentException("Payment invoice amount not equal to the expected amount");
            }
            if ($payment->getPurchase()->getId() !== (int)$parameters['pg_order_id']) {
                throw new PaymentServiceInvalidArgumentException("Purchase for payment does not equal requested order");
            }

            $event = new PaymentCheckEvent($payment, $parameters);
            $this->eventDispatcher->dispatch(PaymentCheckEvent::NAME, $event);
            if ($event->getPayment()->getStatus() !== Payment::STATUS_NEW) {
                throw new PaymentServiceInvalidArgumentException($event->getMessage() ?: "Payment was rejected by client");
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed payment check. Exception occurs: " . $e->getMessage(), $parameters);
            return $this->makeResponse($url, array('pg_status' => 'rejected', 'pg_description' => $e->getMessage()));
        }

        $payment->setStatus(Payment::STATUS_WAIT_PAID);
        $this->entityManager->flush();
        $this->logger->info("Successful payment check action", $parameters);
        return $this->makeResponse($url, array('pg_status' => 'ok', 'pg_timeout' => self::PAYMENT_TIMEOUT));
    }

    /**
     * Return an url for purchase.
     * @param array $parameters
     * @return string
     * @throws \Exception
     */
    public function getPayUrl(array $parameters)
    {
        try {
            $normalizedParameters = $this->getNormalizedParameters($parameters);
            $requestData = $this->makeRequest($this->apiUrlInit, $normalizedParameters);
            $this->checkSignature($this->apiUrlInit, $requestData);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_redirect_url'), $requestData);
            $this->createPayment($parameters['sum'], $requestData['pg_payment_id'], $parameters['purchase_id']);
        } catch (\Exception $e) {
            $this->logger->error("Can't create platron pay url. Exception occurs: " . $e->getMessage(), $parameters);
            throw $e;
        }
        $this->logger->info("Pay url was successfully generated: " . $requestData['pg_redirect_url'], array_merge($requestData, $parameters));
        return $requestData['pg_redirect_url'];
    }

    /**
     * Get payment with paid status by parameters
     * @param $url
     * @param $params
     * @return bool|Payment
     */
    public function getSuccessPayment($url, $params)
    {
        try {
            $this->checkSignature($url, $params);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id'), $params);
            $payment = $this->getPaymentById($params['pg_payment_id']);
        } catch (\Exception $e) {
            $this->logger->error("Error occurs while getting success payment: " . $e->getMessage(), $params);
            return null;
        }

        if ($payment->getStatus() != Payment::STATUS_PAID) {
            $payment->setStatus(Payment::STATUS_PAID);
            $this->entityManager->flush();
        }

        $event = new PaymentResultEvent($payment, $params);
        $this->eventDispatcher->dispatch(PaymentResultEvent::NAME, $event);

        return $payment;
    }

    /**
     * Get payment with failure status by parameters
     * @param $url
     * @param $params
     * @return bool|Payment
     */
    public function getFailurePayment($url, $params)
    {
        try {
            $this->checkSignature($url, $params);
            $this->checkRequiredParameters(array('pg_payment_id', 'pg_order_id', 'pg_failure_code', 'pg_failure_description'), $params);
            $payment = $this->getPaymentById($params['pg_payment_id']);
        } catch (\Exception $e) {
            $this->logger->error("Error occurs while getting failure payment: " . $e->getMessage(), $params);
            return null;
        }

        if ($payment->getStatus() != Payment::STATUS_ERROR) {
            $payment->setStatus(Payment::STATUS_ERROR);
            $this->entityManager->flush();
        }

        $event = new PaymentResultEvent($payment, $params);
        $this->eventDispatcher->dispatch(PaymentResultEvent::NAME, $event);

        return $payment;
    }
}