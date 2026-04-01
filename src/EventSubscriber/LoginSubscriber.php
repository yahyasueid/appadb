<?php
// src/EventSubscriber/LoginSubscriber.php
// ============================================
// Enregistre la date de dernière connexion
// + Log dans le journal d'audit
// ============================================

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof User) {
            // Mettre à jour la dernière connexion
            $user->setLastLogin(new \DateTime());
            $this->em->flush();

            // TODO: Ajouter un log dans le journal d'audit
            // $this->auditLogger->log('Connexion', $user);
        }
    }
}
