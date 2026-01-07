<?php

namespace App\Form;

use App\Entity\Guestbook;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GuestbookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Ton Prénom',
                'attr' => [
                    'class' => 'w-full px-4 py-2 rounded-xl bg-white border-2 border-gk-blue-sky focus:outline-none focus:ring-2 focus:ring-gk-yellow-sun',
                    'placeholder' => 'Ex: Sarah'
                ]
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Ton Message Magique',
                'attr' => [
                    'class' => 'w-full px-4 py-2 rounded-xl bg-white border-2 border-gk-blue-sky focus:outline-none focus:ring-2 focus:ring-gk-yellow-sun h-32',
                    'placeholder' => 'Écris quelque chose de gentil...'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Guestbook::class,
        ]);
    }
}
