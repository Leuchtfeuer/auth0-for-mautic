<?php

namespace MauticPlugin\LeuchtfeuerAuth0Bundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\ArrayLinebreakTransformer;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    /**
     * @param array<mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'auth0_username',
            TextType::class,
            [
                'label'      => 'mautic.core.username',
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
                'label'      => 'mautic.core.firstname',
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
                'label'      => 'mautic.core.lastname',
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
                'label'      => 'mautic.core.position',
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
                'label'      => 'mautic.email.token.signature',
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
                'label'      => 'mautic.core.type.email',
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
                'label'      => 'mautic.core.timezone',
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
                'label'      => 'mautic.core.language',
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
            'auth0_role',
            TextType::class,
            [
                'label'      => 'plugin.auth0.integration.type_label.role_path',
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
            'multiple_roles',
            YesNoButtonGroupType::class,
            [
                'label'      => 'plugin.auth0.integration.type_label.multiple_roles',
                'label_attr' => [
                    'class' => 'control-label',
                ],
                'attr' => [
                    'class' => 'form-control',
                    'data'  => (bool) $options['data']['multiple_roles'],
                ],
                'required' => false,
            ]
        );
        $arrayLinebreakTransformer = new ArrayLinebreakTransformer();
        $builder->add(
            $builder->create(
                'rolemapping',
                TextareaType::class,
                [
                    'label'      => 'plugin.auth0.integration.type_label.rolemapping',
                    'label_attr' => [
                        'class' => 'control-label',
                    ],
                    'attr' => [
                        'class'        => 'form-control',
                        'tooltip'      => 'plugin.auth0.integration.type_label.rolemapping.tooltip',
                        'rows'         => 4,
                        'data-show-on' => '{"config_auth0config_multiple_roles_1":"checked"}',
                    ],
                    'required' => false,
                ]
            )->addViewTransformer($arrayLinebreakTransformer)
        );
    }

    public function getBlockPrefix(): string
    {
        return 'auth0config';
    }
}
