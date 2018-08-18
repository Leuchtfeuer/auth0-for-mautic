<?php

namespace MauticPlugin\MauticAuth0Bundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ConfigType
 *
 * @package MauticPlugin\MauticAuth0Bundle\Form\Type
 */
class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'auth0_username',
            TextType::class,
            [
                'label' => 'mautic.core.username',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'auth0_firstName',
            TextType::class,
            [
                'label' => 'mautic.core.firstname',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'auth0_lastName',
            TextType::class,
            [
                'label' => 'mautic.core.lastname',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'auth0_position',
            TextType::class,
            [
                'label' => 'mautic.core.position',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'auth0_signature',
            TextType::class,
            [
                'label' => 'mautic.email.token.signature',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'auth0_email',
            TextType::class,
            [
                'label' => 'mautic.core.type.email',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'auth0_timezone',
            TextType::class,
            [
                'label' => 'mautic.core.timezone',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'auth0_locale',
            TextType::class,
            [
                'label' => 'mautic.core.language',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'auth0config';
    }
}