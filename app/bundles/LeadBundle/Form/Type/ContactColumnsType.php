<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Services\ContactColumnsDictionary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactColumnsType extends AbstractType
{
    public function __construct(private ContactColumnsDictionary $columnsDictionary)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices'    => array_flip($this->columnsDictionary->getFields()),
                'label'      => false,
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'multiple'   => true,
                'expanded'   => false,
                'attr'       => [
                    'class'         => 'form-control',
                ],
            ]
        );
    }

    /**
     * @return string|\Symfony\Component\Form\FormTypeInterface|null
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
