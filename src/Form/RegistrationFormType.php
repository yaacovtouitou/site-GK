<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Section Parent
        $builder
            ->add('lastName', TextType::class, [
                'label' => 'Nom de Famille',
                'attr' => ['class' => 'w-full px-4 py-3 rounded-2xl bg-gray-100 border-2 border-transparent focus:border-gk-blue-sky focus:bg-white focus:outline-none transition shadow-inner'],
                'constraints' => [new NotBlank(message: 'Le nom est obligatoire')],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom du Parent',
                'attr' => ['class' => 'w-full px-4 py-3 rounded-2xl bg-gray-100 border-2 border-transparent focus:border-gk-blue-sky focus:bg-white focus:outline-none transition shadow-inner'],
                'constraints' => [new NotBlank(message: 'Le prénom est obligatoire')],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse Email',
                'attr' => ['class' => 'w-full px-4 py-3 rounded-2xl bg-gray-100 border-2 border-transparent focus:border-gk-blue-sky focus:bg-white focus:outline-none transition shadow-inner'],
                'constraints' => [new NotBlank(message: 'L\'email est obligatoire')],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'new-password', 'class' => 'w-full px-4 py-3 rounded-2xl bg-gray-100 border-2 border-transparent focus:border-gk-blue-sky focus:bg-white focus:outline-none transition shadow-inner'],
                'constraints' => [
                    new NotBlank(message: 'Entre un mot de passe !'),
                    new Length(min: 6, minMessage: 'Ton mot de passe doit faire au moins {{ limit }} caractères', max: 4096),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse Postale',
                'required' => false,
                'attr' => ['class' => 'w-full px-4 py-3 rounded-2xl bg-gray-100 border-2 border-transparent focus:border-gk-blue-sky focus:bg-white focus:outline-none transition shadow-inner'],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code Postal',
                'required' => false,
                'attr' => ['class' => 'w-full px-4 py-3 rounded-2xl bg-gray-100 border-2 border-transparent focus:border-gk-blue-sky focus:bg-white focus:outline-none transition shadow-inner'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['class' => 'w-full px-4 py-3 rounded-2xl bg-gray-100 border-2 border-transparent focus:border-gk-blue-sky focus:bg-white focus:outline-none transition shadow-inner'],
            ])

            // Section Enfants (Collection)
            ->add('children', CollectionType::class, [
                'entry_type' => ChildType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
