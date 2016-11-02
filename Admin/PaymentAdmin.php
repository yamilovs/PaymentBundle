<?php

namespace Yamilovs\PaymentBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\CoreBundle\Form\Type\DateTimePickerType;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Yamilovs\PaymentBundle\Entity\Payment;
use Yamilovs\PaymentBundle\Manager\PaymentFactory;

class PaymentAdmin extends AbstractAdmin
{
    protected $payments;

    public function setPayments(PaymentFactory $paymentFactory)
    {
        $this->payments = $paymentFactory->getPayments();
    }

    protected $datagridValues = array(
        '_sort_order'   => 'DESC',
        '_sort_by'      => 'createdAt'
    );

    protected function configureFormFields(FormMapper $formMapper)
    {
        /** @var Payment $entity */
        $entity = $this->getSubject();
        $isNew = ($entity->getId()) ? false : true;

        if (!$isNew) {
            $formMapper
                ->with('yamilovs.payment.admin.with.information')
                    ->add('created_at', DateTimePickerType::class, array(
                        'label' => 'yamilovs.payment.admin.label.created_at',
                        'widget' => 'single_text',
                        'format' => 'dd MMMM YYYY в H:m:s',
                        'required' => false,
                        'disabled' => true,
                    ))
                    ->add('updated_at', DateTimePickerType::class, array(
                        'label' => 'yamilovs.payment.admin.label.updated_at',
                        'widget' => 'single_text',
                        'format' => 'dd MMMM YYYY в H:m:s',
                        'required' => false,
                        'disabled' => true,
                    ))
                ->end()
            ;
        }

        $formMapper
            ->with('yamilovs.payment.admin.with.data')
                ->add('status', ChoiceType::class, [
                    'label' => 'yamilovs.payment.admin.label.status',
                    'choices' => Payment::getStatuses(),
                    'translation_domain' => 'SonataAdminBundle',
                ])
                ->add('paymentType', ChoiceType::class, [
                    'label' => 'yamilovs.payment.admin.label.payment_type',
                    'choices' => $this->payments,
                ])
                ->add('paymentId', TextType::class, [
                    'label' => 'yamilovs.payment.admin.label.payment_id',
                ])
                ->add('purchase', null, [
                    'label' => 'yamilovs.payment.admin.label.purchase',
                    'required' => true,
                ])
                ->add('invoiceSum', IntegerType::class, [
                    'label' => 'yamilovs.payment.admin.label.invoice_sum',
                ])
                ->add('paidSum', IntegerType::class, [
                    'label' => 'yamilovs.payment.admin.label.paid_sum',
                ])
            ->end()
        ;
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('paymentType', 'doctrine_orm_choice', ['label' => 'yamilovs.payment.admin.label.payment_type'], 'choice', ['choices' => $this->payments])
            ->add('status', 'doctrine_orm_choice', ['label' => 'yamilovs.payment.admin.label.status'], 'choice', ['choices' => Payment::getStatuses()])
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('status', 'choice', [
                'label' => 'yamilovs.payment.admin.label.status',
                'choices' => Payment::getStatuses(),
            ])
            ->add('paymentType', 'choice', [
                'label' => 'yamilovs.payment.admin.label.payment_type',
                'choices' => $this->payments,
            ])
            ->add('paymentId', null, [
                'label' => 'yamilovs.payment.admin.label.payment_id',
            ])
            ->add('purchase', null, [
                'label' => 'yamilovs.payment.admin.label.purchase',
            ])
            ->add('invoiceSum', null, [
                'label' => 'yamilovs.payment.admin.label.invoice_sum',
            ])
            ->add('paidSum', null, [
                'label' => 'yamilovs.payment.admin.label.paid_sum',
            ])
            ->add('createdAt', null, [
                'label' => 'yamilovs.payment.admin.label.created_at',
            ])
            ->add('updatedAt', null, [
                'label' => 'yamilovs.payment.admin.label.updated_at',
            ])
            ->add('_action', 'actions', array(
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }

    public function getExportFields()
    {
        return array('id', 'status', 'paymentType', 'paymentId', 'invoiceSum', 'paidSum', 'createdAt', 'updatedAt');
    }
}