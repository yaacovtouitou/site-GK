<?php

namespace App\EventListener;

use App\Entity\User;
use App\Service\GamificationManager;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

#[AsEventListener(event: 'security.interactive_login')]
class LoginListener
{
    public function __construct(
        private GamificationManager $gamificationManager
    ) {}

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            $this->gamificationManager->updateLoginStreak($user);
        }
    }
}
