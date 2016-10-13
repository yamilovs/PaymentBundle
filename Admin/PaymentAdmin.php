<?php

namespace Yamilovs\PaymentBundle\Admin;

use Imagine\Filter\Basic\Copy;
use ProfiTravel\NasheTravelBundle\Entity\Article;
use ProfiTravel\NasheTravelBundle\Form\Type\CkeditorType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\CoreBundle\Form\Type\DateTimePickerType;
use Sonata\AdminBundle\Form\FormMapper;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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

    // setup the default sort column and order
    protected $datagridValues = array(
        '_sort_order' => 'DESC',
        '_sort_by' => 'createdAt'
    );

    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        /** @var Payment $entity */
        $entity = $this->getSubject();
        $isNew = ($entity->getId()) ? false : true;

        $formMapper
            ->with('Основное')
                ->add('status', ChoiceType::class, [
                    'label'     => 'Статус',
                    'choices'   => Payment::getStatuses(),
                    'required'  => true,
                ])
                ->add('paymentType', ChoiceType::class, [
                    'label'     => 'Платежная система',
                    'choices'   => $this->payments,
                    'required'  => true,
                ])
                ->add('paymentId', TextType::class, [
                    'label'     => 'Id платежа во внешней системе',
                    'required'  => true,
                ])
                ->add('purchase', null, [
                    'label' => 'Платеж',
                ])
                ->add('invoiceSum', NumberType::class, [
                    'label'     => 'Сумма',
                    'required'  => true,
                ])
                ->add('paidSum', NumberType::class, [
                    'label'     => 'Сумма',
                    'required'  => true,
                ])
            ->end()
        ;

        if (!$isNew) {
            $formMapper
                ->with('Информация')
                ->add('created_at', DateTimePickerType::class, array(
                    'label' => 'Дата создания статьи',
                    'widget' => 'single_text',
                    'format' => 'dd MMMM YYYY в H:m:s',
                    'required' => false,
                    'disabled' => true,
                ))
                ->add('updated_at', DateTimePickerType::class, array(
                    'label' => 'Дата обновления статьи',
                    'widget' => 'single_text',
                    'format' => 'dd MMMM YYYY в H:m:s',
                    'required' => false,
                    'disabled' => true,
                ))
                ->end()
            ;
        }
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('paymentType',
                'doctrine_orm_choice',
                ['label' => 'Статус'],
                'choice' ,
                ['choices' => $this->payments]
            )
            ->add('status',
                'doctrine_orm_choice',
                ['label' => 'Статус'],
                'choice' ,
                ['choices' => Payment::getStatuses()]
            )
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id')
            ->add('status', 'choice', [
                'label'     => 'Статус',
                'choices'   => Payment::getStatuses(),
                'required'  => true,
            ])
            ->add('paymentType', 'choice', [
                'label'     => 'Платежная система',
                'choices'   => $this->payments,
                'required'  => true,
            ])
            ->add('paymentId', null, [
                'label'     => 'Id платежа во внешней системе',
                'required'  => true,
            ])
            ->add('purchase', null, [
                'label' => 'Платеж',
                'required' => false,
                'multiple' => true,
                'by_reference' => false,
            ])
            ->add('invoiceSum', null, [
                'label'     => 'Сумма счета',
                'required'  => true,
            ])
            ->add('paidSum', null, [
                'label'     => 'Сумма оплаты',
                'required'  => true,
            ])
            ->add('_action', 'actions', array(
                'label' => 'Действия',
                'actions' => array(
                    'edit' => array(),
                    'delete' => array(),
                )
            ))
        ;
    }

    // Field to be exported into file
    public function getExportFields()
    {
        return array('id', 'paymentType', 'paymentId', 'status', 'sum', 'createdAt', 'updatedAt');
    }

}