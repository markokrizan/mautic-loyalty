<?php

namespace Mautic\ChannelBundle\Helper;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelEvent;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChannelListHelper
{
    /**
     * @var array<string,string>
     */
    private array $channels = [];

    /**
     * @var array<string,string[]>
     */
    private array $featureChannels = [];

    public function __construct(private EventDispatcherInterface $dispatcher, private Translator $translator)
    {
    }

    /**
     * Get contact channels.
     */
    public function getChannelList(): array
    {
        $channels = [];
        foreach ($this->getChannels() as $channel => $details) {
            $channelName            = isset($details['label']) ? $this->translator->trans($details['label']) : $this->getChannelLabel($channel);
            $channels[$channelName] = $channel;
        }

        return $channels;
    }

    /**
     * @param bool $listOnly
     *
     * @return array
     */
    public function getFeatureChannels($features, $listOnly = false)
    {
        $this->setupChannels();

        if (!is_array($features)) {
            $features = [$features];
        }
        $channels = [];
        foreach ($features as $feature) {
            $featureChannels = (isset($this->featureChannels[$feature])) ? $this->featureChannels[$feature] : [];
            $returnChannels  = [];
            foreach ($featureChannels as $channel => $details) {
                if (!isset($details['label'])) {
                    $featureChannels[$channel]['label'] = $this->getChannelLabel($channel);
                }

                if ($listOnly) {
                    $returnChannels[$featureChannels[$channel]['label']] = $channel;
                } else {
                    $returnChannels[$channel] = $featureChannels[$channel];
                }
            }
            unset($featureChannels);
            $channels[$feature] = $returnChannels;
        }

        if (1 === count($features)) {
            $channels = $channels[$features[0]];
        }

        return $channels;
    }

    /**
     * @return array
     */
    public function getChannels()
    {
        $this->setupChannels();

        return $this->channels;
    }

    /**
     * @return string
     */
    public function getChannelLabel($channel)
    {
        switch (true) {
            case $this->translator->hasId('mautic.channel.'.$channel):
                return $this->translator->trans('mautic.channel.'.$channel);
            case $this->translator->hasId('mautic.'.$channel.'.'.$channel):
                return $this->translator->trans('mautic.'.$channel.'.'.$channel);
            default:
                return ucfirst($channel);
        }
    }

    public function getName(): string
    {
        return 'chanel';
    }

    /**
     * Setup channels.
     *
     * Done this way to avoid a circular dependency error with LeadModel
     */
    private function setupChannels(): void
    {
        if (!empty($this->channels)) {
            return;
        }

        $event                 = $this->dispatcher->dispatch(new ChannelEvent(), ChannelEvents::ADD_CHANNEL);
        $this->channels        = $event->getChannelConfigs();
        $this->featureChannels = $event->getFeatureChannels();
        unset($event);
    }
}
