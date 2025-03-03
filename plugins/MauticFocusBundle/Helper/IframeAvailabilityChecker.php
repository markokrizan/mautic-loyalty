<?php

namespace MauticPlugin\MauticFocusBundle\Helper;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Check if URL can be displayed via IFRAME.
 */
class IframeAvailabilityChecker
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function check(string $url, string $currentScheme): JsonResponse
    {
        $response        = new JsonResponse();
        $responseContent = [
            'status'       => 0,
            'errorMessage' => '',
        ];

        if ($this->checkProtocolMismatch($url, $currentScheme)) {
            $responseContent['errorMessage'] = $this->translator->trans(
                'mautic.focus.protocol.mismatch',
                [
                    '%url%' => str_replace('http://', 'https://', $url),
                ]);
        } else {
            $client = HttpClient::create([
                'headers' => [
                    'User-Agent' => 'Mautic',
                ],
            ]);

            try {
                /** @var ResponseInterface $httpResponse */
                $httpResponse = $client->request(Request::METHOD_GET, $url);

                $blockingHeader = $this->checkHeaders($httpResponse->getHeaders(false));

                if ('' !== $blockingHeader) {
                    $responseContent['errorMessage'] = $this->translator->trans(
                        'mautic.focus.blocking.iframe.header',
                        [
                            '%url%'    => $url,
                            '%header%' => $blockingHeader,
                        ]
                    );
                }
            } catch (\Exception $e) {
                // Transport exception with SSL cert for example
                $responseContent['errorMessage'] = $e->getMessage();
            }
        }

        if ('' === $responseContent['errorMessage'] && Response::HTTP_OK === $httpResponse->getStatusCode()) {
            $responseContent['status'] = 1;
        }

        $response->setData($responseContent);

        return $response;
    }

    /**
     * Iframe doesn't allow cross protocol requests.
     */
    private function checkProtocolMismatch(string $url, string $currentScheme): bool
    {
        // Mixed Content: The page at 'https://example.com' was loaded over HTTPS,
        // but requested an insecure frame 'http://target-example.com/'. This request has been blocked; the content
        // must be served over HTTPS.
        return 'https' === $currentScheme && str_starts_with($url, 'http://');
    }

    /**
     * @param array $headers Content of Symfony\Contracts\HttpClient\ResponseInterface::getHeaders()
     *
     * @return string Blocking header if problem found
     */
    private function checkHeaders(array $headers): string
    {
        $return = '';

        if ($this->headerContains($headers, 'x-frame-options')) {
            // @see https://stackoverflow.com/questions/31944552/iframe-refuses-to-display
            $return = 'x-frame-options: SAMEORIGIN';
        }

        if ($this->headerContains($headers, 'content-security-policy', "frame-ancestors 'self'")) {
            // https://seznam.cz
            // Refused to display 'https://www.seznam.cz/' in a frame because an ancestor violates the following
            // Content Security Policy directive: "frame-ancestors 'self'".
            // @see https://stackoverflow.com/questions/31944552/iframe-refuses-to-display
            $return = 'content-security-policy';
        }

        return $return;
    }

    private function headerContains(array $headers, string $name, string $content = null): bool
    {
        $headers = array_change_key_case($headers, CASE_LOWER);

        if (array_key_exists($name, $headers)) {
            if (null !== $content) {
                if (str_starts_with($headers[$name][0], $content)) {
                    return true;
                } else {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
