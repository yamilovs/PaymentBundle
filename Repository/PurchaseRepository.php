<?php

namespace Yamilovs\PaymentBundle\Repository;

use Yamilovs\PaymentBundle\Entity\Payment;
use Doctrine\ORM\Query\Expr\Join;

/**
 * PurchaseRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PurchaseRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * return amount of paid sum from all payments by product id
     * @param $productType
     * @param $productId
     * @return float
     */
    public function getPaidSum($productType, $productId)
    {
        $qb = $this->createQueryBuilder('pr');
        $qb->innerJoin(
            'pr.payments',
            'pt', Join::WITH,
            $qb->expr()->in('pt.status', array(Payment::STATUS_PAID, Payment::STATUS_PARTIAL_PAID))
        );
        $qb->select(['SUM(pt.paidSum) as paidSum']);
        $qb->where(
            $qb->expr()->eq('pr.productType', $productType),
            $qb->expr()->eq('pr.productId', $productId)
        );
        return (float) $qb->getQuery()->getSingleScalarResult();
    }
}
