<?php

namespace Yamilovs\PaymentBundle\Tests\Entity;

use Yamilovs\PaymentBundle\Entity\Payment;

class PaymentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return Payment
     */
    protected function getPayment()
    {
        return $this->getMockForAbstractClass('Yamilovs\PaymentBundle\Entity\Payment');
    }

    public function testCheckStatusConstants()
    {
        $this->assertEquals(1, Payment::STATUS_NEW);
        $this->assertEquals(2, Payment::STATUS_WAIT_PAID);
        $this->assertEquals(3, Payment::STATUS_WAIT_REJECT);
        $this->assertEquals(4, Payment::STATUS_REJECT);
        $this->assertEquals(5, Payment::STATUS_PARTIAL_REFUND);
        $this->assertEquals(6, Payment::STATUS_REFUND);
        $this->assertEquals(7, Payment::STATUS_PARTIAL_PAID);
        $this->assertEquals(9, Payment::STATUS_ERROR);
        $this->assertEquals(10, Payment::STATUS_PAID);
    }

    public function testPaymentType()
    {
        $payment = $this->getPayment();
        $payment->setPaymentType('platron');
        $this->assertSame('platron', $payment->getPaymentType());
    }
}