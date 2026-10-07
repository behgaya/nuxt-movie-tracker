<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Turns every error under /api into { statusCode, message } JSON, the shape the Nuxt app
 * already reads (e.data.message). Validation errors show the first violation's message.
 */
#[AsEventListener]
final class ApiExceptionListener
{
    public function __construct(
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();

        if (!$exception instanceof HttpExceptionInterface) {
            // Unexpected errors: keep Symfony's detailed debug page in dev, hide the details in prod
            if ($this->debug) {
                return;
            }
            $event->setResponse(new JsonResponse(['statusCode' => 500, 'message' => 'Something went wrong'], 500));

            return;
        }

        $message = $exception->getMessage();
        if ($exception->getPrevious() instanceof ValidationFailedException) {
            $message = $exception->getPrevious()->getViolations()->get(0)->getMessage();
        }

        $status = $exception->getStatusCode();
        $event->setResponse(new JsonResponse(['statusCode' => $status, 'message' => $message], $status, $exception->getHeaders()));
    }
}
