<?php

namespace Yamilovs\PaymentBundle\Tests\Manager;

use Yamilovs\PaymentBundle\Manager\PaymentFactory;
use Yamilovs\PaymentBundle\Manager\PaymentServiceInterface;
use Yamilovs\PaymentBundle\Manager\PaymentServicePlatron;

class PaymentFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return PaymentFactory
     */
    private function getPaymentFactory()
    {
        /** @var PaymentFactory $factory */
        $factory = $this->getMockBuilder(PaymentFactory::class)
            ->setConstructorArgs(
                array(PaymentServicePlatron::ALIAS)
            )
            ->getMockForAbstractClass()
        ;
        $factory->setPaymentService($this->getPlatronService());
        return $factory;
    }

    private function getPlatronService()
    {
        return $this->getMockBuilder(PaymentServicePlatron::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass()
        ;
    }

    /**
     * @expectedException \Yamilovs\PaymentBundle\Manager\PaymentServiceNotFountException
     * @expectedExceptionMessage Payment service 'SomeNonExistsPaymentService' not found in available payment services. Found platron
     */
    public function testGettingNonExistsPaymentService()
    {
        $factory = $this->getPaymentFactory();
        $factory->get('SomeNonExistsPaymentService');
    }

    public function testThatPlatronServiceExists()
    {
        $factory = $this->getPaymentFactory();
        $this->assertInstanceOf(PaymentServiceInterface::class, $factory->get(PaymentServicePlatron::ALIAS));
    }

    public function testThatPlatronIsDefaultPaymentService()
    {
        $factory = $this->getPaymentFactory();
        $this->assertInstanceOf(PaymentServicePlatron::class, $factory->get());
    }

    public function testGettingAllPayments()
    {
        $factory = $this->getPaymentFactory();
        $payments = $factory->getPayments();
        $this->assertArrayHasKey(PaymentServicePlatron::ALIAS, $payments);
    }

    public function testThatAllGettingPaymentsIsInstanceOfPaymentInterface()
    {
        $factory = $this->getPaymentFactory();
        $payments = $factory->getPayments();

        foreach ($payments as $payment) {
            $this->assertInstanceOf(PaymentServiceInterface::class, $payment);
        }
    }

    public function testThatAllGettingPaymentsHasAValidToStringMethod()
    {
        $factory = $this->getPaymentFactory();
        $payments = $factory->getPayments();

        /** @var PaymentServiceInterface $payment */
        foreach ($payments as $payment) {
            $this->assertEquals($payment->getAlias(), $payment);
        }
    }
}