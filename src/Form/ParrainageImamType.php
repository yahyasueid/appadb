<?php

namespace App\Form;

use App\Entity\Parrainage;
use App\Entity\ParrainageImam;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParrainageImamType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomComplet')
            ->add('cin')
            ->add('dateNaissance')
            ->add('lieuNaissance')
            ->add('genre')
            ->add('metier')
            ->add('institutionFormation')
            ->add('diplome')
            ->add('specialite')
            ->add('anneeObtentionDiplome')
            ->add('dateDebutFonction')
            ->add('experiences')
            ->add('competences')
            ->add('typeEmploi')
            ->add('fonctionActuelle')
            ->add('revenuMensuel')
            ->add('revenuFamilleTotal')
            ->add('situationSociale')
            ->add('nbGarcons')
            ->add('nbFilles')
            ->add('nombrePersonnesCharge')
            ->add('adresse')
            ->add('besoins')
            ->add('telephone')
            ->add('telephone2')
            ->add('photo')
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
            'data_class' => ParrainageImam::class,
        ]);
    }
}
