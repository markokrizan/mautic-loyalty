<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormFieldTelType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'international',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.form.field.type.tel.international',
                'data'  => isset($options['data']['international']) ? $options['data']['international'] : false,
            ]
        );

        $builder->add(
            'international_validationmsg',
            TextType::class,
            [
                'label'      => 'mautic.form.field.form.validationmsg',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => $this->translator->trans('mautic.core.form.default').': '.$this->translator->trans('mautic.form.submission.phone.invalid', [], 'validators'),
                    'data-show-on' => '{"formfield_validation_international_1": "checked"}',
                ],
                'required' => false,
            ]
        );
    }
}
