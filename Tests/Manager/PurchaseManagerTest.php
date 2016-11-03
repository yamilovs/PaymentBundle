<?php

namespace Yamilovs\PaymentBundle\Tests\Manager;


use Doctrine\ORM\EntityManagerInterface;
use Yamilovs\PaymentBundle\Entity\Purchase;
use Yamilovs\PaymentBundle\Manager\PurchaseManager;
use Yamilovs\PaymentBundle\Repository\PurchaseRepository;

class PurchaseManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return PurchaseManager
     */
    private function getPurchaseManager()
    {
        $products = [
            'FirstProductAlias' => [
                'name' => 'FirstProductName',
                'type' => '1',
                'class' => 'SomeClass',
                'primaryKey' => 'NonAnId',
            ],
        ];
        $manager = $this->getMockBuilder(PurchaseManager::class)
            ->setConstructorArgs(
                [$this->getMockEntityManager(), $products]
            )
            ->getMockForAbstractClass();

        return $manager;
    }

    private function getMockFirstProductEntity()
    {
        return $this->getMockBuilder('SomeClass')->setMethods(['getNonAnId'])->getMock();
    }

    private function getMockFirstProductEntityWithoutPrimaryKeyGetter()
    {
        return $this->getMockBuilder('SomeClass')->getMock();
    }

    private function getMockEntityManager()
    {
        $em = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValue($this->getMockPurchaseRepository())
            );
        return $em;
    }

    private function getMockPurchaseRepository()
    {
        $repo = $this->getMockBuilder(PurchaseRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('getPaidSum')
            ->will(
                $this->returnValue(1000)
            );
        return $repo;
    }

    private function getMockForNotValidProductEntity()
    {
        return $this->getMockBuilder('NotValidProductEntity')->getMock();
    }

    /**
     * @expectedException \Yamilovs\PaymentBundle\Manager\PurchaseInvalidArgumentException
     * @expectedExceptionMessage Product alias 'NonExistsProductAlias' doesn't has a configuration
     */
    public function testThatProductWasNotSet()
    {
        $manager = $this->getPurchaseManager();
        $product = $this->getMockFirstProductEntity();
        $manager->create('NonExistsProductAlias', $product);
    }

    /**
     * @expectedException \Yamilovs\PaymentBundle\Manager\PurchaseInvalidArgumentException
     * @expectedExceptionMessage Product object doesn't equal configured class
     */
    public function testThatProductWithWrongClassNotAllowed()
    {
        $manager = $this->getPurchaseManager();
        $product = $this->getMockForNotValidProductEntity();
        $manager->create('FirstProductAlias', $product);
    }

    public function testThatPurchaseWasCreated()
    {
        $manager = $this->getPurchaseManager();
        $product = $this->getMockFirstProductEntity();
        $purchase = $manager->create('FirstProductAlias', $product);
        $this->assertInstanceOf(Purchase::class, $purchase);
    }

    /**
     * @expectedException \Yamilovs\PaymentBundle\Manager\PurchaseInvalidArgumentException
     * @expectedExceptionMessage Method 'getNonAnId' doesn't found in class 'SomeClass'
     */
    public function testThatProductMustHasPrimaryKeyGetter()
    {
        $manager = $this->getPurchaseManager();
        $product = $this->getMockFirstProductEntityWithoutPrimaryKeyGetter();
        $manager->create('FirstProductAlias', $product);
    }

    public function testThatGetPaidSumReturnValue()
    {
        $manager = $this->getPurchaseManager();
        $product = $this->getMockFirstProductEntity();
        $amountPaid = $manager->getPaidSum('FirstProductAlias', $product);
        $this->assertEquals(1000, $amountPaid);
    }
}