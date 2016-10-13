<?php

namespace Yamilovs\PaymentBundle\Manager;

class PaymentFactory
{
    protected $paymentDefault;
    protected $payments = [];

    public function __construct($paymentDefault)
    {
        $this->paymentDefault = $paymentDefault;
    }

    public function setPaymentService(PaymentServiceInterface $paymentService)
    {
        $this->payments[$paymentService->getAlias()] = $paymentService;
    }

    /**
     * @param string $alias
     * @return PaymentServiceInterface
     * @throws PaymentServiceInvalidArgumentException
     */
    public function get($alias = null)
    {
        if (!$alias) {
            $alias = $this->paymentDefault;
        }

        if (!array_key_exists($alias, $this->payments)) {
            throw new PaymentServiceNotFountException("Payment service '$alias' not found in available payment services. Found ".implode(',', array_keys($this->payments)));
        }

        return $this->payments[$alias];
    }

    public function getPayments()
    {
        return $this->payments;
    }
}