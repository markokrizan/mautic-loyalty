<?php

namespace Mautic\PluginBundle\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;
use Mautic\PluginBundle\Model\PluginModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

class IntegrationHelper
{
    private $integrations = [];

    private $available = [];

    private $byFeatureList = [];

    private $byPlugin = [];

    public function __construct(private ContainerInterface $container, protected EntityManager $em, protected PathsHelper $pathsHelper, protected BundleHelper $bundleHelper, protected CoreParametersHelper $coreParametersHelper, protected Environment $twig, protected PluginModel $pluginModel)
    {
    }

    /**
     * Get a list of integration helper classes.
     *
     * @param array|string $specificIntegrations
     * @param array        $withFeatures
     * @param bool         $alphabetical
     * @param int|null     $pluginFilter
     * @param bool|false   $publishedOnly
     *
     * @return array<AbstractIntegration>
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function getIntegrationObjects($specificIntegrations = null, $withFeatures = null, $alphabetical = false, $pluginFilter = null, $publishedOnly = false): array
    {
        // Build the service classes
        if (empty($this->available)) {
            $this->available = [];

            // Get currently installed integrations
            $integrationSettings = $this->getIntegrationSettings();

            // And we'll be scanning the addon bundles for additional classes, so have that data on standby
            $plugins = $this->bundleHelper->getPluginBundles();

            // Get a list of already installed integrations
            $integrationRepo = $this->em->getRepository(\Mautic\PluginBundle\Entity\Integration::class);
            // get a list of plugins for filter
            $installedPlugins = $this->pluginModel->getEntities(
                [
                    'hydration_mode' => 'hydrate_array',
                    'index'          => 'bundle',
                ]
            );

            $newIntegrations = [];

            // Scan the plugins for integration classes
            foreach ($plugins as $plugin) {
                // Do not list the integration if the bundle has not been "installed"
                if (!isset($plugin['bundle']) || !isset($installedPlugins[$plugin['bundle']])) {
                    continue;
                }

                if (is_dir($plugin['directory'].'/Integration')) {
                    $finder = new Finder();
                    $finder->files()->name('*Integration.php')->in($plugin['directory'].'/Integration')->ignoreDotFiles(true);

                    $id                  = $installedPlugins[$plugin['bundle']]['id'];
                    $this->byPlugin[$id] = [];
                    $pluginReference     = $this->em->getReference(\Mautic\PluginBundle\Entity\Plugin::class, $id);
                    $pluginNamespace     = str_replace('MauticPlugin', '', $plugin['bundle']);

                    foreach ($finder as $file) {
                        $integrationName = substr($file->getBaseName(), 0, -15);

                        if (!isset($integrationSettings[$integrationName])) {
                            $newIntegration = new Integration();
                            $newIntegration->setName($integrationName)
                                ->setPlugin($pluginReference);
                            $integrationSettings[$integrationName] = $newIntegration;
                            $integrationContainerKey               = strtolower("mautic.integration.{$integrationName}");

                            // Initiate the class in order to get the features supported
                            if ($this->container->has($integrationContainerKey)) {
                                $this->integrations[$integrationName] = $this->container->get($integrationContainerKey);

                                $features = $this->integrations[$integrationName]->getSupportedFeatures();
                                $newIntegration->setSupportedFeatures($features);

                                // Go ahead and stash it since it's built already
                                $this->integrations[$integrationName]->setIntegrationSettings($newIntegration);

                                $newIntegrations[] = $newIntegration;

                                unset($newIntegration);
                            }
                        }

                        /** @var \Mautic\PluginBundle\Entity\Integration $settings */
                        $settings                          = $integrationSettings[$integrationName];
                        $this->available[$integrationName] = [
                            'isPlugin'    => true,
                            'integration' => $integrationName,
                            'settings'    => $settings,
                            'namespace'   => $pluginNamespace,
                        ];

                        // Sort by feature and plugin for later
                        $features = $settings->getSupportedFeatures();
                        foreach ($features as $feature) {
                            if (!isset($this->byFeatureList[$feature])) {
                                $this->byFeatureList[$feature] = [];
                            }
                            $this->byFeatureList[$feature][] = $integrationName;
                        }
                        $this->byPlugin[$id][] = $integrationName;
                    }
                }
            }

            $coreIntegrationSettings = $this->getCoreIntegrationSettings();

            // Scan core bundles for integration classes
            foreach ($this->bundleHelper->getMauticBundles() as $coreBundle) {
                if (
                    // Skip plugin bundles
                    str_contains($coreBundle['relative'], 'app/bundles')
                    // Skip core bundles without an Integration directory
                    && is_dir($coreBundle['directory'].'/Integration')
                ) {
                    $finder = new Finder();
                    $finder->files()->name('*Integration.php')->in($coreBundle['directory'].'/Integration')->ignoreDotFiles(true);

                    $coreBundleNamespace = str_replace('Mautic', '', $coreBundle['bundle']);

                    foreach ($finder as $file) {
                        $integrationName = substr($file->getBaseName(), 0, -15);

                        if (!isset($coreIntegrationSettings[$integrationName])) {
                            $newIntegration = new Integration();
                            $newIntegration->setName($integrationName);
                            $integrationSettings[$integrationName] = $newIntegration;

                            $integrationContainerKey = strtolower("mautic.integration.{$integrationName}");

                            // Initiate the class in order to get the features supported
                            if ($this->container->has($integrationContainerKey)) {
                                $this->integrations[$integrationName] = $this->container->get($integrationContainerKey);
                                $features                             = $this->integrations[$integrationName]->getSupportedFeatures();
                                $newIntegration->setSupportedFeatures($features);

                                // Go ahead and stash it since it's built already
                                $this->integrations[$integrationName]->setIntegrationSettings($newIntegration);

                                $newIntegrations[] = $newIntegration;
                            } else {
                                continue;
                            }
                        }

                        /** @var \Mautic\PluginBundle\Entity\Integration $settings */
                        $settings                          = isset($coreIntegrationSettings[$integrationName]) ? $coreIntegrationSettings[$integrationName] : $newIntegration;
                        $this->available[$integrationName] = [
                            'isPlugin'    => false,
                            'integration' => $integrationName,
                            'settings'    => $settings,
                            'namespace'   => $coreBundleNamespace,
                        ];
                    }
                }
            }

            // Save newly found integrations
            if (!empty($newIntegrations)) {
                $integrationRepo->saveEntities($newIntegrations);
                unset($newIntegrations);
            }
        }

        // Ensure appropriate formats
        if (null !== $specificIntegrations && !is_array($specificIntegrations)) {
            $specificIntegrations = [$specificIntegrations];
        }

        if (null !== $withFeatures && !is_array($withFeatures)) {
            $withFeatures = [$withFeatures];
        }

        // Build the integrations wanted
        if (!empty($pluginFilter)) {
            // Filter by plugin
            $filteredIntegrations = $this->byPlugin[$pluginFilter];
        } elseif (!empty($specificIntegrations)) {
            // Filter by specific integrations
            $filteredIntegrations = $specificIntegrations;
        } else {
            // All services by default
            $filteredIntegrations = array_keys($this->available);
        }

        // Filter by features
        if (!empty($withFeatures)) {
            $integrationsWithFeatures = [];
            foreach ($withFeatures as $feature) {
                if (isset($this->byFeatureList[$feature])) {
                    $integrationsWithFeatures = $integrationsWithFeatures + $this->byFeatureList[$feature];
                }
            }

            $filteredIntegrations = array_intersect($filteredIntegrations, $integrationsWithFeatures);
        }

        $returnServices = [];

        // Build the classes if not already
        foreach ($filteredIntegrations as $integrationName) {
            if (!isset($this->available[$integrationName]) || ($publishedOnly && !$this->available[$integrationName]['settings']->isPublished())) {
                continue;
            }

            if (!isset($this->integrations[$integrationName])) {
                $integration             = $this->available[$integrationName];
                $integrationContainerKey = strtolower("mautic.integration.{$integrationName}");

                if ($this->container->has($integrationContainerKey)) {
                    $this->integrations[$integrationName] = $this->container->get($integrationContainerKey);
                    $this->integrations[$integrationName]->setIntegrationSettings($integration['settings']);
                }
            }

            if (isset($this->integrations[$integrationName])) {
                $returnServices[$integrationName] = $this->integrations[$integrationName];
            }
        }

        foreach ($returnServices as $key => $value) {
            if (!$value) {
                unset($returnServices[$key]);
            }
        }

        if (empty($alphabetical)) {
            // Sort by priority
            uasort($returnServices, function ($a, $b): int {
                $aP = (int) $a->getPriority();
                $bP = (int) $b->getPriority();

                if ($aP === $bP) {
                    return 0;
                }

                return ($aP < $bP) ? -1 : 1;
            });
        } else {
            // Sort by display name
            uasort($returnServices, function ($a, $b): int {
                $aName = $a->getDisplayName();
                $bName = $b->getDisplayName();

                return strcasecmp($aName, $bName);
            });
        }

        return $returnServices;
    }

    /**
     * Get a single integration object.
     *
     * @return AbstractIntegration|false
     */
    public function getIntegrationObject($name)
    {
        $integrationObjects = $this->getIntegrationObjects($name);

        return (isset($integrationObjects[$name])) ? $integrationObjects[$name] : false;
    }

    /**
     * Gets a count of integrations.
     */
    public function getIntegrationCount($plugin): int
    {
        if (!is_array($plugin)) {
            $plugins = $this->coreParametersHelper->get('plugin.bundles');
            if (array_key_exists($plugin, $plugins)) {
                $plugin = $plugins[$plugin];
            } else {
                // It doesn't exist so return 0

                return 0;
            }
        }

        if (is_dir($plugin['directory'].'/Integration')) {
            $finder = new Finder();
            $finder->files()->name('*Integration.php')->in($plugin['directory'].'/Integration')->ignoreDotFiles(true);

            return iterator_count($finder);
        }

        return 0;
    }

    /**
     * Returns popular social media services and regex URLs for parsing purposes.
     *
     * @param bool $find If true, array of regexes to find a handle will be returned;
     *                   If false, array of URLs with a placeholder of %handle% will be returned
     *
     * @return array
     *
     * @todo Extend this method to allow plugins to add URLs to these arrays
     */
    public function getSocialProfileUrlRegex($find = true)
    {
        if ($find) {
            // regex to find a match
            return [
                'twitter'  => "/twitter.com\/(.*?)($|\/)/",
                'facebook' => [
                    "/facebook.com\/(.*?)($|\/)/",
                    "/fb.me\/(.*?)($|\/)/",
                ],
                'linkedin'  => "/linkedin.com\/in\/(.*?)($|\/)/",
                'instagram' => "/instagram.com\/(.*?)($|\/)/",
                'pinterest' => "/pinterest.com\/(.*?)($|\/)/",
                'klout'     => "/klout.com\/(.*?)($|\/)/",
                'youtube'   => [
                    "/youtube.com\/user\/(.*?)($|\/)/",
                    "/youtu.be\/user\/(.*?)($|\/)/",
                ],
                'flickr' => "/flickr.com\/photos\/(.*?)($|\/)/",
                'skype'  => "/skype:(.*?)($|\?)/",
            ];
        } else {
            // populate placeholder
            return [
                'twitter'    => 'https://twitter.com/%handle%',
                'facebook'   => 'https://facebook.com/%handle%',
                'linkedin'   => 'https://linkedin.com/in/%handle%',
                'instagram'  => 'https://instagram.com/%handle%',
                'pinterest'  => 'https://pinterest.com/%handle%',
                'klout'      => 'https://klout.com/%handle%',
                'youtube'    => 'https://youtube.com/user/%handle%',
                'flickr'     => 'https://flickr.com/photos/%handle%',
                'skype'      => 'skype:%handle%?call',
            ];
        }
    }

    /**
     * Get array of integration entities.
     *
     * @return mixed
     */
    public function getIntegrationSettings()
    {
        return $this->em->getRepository(\Mautic\PluginBundle\Entity\Integration::class)->getIntegrations();
    }

    public function getCoreIntegrationSettings()
    {
        return $this->em->getRepository(\Mautic\PluginBundle\Entity\Integration::class)->getCoreIntegrations();
    }

    /**
     * Get the user's social profile data from cache or integrations if indicated.
     *
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $fields
     * @param bool                           $refresh
     * @param string                         $specificIntegration
     * @param bool                           $persistLead
     * @param bool                           $returnSettings
     *
     * @return array
     */
    public function getUserProfiles($lead, $fields = [], $refresh = false, $specificIntegration = null, $persistLead = true, $returnSettings = false)
    {
        $socialCache     = $lead->getSocialCache();
        $featureSettings = [];
        if ($refresh) {
            // regenerate from integrations
            $now = new DateTimeHelper();

            // check to see if there are social profiles activated
            $socialIntegrations = $this->getIntegrationObjects($specificIntegration, ['public_profile', 'public_activity']);

            /* @var \MauticPlugin\MauticSocialBundle\Integration\SocialIntegration $sn */
            foreach ($socialIntegrations as $integration => $sn) {
                $settings        = $sn->getIntegrationSettings();
                $features        = $settings->getSupportedFeatures();
                $identifierField = $this->getUserIdentifierField($sn, $fields);

                if ($returnSettings) {
                    $featureSettings[$integration] = $settings->getFeatureSettings();
                }

                if ($identifierField && $settings->isPublished()) {
                    $profile = (!isset($socialCache[$integration])) ? [] : $socialCache[$integration];

                    // clear the cache
                    unset($profile['profile'], $profile['activity']);

                    if (in_array('public_profile', $features) && $sn->isAuthorized()) {
                        $sn->getUserData($identifierField, $profile);
                    }

                    if (in_array('public_activity', $features) && $sn->isAuthorized()) {
                        $sn->getPublicActivity($identifierField, $profile);
                    }

                    if (!empty($profile['profile']) || !empty($profile['activity'])) {
                        if (!isset($socialCache[$integration])) {
                            $socialCache[$integration] = [];
                        }

                        $socialCache[$integration]['profile']     = (!empty($profile['profile'])) ? $profile['profile'] : [];
                        $socialCache[$integration]['activity']    = (!empty($profile['activity'])) ? $profile['activity'] : [];
                        $socialCache[$integration]['lastRefresh'] = $now->toUtcString();
                    }
                } elseif (isset($socialCache[$integration])) {
                    // integration is now not applicable
                    unset($socialCache[$integration]);
                }
            }

            if ($persistLead && !empty($socialCache)) {
                $lead->setSocialCache($socialCache);
                $this->em->getRepository(\Mautic\LeadBundle\Entity\Lead::class)->saveEntity($lead);
            }
        } elseif ($returnSettings) {
            $socialIntegrations = $this->getIntegrationObjects($specificIntegration, ['public_profile', 'public_activity']);
            foreach ($socialIntegrations as $integration => $sn) {
                $settings                      = $sn->getIntegrationSettings();
                $featureSettings[$integration] = $settings->getFeatureSettings();
            }
        }

        if ($specificIntegration) {
            return ($returnSettings) ? [[$specificIntegration => $socialCache[$specificIntegration]], $featureSettings]
                : [$specificIntegration => $socialCache[$specificIntegration]];
        }

        return ($returnSettings) ? [$socialCache, $featureSettings] : $socialCache;
    }

    /**
     * @param bool $integration
     *
     * @return array
     */
    public function clearIntegrationCache($lead, $integration = false)
    {
        $socialCache = $lead->getSocialCache();
        if (!empty($integration)) {
            unset($socialCache[$integration]);
        } else {
            $socialCache = [];
        }
        $lead->setSocialCache($socialCache);
        $this->em->getRepository(\Mautic\LeadBundle\Entity\Lead::class)->saveEntity($lead);

        return $socialCache;
    }

    /**
     * Gets an array of the HTML for share buttons.
     */
    public function getShareButtons()
    {
        static $shareBtns = [];

        if (empty($shareBtns)) {
            $socialIntegrations = $this->getIntegrationObjects(null, ['share_button'], true);

            /**
             * @var string                                               $integration
             * @var \Mautic\PluginBundle\Integration\AbstractIntegration $details
             */
            foreach ($socialIntegrations as $integration => $details) {
                /** @var \Mautic\PluginBundle\Entity\Integration $settings */
                $settings = $details->getIntegrationSettings();

                $featureSettings = $settings->getFeatureSettings();
                $apiKeys         = $details->decryptApiKeys($settings->getApiKeys());
                $plugin          = $settings->getPlugin();
                $shareSettings   = isset($featureSettings['shareButton']) ? $featureSettings['shareButton'] : [];

                // add the api keys for use within the share buttons
                $shareSettings['keys']   = $apiKeys;
                $shareBtns[$integration] = $this->twig->render($plugin->getBundle()."/Integration/$integration:share.html.twig", [
                    'settings' => $shareSettings,
                ]);
            }
        }

        return $shareBtns;
    }

    /**
     * Loops through field values available and finds the field the integration needs to obtain the user.
     *
     * @return bool
     */
    public function getUserIdentifierField($integrationObject, $fields)
    {
        $identifierField = $integrationObject->getIdentifierFields();
        $identifier      = (is_array($identifierField)) ? [] : false;
        $matchFound      = false;

        $findMatch = function ($f, $fields) use (&$identifierField, &$identifier, &$matchFound): void {
            if (is_array($identifier)) {
                // there are multiple fields the integration can identify by
                foreach ($identifierField as $idf) {
                    $value = (is_array($fields[$f]) && isset($fields[$f]['value'])) ? $fields[$f]['value'] : $fields[$f];

                    if (!in_array($value, $identifier) && str_contains($f, $idf)) {
                        $identifier[$f] = $value;
                        if (count($identifier) === count($identifierField)) {
                            // found enough matches so break
                            $matchFound = true;
                            break;
                        }
                    }
                }
            } elseif ($identifierField === $f || str_contains($f, $identifierField)) {
                $matchFound = true;
                $identifier = (is_array($fields[$f])) ? $fields[$f]['value'] : $fields[$f];
            }
        };

        $groups = ['core', 'social', 'professional', 'personal'];
        $keys   = array_keys($fields);
        if (0 !== count(array_intersect($groups, $keys)) && count($keys) <= 4) {
            // fields are group
            foreach ($fields as $groupFields) {
                $availableFields = array_keys($groupFields);
                foreach ($availableFields as $f) {
                    $findMatch($f, $groupFields);

                    if ($matchFound) {
                        break;
                    }
                }
            }
        } else {
            $availableFields = array_keys($fields);
            foreach ($availableFields as $f) {
                $findMatch($f, $fields);

                if ($matchFound) {
                    break;
                }
            }
        }

        return $identifier;
    }

    /**
     * Get the path to the integration's icon relative to the site root.
     *
     * @return string
     */
    public function getIconPath($integration)
    {
        $systemPath  = $this->pathsHelper->getSystemPath('root');
        $bundlePath  = $this->pathsHelper->getSystemPath('bundles');
        $pluginPath  = $this->pathsHelper->getSystemPath('plugins');
        $genericIcon = $bundlePath.'/PluginBundle/Assets/img/generic.png';

        if (is_array($integration)) {
            // A bundle so check for an icon
            $icon = $pluginPath.'/'.$integration['bundle'].'/Assets/img/icon.png';
        } elseif ($integration instanceof Plugin) {
            // A bundle so check for an icon
            $icon = $pluginPath.'/'.$integration->getBundle().'/Assets/img/icon.png';
        } elseif ($integration instanceof UnifiedIntegrationInterface) {
            return $integration->getIcon();
        }

        if (file_exists($systemPath.'/'.$icon)) {
            return $icon;
        }

        return $genericIcon;
    }
}
