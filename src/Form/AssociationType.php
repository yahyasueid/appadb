<?php

namespace App\Form;

use App\Entity\Association;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssociationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('nomAr')
            ->add('sigle')
            ->add('adresse')
            ->add('ville')
            ->add('pays')
            ->add('telephone')
            ->add('email')
            ->add('siteWeb')
            ->add('numeroAgrement')
            ->add('dateCreation')
            ->add('responsable')
            ->add('logo')
            ->add('isActive')
            ->add('createdAt')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Association::class,
        ]);
    }
}
