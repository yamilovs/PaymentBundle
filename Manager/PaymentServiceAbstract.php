<?php

namespace ProfiTravel\PaymentBundle\Manager;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Doctrine\ORM\EntityManagerInterface;
use ProfiTravel\PaymentBundle\Entity\Payment;
use ProfiTravel\PaymentBundle\Entity\Purchase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class PaymentServiceAbstract implements PaymentServiceInterface
{
    protected $logger;
    protected $paramsMapping = [];
    /** @var  EntityManager */
    protected $entityManager;
    /** @var  EventDispatcher */
    protected $eventDispatcher;

    public final function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __toString()
    {
        return $this->getAlias();
    }

    public final function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public final function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    protected final function setPayment($sum, $paymentId, $purchaseId)
    {
        $purchase = $this->entityManager
            ->getRepository('PaymentBundle:Purchase')
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

        $this->checkRequiredFields($params, $requiredParams);

        return $this->transformParamsKey($params);
    }

    /**
     * Check required params fields
     *
     * @param array $params
     * @param array $requiredParams
     * @throws PaymentServiceInvalidArgumentException
     */
    private function checkRequiredFields(array $params, array $requiredParams)
    {
        $missingParams = array_diff($requiredParams, array_keys($params));
        if ($missingParams) {
            throw new PaymentServiceInvalidArgumentException('Miss required method params: ' . implode($missingParams, ', '));
        }
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
