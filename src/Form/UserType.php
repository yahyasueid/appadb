<?php
// src/Form/UserType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            // ── Prénom ──
            ->add('prenom', TextType::class, [
                'label'       => 'form.prenom',
                'attr'        => ['placeholder' => 'form.prenom_placeholder'],
                'constraints' => [new NotBlank(message: 'form.prenom_required')],
            ])

            // ── Nom ──
            ->add('nom', TextType::class, [
                'label'       => 'form.nom',
                'attr'        => ['placeholder' => 'form.nom_placeholder'],
                'constraints' => [new NotBlank(message: 'form.nom_required')],
            ])

            // ── Email ──
            ->add('email', EmailType::class, [
                'label'       => 'form.email',
                'attr'        => ['placeholder' => 'form.email_placeholder'],
                'constraints' => [
                    new NotBlank(message: 'form.email_required'),
                    new Email(message: 'form.email_invalid'),
                ],
            ])

            // ── Téléphone (optionnel) ──
            ->add('telephone', TelType::class, [
                'label'    => 'form.telephone',
                'required' => false,
                'attr'     => ['placeholder' => 'form.telephone_placeholder'],
            ])

            // ── Poste (select) ──
            ->add('poste', ChoiceType::class, [
                'label'       => 'form.poste',
                'choices'     => User::getPostesChoices(),
                'placeholder' => 'form.poste_placeholder',
                'constraints' => [new NotBlank(message: 'form.poste_required')],
            ])

            // ── Mot de passe (2 champs) ──
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'required'        => !$isEdit,
                'first_options'   => [
                    'label' => $isEdit ? 'form.new_password' : 'form.password',
                    'attr'  => [
                        'placeholder' => $isEdit ? 'form.password_optional' : 'form.password_placeholder',
                    ],
                ],
                'second_options'  => [
                    'label' => 'form.password_confirm',
                    'attr'  => ['placeholder' => 'form.password_confirm_placeholder'],
                ],
                'invalid_message' => 'form.password_mismatch',
                'constraints'     => $isEdit ? [] : [
                    new NotBlank(message: 'form.password_required'),
                    new Length(min: 8, minMessage: 'form.password_min'),
                ],
            ])

            // ── Photo (upload) ──
            ->add('photoFile', FileType::class, [
                'label'       => 'form.photo',
                'mapped'      => false,
                'required'    => false,
                'constraints' => [
                    new Image(
                        maxSize: '2M',
                        maxSizeMessage: 'form.photo_max_size',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'form.photo_mime',
                    ),
                ],
            ])

            // ── Supprimer photo (checkbox) ──
            ->add('removePhoto', CheckboxType::class, [
                'label'    => 'form.remove_photo',
                'mapped'   => false,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit'    => false,
        ]);
    }
}
