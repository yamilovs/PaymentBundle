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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $payments;

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
