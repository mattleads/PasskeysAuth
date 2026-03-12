<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

readonly class AuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(private UrlGeneratorInterface $urlGenerator) {}

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse|JsonResponse
    {
        if ($request->getContentTypeFormat() === 'json' || $request->isXmlHttpRequest()) {
            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => $exception->getMessageKey(),
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Store the error in the session
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
