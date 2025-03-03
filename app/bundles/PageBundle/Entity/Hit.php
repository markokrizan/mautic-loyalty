<?php

namespace Mautic\PageBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDevice;

class Hit
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \DateTimeInterface
     */
    private $dateHit;

    /**
     * @var \DateTimeInterface
     */
    private $dateLeft;

    private ?Page $page = null;

    /**
     * @var Redirect|null
     */
    private $redirect;

    /**
     * @var \Mautic\EmailBundle\Entity\Email|null
     */
    private $email;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead|null
     */
    private $lead;

    /**
     * @var \Mautic\CoreBundle\Entity\IpAddress
     */
    private $ipAddress;

    /**
     * @var string|null
     */
    private $country;

    /**
     * @var string|null
     */
    private $region;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $isp;

    /**
     * @var string|null
     */
    private $organization;

    /**
     * @var int
     */
    private $code;

    private $referer;

    private $url;

    /**
     * @var string|null
     */
    private $urlTitle;

    /**
     * @var string|null
     */
    private $userAgent;

    /**
     * @var string|null
     */
    private $remoteHost;

    /**
     * @var string|null
     */
    private $pageLanguage;

    /**
     * @var array<string>
     */
    private $browserLanguages = [];

    /**
     * @var string
     **/
    private $trackingId;

    /**
     * @var string|null
     */
    private $source;

    /**
     * @var int|null
     */
    private $sourceId;

    /**
     * @var array
     */
    private $query = [];

    /**
     * @var LeadDevice|null
     */
    private $device;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('page_hits')
            ->setCustomRepositoryClass('Mautic\PageBundle\Entity\HitRepository')
            ->addIndex(['tracking_id'], 'page_hit_tracking_search')
            ->addIndex(['code'], 'page_hit_code_search')
            ->addIndex(['source', 'source_id'], 'page_hit_source_search')
            ->addIndex(['date_hit', 'date_left'], 'date_hit_left_index')
            ->addIndexWithOptions(['url'], 'page_hit_url', ['lengths' => [0 => 128]]);

        $builder->addBigIntIdField();

        $builder->createField('dateHit', 'datetime')
            ->columnName('date_hit')
            ->build();

        $builder->createField('dateLeft', 'datetime')
            ->columnName('date_left')
            ->nullable()
            ->build();

        $builder->createManyToOne('page', 'Page')
            ->addJoinColumn('page_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('redirect', 'Redirect')
            ->addJoinColumn('redirect_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('email', 'Mautic\EmailBundle\Entity\Email')
            ->addJoinColumn('email_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addLead(true, 'SET NULL');

        $builder->addIpAddress();

        $builder->createField('country', 'string')
            ->nullable()
            ->build();

        $builder->createField('region', 'string')
            ->nullable()
            ->build();

        $builder->createField('city', 'string')
            ->nullable()
            ->build();

        $builder->createField('isp', 'string')
            ->nullable()
            ->build();

        $builder->createField('organization', 'string')
            ->nullable()
            ->build();

        $builder->addField('code', 'integer');

        $builder->createField('referer', 'text')
            ->nullable()
            ->build();

        $builder->createField('url', 'text')
            ->nullable()
            ->build();

        $builder->createField('urlTitle', 'string')
            ->columnName('url_title')
            ->nullable()
            ->build();

        $builder->createField('userAgent', 'text')
            ->columnName('user_agent')
            ->nullable()
            ->build();

        $builder->createField('remoteHost', 'string')
            ->columnName('remote_host')
            ->nullable()
            ->build();

        $builder->createField('pageLanguage', 'string')
            ->columnName('page_language')
            ->nullable()
            ->build();

        $builder->createField('browserLanguages', 'array')
            ->columnName('browser_languages')
            ->nullable()
            ->build();

        $builder->createField('trackingId', 'string')
            ->columnName('tracking_id')
            ->build();

        $builder->createField('source', 'string')
            ->nullable()
            ->build();

        $builder->createField('sourceId', 'integer')
            ->columnName('source_id')
            ->nullable()
            ->build();

        $builder->addNullableField('query', 'array');

        $builder->createManyToOne('device', 'Mautic\LeadBundle\Entity\LeadDevice')
            ->addJoinColumn('device_id', 'id', true, false, 'SET NULL')
            ->cascadePersist()
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('hit')
            ->addProperties(
                [
                    'id',
                    'dateHit',
                    'dateLeft',
                    'page',
                    'redirect',
                    'email',
                    'lead',
                    'ipAddress',
                    'country',
                    'region',
                    'city',
                    'isp',
                    'organization',
                    'code',
                    'referer',
                    'url',
                    'urlTitle',
                    'userAgent',
                    'remoteHost',
                    'pageLanguage',
                    'browserLanguages',
                    'trackingId',
                    'source',
                    'sourceId',
                    'query',
                ]
            )
            ->build();
    }

    /**
     * Get id.
     */
    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * Set dateHit.
     *
     * @param \DateTime $dateHit
     *
     * @return Hit
     */
    public function setDateHit($dateHit)
    {
        $this->dateHit = $dateHit;

        return $this;
    }

    /**
     * Get dateHit.
     *
     * @return \DateTimeInterface
     */
    public function getDateHit()
    {
        return $this->dateHit;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateLeft()
    {
        return $this->dateLeft;
    }

    /**
     * @param \DateTime $dateLeft
     *
     * @return Hit
     */
    public function setDateLeft($dateLeft)
    {
        $this->dateLeft = $dateLeft;

        return $this;
    }

    /**
     * Set country.
     *
     * @param string $country
     *
     * @return Hit
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set region.
     *
     * @param string $region
     *
     * @return Hit
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region.
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set city.
     *
     * @param string $city
     *
     * @return Hit
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set isp.
     *
     * @param string $isp
     *
     * @return Hit
     */
    public function setIsp($isp)
    {
        $this->isp = $isp;

        return $this;
    }

    /**
     * Get isp.
     *
     * @return string
     */
    public function getIsp()
    {
        return $this->isp;
    }

    /**
     * Set organization.
     *
     * @param string $organization
     *
     * @return Hit
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization.
     *
     * @return string
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set code.
     *
     * @param int $code
     *
     * @return Hit
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set referer.
     *
     * @param string $referer
     *
     * @return Hit
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get referer.
     *
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set url.
     *
     * @param string $url
     *
     * @return Hit
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url.
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set url title.
     *
     * @param string $urlTitle
     *
     * @return Hit
     */
    public function setUrlTitle($urlTitle)
    {
        $urlTitle       = mb_strlen($urlTitle) <= 191 ? $urlTitle : mb_substr($urlTitle, 0, 191);
        $this->urlTitle = $urlTitle;

        return $this;
    }

    /**
     * Get url title.
     *
     * @return string
     */
    public function getUrlTitle()
    {
        return $this->urlTitle;
    }

    /**
     * Set userAgent.
     *
     * @param string $userAgent
     *
     * @return Hit
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get userAgent.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * Set remoteHost.
     *
     * @param string $remoteHost
     *
     * @return Hit
     */
    public function setRemoteHost($remoteHost)
    {
        $this->remoteHost = $remoteHost;

        return $this;
    }

    /**
     * Get remoteHost.
     *
     * @return string
     */
    public function getRemoteHost()
    {
        return $this->remoteHost;
    }

    /**
     * Set page.
     *
     * @return Hit
     */
    public function setPage(Page $page = null)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @return ?Page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set ipAddress.
     *
     * @return Hit
     */
    public function setIpAddress(\Mautic\CoreBundle\Entity\IpAddress $ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress.
     *
     * @return \Mautic\CoreBundle\Entity\IpAddress
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param string $trackingId
     *
     * @return Page
     */
    public function setTrackingId($trackingId)
    {
        $this->trackingId = $trackingId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTrackingId()
    {
        return $this->trackingId;
    }

    /**
     * Set pageLanguage.
     *
     * @param string $pageLanguage
     *
     * @return Hit
     */
    public function setPageLanguage($pageLanguage)
    {
        $this->pageLanguage = $pageLanguage;

        return $this;
    }

    /**
     * Get pageLanguage.
     *
     * @return string
     */
    public function getPageLanguage()
    {
        return $this->pageLanguage;
    }

    /**
     * Set browserLanguages.
     *
     * @param array<string> $browserLanguages
     *
     * @return Hit
     */
    public function setBrowserLanguages($browserLanguages)
    {
        $this->browserLanguages = $browserLanguages;

        return $this;
    }

    /**
     * Get browserLanguages.
     *
     * @return array<string>
     */
    public function getBrowserLanguages()
    {
        return $this->browserLanguages;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @return Hit
     */
    public function setLead(Lead $lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string $source
     *
     * @return Hit
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @return int
     */
    public function getSourceId()
    {
        return $this->sourceId;
    }

    /**
     * @param int $sourceId
     *
     * @return Hit
     */
    public function setSourceId($sourceId)
    {
        $this->sourceId = (int) $sourceId;

        return $this;
    }

    /**
     * @return Redirect
     */
    public function getRedirect()
    {
        return $this->redirect;
    }

    /**
     * @return Hit
     */
    public function setRedirect(Redirect $redirect)
    {
        $this->redirect = $redirect;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail(Email $email): void
    {
        $this->email = $email;
    }

    /**
     * @return array
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param array $query
     *
     * @return Hit
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @return LeadDevice
     */
    public function getDeviceStat()
    {
        return $this->device;
    }

    /**
     * @return Hit
     */
    public function setDeviceStat(LeadDevice $device)
    {
        $this->device = $device;

        return $this;
    }
}
