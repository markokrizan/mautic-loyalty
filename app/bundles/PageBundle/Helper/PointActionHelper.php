<?php

namespace Mautic\PageBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\PageBundle\Entity\Page;

class PointActionHelper
{
    /**
     * @param MauticFactory $factory
     */
    public static function validatePageHit($factory, $eventDetails, $action): bool
    {
        $pageHit = $eventDetails->getPage();

        if ($pageHit instanceof Page) {
            /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
            $pageModel               = $factory->getModel('page');
            list($parent, $children) = $pageHit->getVariants();
            // use the parent (self or configured parent)
            $pageHitId = $parent->getId();
        } else {
            $pageHitId = 0;
        }

        // If no pages are selected, the pages array does not exist
        if (isset($action['properties']['pages'])) {
            $limitToPages = $action['properties']['pages'];
        }

        if (!empty($limitToPages) && !in_array($pageHitId, $limitToPages)) {
            // no points change
            return false;
        }

        return true;
    }

    /**
     * @param MauticFactory $factory
     */
    public static function validateUrlHit($factory, $eventDetails, $action): bool
    {
        $changePoints = [];
        $url          = $eventDetails->getUrl();
        $limitToUrl   = html_entity_decode(trim($action['properties']['page_url']));

        if (!$limitToUrl || !fnmatch($limitToUrl, $url)) {
            // no points change
            return false;
        }

        $hitRepository = $factory->getEntityManager()->getRepository(\Mautic\PageBundle\Entity\Hit::class);
        $lead          = $eventDetails->getLead();
        $urlWithSqlWC  = str_replace('*', '%', $limitToUrl);

        if (isset($action['properties']['first_time']) && true === $action['properties']['first_time']) {
            $hitStats = $hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
            if (isset($hitStats['count']) && $hitStats['count']) {
                $changePoints['first_time'] = false;
            } else {
                $changePoints['first_time'] = true;
            }
        }
        $now       = new \DateTime();
        $latestHit = $hitRepository->getLatestHit(['leadId' => $lead->getId(), $urlWithSqlWC, 'second_to_last' => $eventDetails->getId()]);

        if ($action['properties']['accumulative_time']) {
            if (!isset($hitStats)) {
                $hitStats = $hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
            }

            if (isset($hitStats['sum'])) {
                if ($action['properties']['accumulative_time'] <= $hitStats['sum']) {
                    $changePoints['accumulative_time'] = true;
                } else {
                    $changePoints['accumulative_time'] = false;
                }
            } else {
                $changePoints['accumulative_time'] = false;
            }
        }
        if ($action['properties']['page_hits']) {
            if (!isset($hitStats)) {
                $hitStats = $hitRepository->getDwellTimesForUrl($urlWithSqlWC, ['leadId' => $lead->getId()]);
            }
            if (isset($hitStats['count']) && $hitStats['count'] >= $action['properties']['page_hits']) {
                $changePoints['page_hits'] = true;
            } else {
                $changePoints['page_hits'] = false;
            }
        }
        if ($action['properties']['returns_within']) {
            if ($now->getTimestamp() - $latestHit->getTimestamp() <= $action['properties']['returns_within']) {
                $changePoints['returns_within'] = true;
            } else {
                $changePoints['returns_within'] = false;
            }
        }
        if ($action['properties']['returns_after']) {
            if ($now->getTimestamp() - $latestHit->getTimestamp() >= $action['properties']['returns_after']) {
                $changePoints['returns_after'] = true;
            } else {
                $changePoints['returns_after'] = false;
            }
        }

        // return true only if all configured options are true
        return !in_array(false, $changePoints);
    }
}
