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

    protected function getProductType($product)
    {
        if ( !array_key_exists($product, $this->products)) {
            throw new \InvalidArgumentException("product don't exists");
        }
        return $this->products[$product];
    }

    /**
     * @param $productAlias
     * @param $entity
     * @return Purchase
     */
    public function create($productAlias, $entity)
    {
        if ( !array_key_exists($productAlias, $this->products)) {
            throw new \InvalidArgumentException("product don't exists");
        }
        $product = $this->products[$productAlias];
        if ( !$entity instanceof $product['class'] ) {
            throw new \InvalidArgumentException("not valid class");
        }
        $method = 'get'.strtoupper($product['primaryKey']);
        if ( !method_exists($entity, $method)) {
            throw new \InvalidArgumentException("method do not exists");
        }
        $purchase = new Purchase();

        $purchase
            ->setProductType($product['type'])
            ->setProductId($entity->$method())
        ;
        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        return $purchase;
     }

}