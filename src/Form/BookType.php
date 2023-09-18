<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Author;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bkName', TextType::class, [
                'required' => false
            ])
            ->add('authors', EntityType::class, [
                'class' => Author::class,
                'choice_label' => 'athr_name',
                'expanded' => true,
                'multiple' => true
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Resim dosyası yükle',
                'mapped' => true,

                'required' => false,

                /* 'constraints' => [
                    new Image([
                        'maxSize' => '1024k',
                    ])
                ], */
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
