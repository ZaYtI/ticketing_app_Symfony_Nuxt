<?php

namespace App\EventListener;

use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Class JWTAuthenticationSuccessListener
 * @package App\EventListener
 */
class JWTAuthenticationSuccessListener
{
    /**
     * @var int
     */
    private $tokenLifetime;

    public function __construct(int $tokenLifetime)
    {
        $this->tokenLifetime = $tokenLifetime;
    }

    /**
     * Sets JWT as a cookie on successful authentication.
     * @param AuthenticationSuccessEvent $event
     * @throws Exception
     */
        public function onAuthenticationSuccess(AuthenticationSuccessEvent $event)
        {
            $token = $event->getData()['token'];
            $response = $event->getResponse();
        
            // Ajout du token dans un cookie
            $cookie = Cookie::create('jwt-token-ticket', $token)
                ->withSecure(false)
                ->withHttpOnly(true)
                ->withSameSite('Strict')
                ->withExpires(time() + 3600);
        
            $response->headers->setCookie($cookie);
        }
}
