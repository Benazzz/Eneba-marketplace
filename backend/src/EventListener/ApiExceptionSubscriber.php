<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Turns HTTP errors on /api routes into the API's error format (PLAN.md §4):
 *   { "error": { "code": "...", "message": "...", "fields": { "email": "..." } } }
 *
 * Like an exception-handling middleware in ASP.NET Core. Symfony calls it whenever a controller
 * (or something before it, such as #[MapRequestPayload]) throws. Without it, Symfony would answer
 * with its own HTML/JSON error page.
 *
 * Only HttpExceptions are handled (they mean "the client did something wrong"). Real crashes (500)
 * are left to Symfony, so they keep their debug page in dev.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class ApiExceptionSubscriber
{
    /** Fixed code and message per status, so internal exception text never reaches the client. */
    private const ERRORS = [
        400 => ['bad_request', 'The request body is not valid JSON.'],
        401 => ['unauthorized', 'Please log in to continue.'],
        403 => ['forbidden', 'You are not allowed to do this.'],
        404 => ['not_found', 'Not found.'],
        405 => ['method_not_allowed', 'This method is not allowed here.'],
        415 => ['unsupported_media_type', 'Send the request body as JSON.'],
        422 => ['validation_failed', 'Please check the highlighted fields.'],
    ];

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $status = $exception->getStatusCode();
        [$code, $message] = self::ERRORS[$status] ?? ['http_error', 'The request could not be handled.'];
        $error = ['code' => $code, 'message' => $message];

        // #[MapRequestPayload] wraps the validator's result in the HttpException it throws.
        $previous = $exception->getPrevious();
        if ($previous instanceof ValidationFailedException) {
            $error['code'] = 'validation_failed';
            $error['message'] = 'Please check the highlighted fields.';
            $fields = $this->fieldErrors($previous);
            if ($fields !== []) {
                $error['fields'] = $fields;
            }
        }

        // Keep headers such as Allow (405) that the exception carries.
        $event->setResponse(new JsonResponse(['error' => $error], $status, $exception->getHeaders()));
    }

    /** @return array<string, string> property name → its first error message */
    private function fieldErrors(ValidationFailedException $exception): array
    {
        $fields = [];
        foreach ($exception->getViolations() as $violation) {
            $path = $violation->getPropertyPath();
            // One message per field is enough for a form. The first one is the most basic (e.g. "required").
            if ($path !== '' && !isset($fields[$path])) {
                $fields[$path] = (string) $violation->getMessage();
            }
        }

        return $fields;
    }
}
