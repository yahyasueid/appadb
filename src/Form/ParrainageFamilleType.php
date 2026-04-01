<?php

namespace App\Form;

use App\Entity\Parrainage;
use App\Entity\ParrainageFamille;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParrainageFamilleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateNaissance')
            ->add('genre')
            ->add('nomChef')
            ->add('cinChef')
            ->add('niveauEducatif')
            ->add('adresseSкn')
            ->add('typeLogement')
            ->add('etatLogement')
            ->add('telephone')
            ->add('telephone2')
            ->add('revenuMensuelTotal')
            ->add('emploiChef')
            ->add('sourceRevenu')
            ->add('raisonDemande')
            ->add('etatSante')
            ->add('nombreMembres')
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
            'data_class' => ParrainageFamille::class,
        ]);
    }
}
