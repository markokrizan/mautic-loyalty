<?php

namespace Mautic\NotificationBundle\EventListener;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\ChannelBundle\Model\MessageModel;
use Mautic\NotificationBundle\Form\Type\NotificationListType;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ChannelSubscriber implements EventSubscriberInterface
{
    public function __construct(private IntegrationHelper $integrationHelper)
    {
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ChannelEvents::ADD_CHANNEL => ['onAddChannel', 70],
        ];
    }

    public function onAddChannel(ChannelEvent $event): void
    {
        $integration = $this->integrationHelper->getIntegrationObject('OneSignal');

        if ($integration && $integration->getIntegrationSettings()->getIsPublished()) {
            $event->addChannel(
                'notification',
                [
                    MessageModel::CHANNEL_FEATURE => [
                        'campaignAction'             => 'notification.send_notification',
                        'campaignDecisionsSupported' => [
                            'page.pagehit',
                            'asset.download',
                            'form.submit',
                        ],
                        'lookupFormType' => NotificationListType::class,
                        'repository'     => \Mautic\NotificationBundle\Entity\Notification::class,
                        'lookupOptions'  => [
                            'mobile'  => false,
                            'desktop' => true,
                        ],
                    ],
                    ReportModel::CHANNEL_FEATURE => [
                        'table' => 'push_notifications',
                    ],
                ]
            );

            $supportedFeatures = $integration->getSupportedFeatures();

            if (in_array('mobile', $supportedFeatures)) {
                $event->addChannel(
                    'mobile_notification',
                    [
                        MessageModel::CHANNEL_FEATURE => [
                            'campaignAction'             => 'notification.send_mobile_notification',
                            'campaignDecisionsSupported' => [
                                'page.pagehit',
                                'asset.download',
                                'form.submit',
                            ],
                            'lookupFormType'             => NotificationListType::class,
                            'repository'                 => \Mautic\NotificationBundle\Entity\Notification::class,
                            'lookupOptions'              => [
                                'mobile'  => true,
                                'desktop' => false,
                            ],
                        ],
                    ]
                );
            }
        }
    }
}
