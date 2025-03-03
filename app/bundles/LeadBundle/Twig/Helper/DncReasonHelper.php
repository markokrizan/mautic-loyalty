<?php

namespace Mautic\LeadBundle\Twig\Helper;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Convert DNC reason ID to text.
 */
final class DncReasonHelper
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * Convert DNC reason ID to text.
     *
     * @param int $reasonId
     *
     * @return string
     *
     * @throws UnknownDncReasonException
     */
    public function toText($reasonId)
    {
        switch ($reasonId) {
            case DoNotContact::IS_CONTACTABLE:
                $reasonKey = 'mautic.lead.event.donotcontact_contactable';
                break;
            case DoNotContact::UNSUBSCRIBED:
                $reasonKey = 'mautic.lead.event.donotcontact_unsubscribed';
                break;
            case DoNotContact::BOUNCED:
                $reasonKey = 'mautic.lead.event.donotcontact_bounced';
                break;
            case DoNotContact::MANUAL:
                $reasonKey = 'mautic.lead.event.donotcontact_manual';
                break;
            default:
                throw new UnknownDncReasonException(sprintf("Unknown DNC reason ID '%c'", $reasonId));
        }

        return $this->translator->trans($reasonKey);
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName(): string
    {
        return 'lead_dnc_reason';
    }
}
