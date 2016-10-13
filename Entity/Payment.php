<?php

namespace ProfiTravel\PaymentBundle\Entity;

/**
 * Payment
 */
class Payment
{
    const STATUS_NEW            = 1;
    const STATUS_WAIT_PAID      = 2;
    const STATUS_WAIT_REJECT    = 3;
    const STATUS_REJECT         = 4;
    const STATUS_PARTIAL_REFUND = 5;
    const STATUS_REFUND         = 6;
    const STATUS_PARTIAL_PAID   = 7;
    const STATUS_ERROR          = 9;
    const STATUS_PAID           = 10;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $paymentType;

    /**
     * @var string
     */
    private $paymentId;

    /**
     * @var string
     */
    private $invoiceSum;

    /**
     * @var string
     */
    private $paidSum;

    /**
     * @var int
     */
    private $status = self::STATUS_NEW;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @var string
     */
    private $sysInfo;

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
     * Set paymentType
     *
     * @param string $paymentType
     *
     * @return Payment
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    /**
     * Get paymentType
     *
     * @return string
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * Set invoiceSum
     *
     * @param string $invoiceSum
     *
     * @return Payment
     */
    public function setInvoiceSum($invoiceSum)
    {
        $this->invoiceSum = $invoiceSum;

        return $this;
    }

    /**
     * Get invoiceSum
     *
     * @return string
     */
    public function getInvoiceSum()
    {
        return $this->invoiceSum;
    }

    /**
     * Set paidSum
     *
     * @param string $paidSum
     *
     * @return Payment
     */
    public function setPaidSum($paidSum)
    {
        $this->paidSum = $paidSum;

        return $this;
    }

    /**
     * Get paidSum
     *
     * @return string
     */
    public function getPaidSum()
    {
        return $this->paidSum;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return Payment
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set sysInfo
     *
     * @param string $sysInfo
     *
     * @return Payment
     */
    public function setSysInfo($sysInfo)
    {
        $this->sysInfo = $sysInfo;

        return $this;
    }

    /**
     * Get sysInfo
     *
     * @return string
     */
    public function getSysInfo()
    {
        return $this->sysInfo;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Payment
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Payment
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @var \ProfiTravel\PaymentBundle\Entity\Purchase
     */
    private $purchase;

    /**
     * Set purchase
     *
     * @param \ProfiTravel\PaymentBundle\Entity\Purchase $purchase
     *
     * @return Payment
     */
    public function setPurchase(\ProfiTravel\PaymentBundle\Entity\Purchase $purchase = null)
    {
        $this->purchase = $purchase;

        return $this;
    }

    /**
     * Get purchase
     *
     * @return \ProfiTravel\PaymentBundle\Entity\Purchase
     */
    public function getPurchase()
    {
        return $this->purchase;
    }

    public function prePersist()
    {
        $this->setCreatedAt(new \DateTime());
    }

    public function preUpdate()
    {
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * Set paymentId
     *
     * @param string $paymentId
     *
     * @return Payment
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;

        return $this;
    }
    /**
     * Get paymentId
     *
     * @return string
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }


    public static function getStatuses()
    {
        return [
            self::STATUS_NEW            => 'Новый',
            self::STATUS_WAIT_PAID      => 'Ожидает оплаты',
            self::STATUS_WAIT_REJECT    => 'Ожидает отклонения',
            self::STATUS_REJECT         => 'Отклонен',
            self::STATUS_PARTIAL_REFUND => 'Частично возвращен',
            self::STATUS_REFUND         => 'Возвращен',
            self::STATUS_PARTIAL_PAID   => 'Частично оплачен',
            self::STATUS_ERROR          => 'Ошибка при оплате',
            self::STATUS_PAID           => 'Оплачен',
        ];
    }
}
