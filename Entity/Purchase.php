<?php

namespace Yamilovs\PaymentBundle\Entity;

/**
 * Purchase
 */
class Purchase
{
    /**
     * @var int
     */
    private $id;

    private $productType;

    private $productId;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $payments;
    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @return mixed
     */
    public function getProductType()
    {
        return $this->productType;
    }

    /**
     * @param $productId
     * @return $this
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * @param $productType
     * @return $this
     */
    public function setProductType($productType)
    {
        $this->productType = $productType;

        return $this;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->payments = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add payment
     *
     * @param \Yamilovs\PaymentBundle\Entity\Payment $payment
     *
     * @return Purchase
     */
    public function addPayment(\Yamilovs\PaymentBundle\Entity\Payment $payment)
    {
        $this->payments[] = $payment;

        return $this;
    }

    /**
     * Remove payment
     *
     * @param \Yamilovs\PaymentBundle\Entity\Payment $payment
     */
    public function removePayment(\Yamilovs\PaymentBundle\Entity\Payment $payment)
    {
        $this->payments->removeElement($payment);
    }

    /**
     * Get payments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPayments()
    {
        return $this->payments;
    }

    public function __toString()
    {
        return (string) $this->getId();
    }
}
