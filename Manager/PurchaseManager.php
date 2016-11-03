<?php

namespace Yamilovs\PaymentBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Yamilovs\PaymentBundle\Entity\Purchase;

class PurchaseManager
{
    protected $products;
    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager, array $products)
    {
        $this->entityManager = $entityManager;
        $this->products = $products;
    }

    /**
     * @param $productAlias
     * @param $entity
     * @return Purchase
     */
    public function create($productAlias, $entity)
    {
        $productConfig = $this->getProductConfig($productAlias, $entity);
        $primaryKeyGetter = $this->primaryKeyGetter($productConfig, $entity);

        $purchase = new Purchase();
        $purchase
            ->setProductType($productConfig['type'])
            ->setProductId($entity->$primaryKeyGetter())
        ;
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        return $purchase;
    }

    /**
     * return the sum paid for product
     *
     * @param $productAlias
     * @param $entity
     * @return array
     */
    public function getPaidSum($productAlias, $entity)
    {
        $productConfig = $this->getProductConfig($productAlias, $entity);
        $primaryKeyGetter = $this->primaryKeyGetter($productConfig, $entity);

        $repo = $this->entityManager->getRepository('YamilovsPaymentBundle:Purchase');
        return $repo->getPaidSum($productConfig['type'], $entity->$primaryKeyGetter());
    }

    /**
     *  return product config buy it's alias
     * @param $productAlias
     * @param $entity
     * @return array
     * @trows Yamilovs\PaymentBundle\Manager\PurchaseInvalidArgumentException
     */
    private function getProductConfig($productAlias, $entity)
    {
        if ( !array_key_exists($productAlias, $this->products)) {
            throw new PurchaseInvalidArgumentException("product don't exists");
        }
        $productConfig = $this->products[$productAlias];
        if ( !$entity instanceof $productConfig['class'] ) {
            throw new PurchaseInvalidArgumentException("not valid class");
        }
        return $productConfig;
    }

    /**
     * return a product primary key getter
     * @param array $productConfig
     * @param object $entity
     * @return string
     */
    private function primaryKeyGetter($productConfig, $entity)
    {
        $primaryKeyGetter = 'get'.strtoupper($productConfig['primaryKey']);
        if ( !method_exists($entity, $primaryKeyGetter)) {
            throw new PurchaseInvalidArgumentException("method do not exists");
        }
        return $primaryKeyGetter;
    }

}