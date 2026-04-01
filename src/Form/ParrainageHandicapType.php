<?php

namespace App\Form;

use App\Entity\Parrainage;
use App\Entity\ParrainageHandicap;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParrainageHandicapType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomComplet')
            ->add('cin')
            ->add('dateNaissance')
            ->add('lieuNaissance')
            ->add('genre')
            ->add('niveauScolaire')
            ->add('niveauEducatif')
            ->add('typeHandicap')
            ->add('causeHandicap')
            ->add('dateHandicap')
            ->add('tauxHandicap')
            ->add('typeTraitement')
            ->add('detailTraitement')
            ->add('coutTraitementMensuel')
            ->add('typeRevenuFixe')
            ->add('emploiActuel')
            ->add('revenuMensuel')
            ->add('revenuFoyerTotal')
            ->add('nbGarcons')
            ->add('nbFilles')
            ->add('besoins')
            ->add('adresse')
            ->add('telephone')
            ->add('telephone2')
            ->add('createdAt')
            ->add('updatedAt')
            ->add('parrainage', EntityType::class, [
                'class' => Parrainage::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParrainageHandicap::class,
        ]);
    }
}
