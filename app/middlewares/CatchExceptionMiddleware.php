<?php

namespace Mautic\Middleware;

use Mautic\CoreBundle\ErrorHandler\ErrorHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CatchExceptionMiddleware implements HttpKernelInterface, PrioritizedMiddlewareInterface
{
    public const PRIORITY = 100;

    /**
     * @var HttpKernelInterface
     */
    protected $app;

    public function __construct(HttpKernelInterface $app)
    {
        $this->app = $app;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        $content = 'The site is currently offline due to encountering an error. If the problem persists, please contact the system administrator. System administrators, check server logs for errors.';
        try {
            $response = $this->app->handle($request, $type, $catch);

            if ($response instanceof Response) {
                return $response;
            }
        } catch (\Exception $exception) {
            $content = ErrorHandler::getHandler()->handleException($exception, true);
        }

        return new Response($content, 500);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return self::PRIORITY;
    }
}
