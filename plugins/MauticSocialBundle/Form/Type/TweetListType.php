<?php

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TweetListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'modal_route'         => 'mautic_tweet_action',
                'modal_header'        => 'mautic.integration.Twitter.new.tweet',
                'model'               => 'social.tweet',
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => function (Options $options): array {
                    return [
                        'type'   => 'tweet',
                        'filter' => '$data',
                        'limit'  => 0,
                        'start'  => 0,
                    ];
                },
                'ajax_lookup_action' => function (Options $options) {
                    return 'mauticSocial:getLookupChoiceList';
                },
                'multiple' => true,
                'required' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return EntityLookupType::class;
    }
}
