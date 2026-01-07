<?php

namespace App\Form;

use App\Entity\Child;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChildType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom de l\'enfant',
                'attr' => ['class' => 'w-full px-4 py-2 rounded-xl border-2 border-gray-200 focus:border-gk-blue-sky focus:outline-none'],
                'constraints' => [new NotBlank(message: 'Le prénom est obligatoire')],
            ])
            ->add('motherName', TextType::class, [
                'label' => 'Prénom de la Mère',
                'required' => false,
                'attr' => ['class' => 'w-full px-4 py-2 rounded-xl border-2 border-gray-200 focus:border-gk-blue-sky focus:outline-none'],
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de Naissance',
                'widget' => 'single_text',
                'attr' => ['class' => 'w-full px-4 py-2 rounded-xl border-2 border-gray-200 focus:border-gk-blue-sky focus:outline-none'],
                'constraints' => [new NotBlank(message: 'La date de naissance est obligatoire')],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Child::class,
        ]);
    }
}
