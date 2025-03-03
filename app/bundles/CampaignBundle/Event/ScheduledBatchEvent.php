<?php

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

class ScheduledBatchEvent extends AbstractLogCollectionEvent
{
    /**
     * ScheduledBatchEvent constructor.
     *
     * @param bool $isReschedule
     */
    public function __construct(AbstractEventAccessor $config, Event $event, ArrayCollection $logs, private $isReschedule = false)
    {
        parent::__construct($config, $event, $logs);
    }

    /**
     * @return ArrayCollection
     */
    public function getScheduled()
    {
        return $this->logs;
    }

    /**
     * @return bool
     */
    public function isReschedule()
    {
        return $this->isReschedule;
    }
}
