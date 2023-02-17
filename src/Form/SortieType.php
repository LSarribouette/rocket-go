<?php

namespace App\Form;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Ville;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom',
                TextType::class,
                [
                    'label' => "Nom de votre Evenement",
                    'attr' => [
                        'placeholder'=>"Visite du Musée de l'informatique"
                    ]
                ])
            ->add('dateDebut',
                type: DateTimeType::class,
                options: [
                    'label' => "Début de l'évênement",
                    'html5' => true,
                    'widget' => 'single_text'
                ])
            ->add('duree',IntegerType::class)
//             ->add('duree',
            /* TODO : handle la duree avec un intervaltype ce serait trop stylé
            DateIntervalType::class,
            [
                'widget'      => 'integer', // render a text field for each part
                // 'input'    => 'string',  // if you want the field to return a ISO 8601 string back to you
                // customize which text boxes are shown
                'with_years'  => false,
                'with_months' => false,
                'with_days'   => true,
                'with_hours'  => true,
                'with_minutes'  => true,
                'label' => "Durée de l'évênement",
                'labels' => [
                    'days' => 'Jours',
                    'hours' => 'Heures',
                    'minutes' => 'Minutes',
                ]
            ])
 */
            ->add('dateCloture',
                type: DateTimeType::class,
                options: [
                    'label' => "Date de Fin des Inscriptions",
                    'html5' => true,
                    'widget' => 'single_text'
                ])
            ->add('nbInscriptionsMax',
                NumberType::class,
                [
                    'label'=>'Combien de personnes peuvent s\'inscrire ?'
                ])
            ->add('descriptionInfos',
                TextareaType::class,
                [
                    'label' => 'Décrivez votre evenement !',
                    'required' => false,
                    'attr' => [
                        'placeholder'=>"Prévoir baskets et sandwichs ! N'oubliez pas d'envoyer le mail de confirmati"
                    ]
                ])
//on récup les villes ou se trouve le lieu de l'activité, on fera matcher plus tard les lieux proposé en fonction des villes
//TODO : atteindre ville
            /*
                        ->add('ville',
                            EntityType::class,
                            [
                                'label' => 'Dans quel ville ?',
                                "class"=>Ville::class,
                                "choice_label"=>"ville_id"
                            ])
            */
            //TODO : on veut pouvoir créer un lieu, on veut peut etre un bouton qui permette d'aller sur le form lieu pour ajouter un novueau lieu ?
            //TODO : les lieux prédéfinis a choisir dans la liste vont dépendre de la ville
            ->add('lieu',
                EntityType::class,
                [
                    'label' => 'Ou voulez vous sortir ?',
                    "class"=>Lieu::class,
                    "choice_label"=>"nom"
                ])

            ->add('urlPhoto',
                FileType::class,
                [
                    'label'=>'Ajouter une image ?',
                    'required' => false,
                    'constraints' => [
                        new File([
                            //'maxSize' => '1024k',
                            'mimeTypes' => [
                                'application/pdf',
                                'application/x-pdf',
                                'image/png',
                                'image/jpeg',
                                'image/webp',
                                'image/gif',
                            ],
                            'mimeTypesMessage' => 'Please upload a valid PDF document',
                        ])
                    ]
                ]
            )
            ->add('submit',
                type: SubmitType::class,
                options: [
                    'label'=>'Proposer la Sortie !'
                ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
