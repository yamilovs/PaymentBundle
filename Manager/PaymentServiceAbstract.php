<?php

namespace Yamilovs\PaymentBundle\Manager;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Yamilovs\PaymentBundle\Entity\Payment;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class PaymentServiceAbstract implements PaymentServiceInterface
{
    /** @var Logger */
    protected $logger;

    public function __toString()
    {
        return $this->getAlias();
    }

    public final function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public final function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public final function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function writeErrorLog($message, $parameters)
    {
        $this->logger->error($message, $parameters);
    }

    protected function writeInfoLog($message, $parameters)
    {
        $this->logger->info($message, $parameters);
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
            throw new PaymentServiceInvalidArgumentException("Some required parameters does not exists. Has: ".implode(", ", array_keys($parameters)).". 
            Also need: ".implode(", ", array_keys($missingParameters)));
        }
    }







    protected $paramsMapping = [];
    /** @var  EntityManager */
    protected $entityManager;
    /** @var  EventDispatcher */
    protected $eventDispatcher;









    protected final function setPayment($sum, $paymentId, $purchaseId)
    {
        $purchase = $this->entityManager
            ->getRepository('YamilovsPaymentBundle:Purchase')
            ->find($purchaseId);
        if (!$purchase) {
            throw new PaymentServiceInvalidArgumentException("purchase id don't exists: " . $purchaseId);
        }
        $payment = new Payment();
        $payment
            ->setPurchase($purchase)
            ->setPaymentId($paymentId)
            ->setInvoiceSum($sum)
            ->setPaymentType($this->getAlias());
        $this->entityManager->persist($payment);
        $this->entityManager->flush();
    }

    public function getPayUrl(array $params)
    {
        $requiredParams = [
            'sum',
            'purchase_id',
            'description',
            'user_phone',
            'user_mail',
        ];

        $this->checkRequiredParameters($requiredParams, $params);

        return $this->transformParamsKey($params);
    }


    /**
     * Transform required params keys to specific payment key
     *
     * @param array $params
     * @return array
     */
    private function transformParamsKey(array $params)
    {
        $result = [];
        $transformKeys = array_keys($this->paramsMapping);
        foreach ($params as $key => $param) {
            if (in_array($key, $transformKeys)) {
                $key = $this->paramsMapping[$key];
            }
            $result[$key] = $param;
        }
        return $result;
    }

}
