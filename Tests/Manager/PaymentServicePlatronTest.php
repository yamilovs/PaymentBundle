<?php

namespace Yamilovs\PaymentBundle\Tests\Manager;

use Yamilovs\PaymentBundle\Manager\PaymentServicePlatron;

class PaymentServicePlatronTest extends \PHPUnit_Framework_TestCase
{
    protected $merchantId = '12345';
    protected $secretKey = '1q2w3e4r5';
    protected $salt = 'qwerty';
    protected $apiUrlInit = 'init_payment.php';

    /**
     * @return PaymentServicePlatron
     */
    private function getPlatronService()
    {
        /** @var PaymentServicePlatron $platronService */
        $platronService = $this->getMockBuilder('Yamilovs\PaymentBundle\Manager\PaymentServicePlatron')
            ->setConstructorArgs(
                array($this->getHostName(), $this->merchantId, $this->secretKey, $this->salt, $this->apiUrlInit)
            )
            ->getMockForAbstractClass()
        ;
        $platronService->setLogger($this->getMockMonolog());
        $platronService->setEntityManager($this->getMockEntityManager());
        $platronService->setEventDispatcher($this->getMockEventDispatcher());

        return $platronService;
    }

    private function getHostName()
    {
        return "https://www.platron.ru";
    }

    private function getMockMonolog()
    {
        return $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();
    }

    private function getMockEntityManager()
    {
        return $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')->getMock();
    }

    private function getMockEventDispatcher()
    {
        return $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
    }

    /**
     * @expectedException \Yamilovs\PaymentBundle\Manager\PaymentServiceInvalidArgumentException
     * @expectedExceptionMessage Some required parameters does not exists. Has: . Also need: sum, purchase_id, description, user_phone, user_email
     */
    public function testThatGetPayUrlReturnException()
    {
        $platronService = $this->getPlatronService();
        $platronService->getPayUrl(array());
    }

    /**
     * @expectedException \Yamilovs\PaymentBundle\Manager\PaymentServiceInvalidArgumentException
     * @expectedExceptionMessage Some required parameters does not exists. Has: sum, another_field. Also need: purchase_id, description, user_phone, user_email
     */
    public function testThatGetPayUrlWithSomeParametersReturnException()
    {
        $platronService = $this->getPlatronService();
        $platronService->getPayUrl(array('sum' => 1000, 'another_field' => 'blah'));
    }

    /**
     * @expectedException \Yamilovs\PaymentBundle\Manager\PaymentServiceInvalidArgumentException
     * @expectedExceptionMessage Platron response does not contain any signature value ('pg_sig')
     */
    public function testThatGetPayUrlWithFullNonexistentParametersReturnException()
    {
        $platronService = $this->getPlatronService();
        $platronService->getPayUrl(array(
            'sum' => 1000,
            'purchase_id' => 1,
            'description' => 'blah',
            'user_phone' => '+79876543210',
            'user_email' => 'some@email.com',
        ));
    }
}