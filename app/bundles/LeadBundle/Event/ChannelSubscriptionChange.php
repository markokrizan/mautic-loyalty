<?php

namespace Mautic\LeadBundle\Event;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Contracts\EventDispatcher\Event;

class ChannelSubscriptionChange extends Event
{
    /**
     * @param string $channel
     */
    public function __construct(private Lead $lead, private $channel, private int $oldStatus, private int $newStatus)
    {
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return int
     */
    public function getOldStatus()
    {
        return $this->oldStatus;
    }

    /**
     * @return string
     */
    public function getOldStatusVerb()
    {
        return $this->getDncReasonVerb($this->oldStatus);
    }

    /**
     * @return int
     */
    public function getNewStatus()
    {
        return $this->newStatus;
    }

    /**
     * @return string
     */
    public function getNewStatusVerb()
    {
        return $this->getDncReasonVerb($this->newStatus);
    }

    /**
     * @return string
     */
    private function getDncReasonVerb($reason)
    {
        // use true matching or else 'foobar' == DoNotContact::IS_CONTACTABLE
        switch (true) {
            case DoNotContact::IS_CONTACTABLE === $reason:
                return 'contactable';
            case DoNotContact::BOUNCED === $reason:
                return 'bounced';
            case DoNotContact::MANUAL === $reason:
                return 'manual';
            default:
                return 'unsubscribed';
        }
    }
}
