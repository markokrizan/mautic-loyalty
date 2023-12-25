<?php

namespace MauticPlugin\LoyaltyBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Symfony\Component\HttpFoundation\Response;

class PrivateWebController extends AbstractFormController
{
    public function users(): Response
    {
        return $this->delegateView(
            array(
                'contentTemplate' => '@Loyalty/Users/users.html.twig'
            )
        );
    }
}
