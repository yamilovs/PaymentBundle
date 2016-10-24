<?php

namespace Yamilovs\PaymentBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Yamilovs\PaymentBundle\Entity\Payment;

abstract class AbstractPaymentService
{
    /** @var LoggerInterface */
    protected $logger;
    /** @var  EntityManagerInterface */
    protected $entityManager;
    /** @var  EventDispatcherInterface */
    protected $eventDispatcher;
    protected $parametersMapping = [];

    public function __toString()
    {
        return $this->getAlias();
    }

    abstract public function getAlias();

    public final function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public final function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public final function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Check that parameters has all required ones
     * @param array $parameters
     * @param array $requiredParameters
     * @throws PaymentServiceInvalidArgumentException
     */
    protected function checkRequiredParameters(array $requiredParameters, array $parameters)
    {
        $missingParameters = array_diff($requiredParameters, array_keys($parameters));
        if ($missingParameters) {
            throw new PaymentServiceInvalidArgumentException("Some required parameters does not exists. Has: " . implode(", ", array_keys($parameters)) . ". 
            Also need: " . implode(", ", array_keys($missingParameters)));
        }
    }

    /**
     * Return array of normalized parameters
     * @param $parameters
     * @return array
     */
    protected function getNormalizedParameters($parameters)
    {
        $requiredParameters = array('sum', 'purchase_id', 'description', 'user_phone', 'user_email');
        $this->checkRequiredParameters($requiredParameters, $parameters);
        return $this->getRemappedParameters($parameters);
    }

    /**
     * Get payment parameters based on specific service parametersMapping
     * @param array $parameters
     * @return array
     */
    private function getRemappedParameters(array $parameters)
    {
        $result = [];
        $mapKeys = array_keys($this->parametersMapping);

        foreach ($parameters as $key => $param) {
            if (in_array($key, $mapKeys)) {
                $key = $this->parametersMapping[$key];
            }
            $result[$key] = $param;
        }
        return $result;
    }

    /**
     * Create new payment for specific purchase
     * @param $amount
     * @param $paymentId
     * @param $purchaseId
     * @return Payment
     */
    protected final function createPayment($amount, $paymentId, $purchaseId)
    {
        $purchase = $this->entityManager->getRepository('YamilovsPaymentBundle:Purchase')->find($purchaseId);
        if (!$purchase) {
            throw new PaymentServiceInvalidArgumentException("Can't create new payment. Purchase with id '$purchaseId' not found in database");
        }

        $payment = new Payment();
        $payment
            ->setPurchase($purchase)
            ->setPaymentId($paymentId)
            ->setInvoiceSum($amount)
            ->setPaymentType($this->getAlias());
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }
}
