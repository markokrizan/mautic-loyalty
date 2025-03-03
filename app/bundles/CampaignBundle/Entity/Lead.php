<?php

namespace Mautic\CampaignBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class Lead
{
    /**
     * @var Campaign
     */
    private $campaign;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     **/
    private $dateAdded;

    /**
     * @var \DateTimeInterface
     */
    private $dateLastExited;

    /**
     * @var bool
     */
    private $manuallyRemoved = false;

    /**
     * @var bool
     */
    private $manuallyAdded = false;

    /**
     * @var int
     */
    private $rotation = 1;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('campaign_leads')
            ->setCustomRepositoryClass('Mautic\CampaignBundle\Entity\LeadRepository')
            ->addIndex(['date_added'], 'campaign_leads_date_added')
            ->addIndex(['date_last_exited'], 'campaign_leads_date_exited')
            ->addIndex(['campaign_id', 'manually_removed', 'lead_id', 'rotation'], 'campaign_leads');

        $builder->createManyToOne('campaign', 'Campaign')
            ->makePrimaryKey()
            ->inversedBy('leads')
            ->addJoinColumn('campaign_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->addDateAdded();

        $builder->createField('manuallyRemoved', 'boolean')
            ->columnName('manually_removed')
            ->build();

        $builder->createField('manuallyAdded', 'boolean')
            ->columnName('manually_added')
            ->build();

        $builder->addNamedField('dateLastExited', 'datetime', 'date_last_exited', true);

        $builder->addField('rotation', 'integer');
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('campaignLead')
            ->addListProperties(
                [
                    'dateAdded',
                    'manuallyRemoved',
                    'manuallyAdded',
                    'rotation',
                    'dateLastExited',
                ]
            )
            ->addProperties(
                [
                    'lead',
                    'campaign',
                ]
            )
            ->build();
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $date
     */
    public function setDateAdded($date): void
    {
        $this->dateAdded = $date;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(\Mautic\LeadBundle\Entity\Lead $lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    public function setCampaign(Campaign $campaign): void
    {
        $this->campaign = $campaign;
    }

    /**
     * @return bool
     */
    public function getManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @param bool $manuallyRemoved
     */
    public function setManuallyRemoved($manuallyRemoved): void
    {
        $this->manuallyRemoved = $manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function wasManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function getManuallyAdded()
    {
        return $this->manuallyAdded;
    }

    /**
     * @param bool $manuallyAdded
     */
    public function setManuallyAdded($manuallyAdded): void
    {
        $this->manuallyAdded = $manuallyAdded;
    }

    /**
     * @return bool
     */
    public function wasManuallyAdded()
    {
        return $this->manuallyAdded;
    }

    /**
     * @return int
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * @param int $rotation
     *
     * @return Lead
     */
    public function setRotation($rotation)
    {
        $this->rotation = (int) $rotation;

        return $this;
    }

    /**
     * @return $this
     */
    public function startNewRotation()
    {
        ++$this->rotation;
        $this->dateAdded = new \DateTime();

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateLastExited()
    {
        return $this->dateLastExited;
    }

    /**
     * @return Lead
     */
    public function setDateLastExited(\DateTime $dateLastExited = null)
    {
        $this->dateLastExited = $dateLastExited;

        return $this;
    }
}
