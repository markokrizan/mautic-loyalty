<?php

namespace MauticPlugin\MauticFocusBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\PageBundle\Helper\TrackingHelper;
use MauticPlugin\MauticFocusBundle\FocusEvents;
use MauticPlugin\MauticFocusBundle\Form\Type\FocusShowType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    /**
     * @var TrackingHelper
     */
    private $trackingHelper;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(TrackingHelper $trackingHelper, RouterInterface $router)
    {
        $this->trackingHelper = $trackingHelper;
        $this->router         = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD       => ['onCampaignBuild', 0],
            FocusEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $action = [
            'label'                  => 'mautic.focus.campaign.event.show_focus',
            'description'            => 'mautic.focus.campaign.event.show_focus_descr',
            'eventName'              => FocusEvents::ON_CAMPAIGN_TRIGGER_ACTION,
            'formType'               => FocusShowType::class,
            'formTheme'              => '@MauticFocus/FormTheme/FocusShowList/focusshow_list_row.html.twig',
            'formTypeOptions'        => ['update_select' => 'campaignevent_properties_focus'],
            'connectionRestrictions' => [
                'anchor' => [
                    'decision.inaction',
                ],
                'source' => [
                    'decision' => [
                        'page.pagehit',
                    ],
                ],
            ],
        ];
        $event->addAction('focus.show', $action);
    }

    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $focusId = (int) $event->getConfig()['focus'];
        if (!$focusId) {
            return $event->setResult(false);
        }
        $values                 = [];
        $values['focus_item'][] = ['id' => $focusId, 'js' => $this->router->generate('mautic_focus_generate', ['id' => $focusId], UrlGeneratorInterface::ABSOLUTE_URL)];
        $this->trackingHelper->updateSession($values);

        return $event->setResult(true);
    }
}
