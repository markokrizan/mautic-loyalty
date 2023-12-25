<?php

namespace MauticPlugin\LoyaltyBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractFormController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends AbstractFormController
{
    public function user(): Response
    {
        $user = [
            'id' => 1,
            'name' => 'Test'
        ];

        return new JsonResponse($user);
    }
}
