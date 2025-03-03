<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Exception\EmailCouldNotBeSentException;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\PointBundle\Event\TriggerExecutedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailToUserSubscriber implements EventSubscriberInterface
{
    public function __construct(private SendEmailToUser $sendEmailToUser)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [EmailEvents::ON_SENT_EMAIL_TO_USER => ['onEmailToUser', 0]];
    }

    public function onEmailToUser(TriggerExecutedEvent $event)
    {
        $triggerEvent = $event->getTriggerEvent();
        $config       = $triggerEvent->getProperties();
        $lead         = $event->getLead();

        try {
            $this->sendEmailToUser->sendEmailToUsers($config, $lead);
            $event->setSucceded();
        } catch (EmailCouldNotBeSentException $e) {
            $event->setFailed();
        }

        return $event;
    }
}
