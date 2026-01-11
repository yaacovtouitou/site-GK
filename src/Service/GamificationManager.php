<?php

namespace App\Service;

use App\Entity\Badge;
use App\Entity\Completion;
use App\Entity\Mission;
use App\Entity\User;
use App\Repository\BadgeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class GamificationManager
{
    public const RANKS = [
        'Soldat' => 0,
        'Caporal' => 500,
        'Lieutenant' => 1000,
        'Capitaine' => 2500,
        'Général' => 5000
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private BadgeRepository $badgeRepository
    ) {}

    public function completeMission(User $user, Mission $mission): void
    {
        $pointsEarned = $mission->getPoints();
        $this->addPoints($user, $pointsEarned);

        $completion = new Completion();
        $completion->setUser($user);
        $completion->setMission($mission);
        $completion->setCompletedAt(new \DateTime());
        $completion->setStatus('validated');

        $this->entityManager->persist($completion);
        $this->entityManager->flush();

        $this->checkMissionBadges($user);

        $this->addFlashMessage($pointsEarned);
    }

    public function addPoints(User $user, int $points): void
    {
        $user->setTotalPoints($user->getTotalPoints() + $points);
        $this->checkRankUpgrade($user);
        $this->checkPointBadges($user);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function updateLoginStreak(User $user): void
    {
        $today = new \DateTime('today');
        $lastLogin = $user->getLastLoginDate();

        if (!$lastLogin) {
            $user->setLoginStreak(1);
        } else {
            $lastLoginDate = \DateTime::createFromInterface($lastLogin)->setTime(0, 0);
            $diff = $today->diff($lastLoginDate)->days;

            if ($diff === 1) {
                $user->setLoginStreak($user->getLoginStreak() + 1);
            } elseif ($diff > 1) {
                $user->setLoginStreak(1);
            }
        }

        $user->setLastLoginDate(new \DateTime());
        $this->checkStreakBadges($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function checkRankUpgrade(User $user): void
    {
        $points = $user->getTotalPoints();
        $newRank = 'Soldat';

        foreach (self::RANKS as $rank => $threshold) {
            if ($points >= $threshold) {
                $newRank = $rank;
            } else {
            }
        }

        if ($newRank !== $user->getCurrentRank()) {
            $user->setCurrentRank($newRank);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    public function getNextRankInfo(User $user): array
    {
        $points = $user->getTotalPoints();
        $currentRank = $user->getCurrentRank();

        $this->checkRankUpgrade($user);
        $currentRank = $user->getCurrentRank();

        $nextRank = null;
        $pointsNeeded = 0;
        $progress = 100;

        $ranks = self::RANKS;
        $rankNames = array_keys($ranks);

        $currentIndex = array_search($currentRank, $rankNames);

        if ($currentIndex !== false && isset($rankNames[$currentIndex + 1])) {
            $nextRank = $rankNames[$currentIndex + 1];
            $nextThreshold = $ranks[$nextRank];
            $currentThreshold = $ranks[$currentRank];

            $pointsNeeded = $nextThreshold - $points;

            $pointsInLevel = $points - $currentThreshold;
            $levelSpan = $nextThreshold - $currentThreshold;

            if ($levelSpan > 0) {
                $progress = ($pointsInLevel / $levelSpan) * 100;
            } else {
                $progress = 0;
            }
        }

        return [
            'nextRank' => $nextRank,
            'pointsNeeded' => $pointsNeeded,
            'progress' => $progress
        ];
    }

    private function checkStreakBadges(User $user): void
    {
        $streak = $user->getLoginStreak();
        if ($streak >= 7) {
            $this->awardBadge($user, 'Ami Fidèle');
        }
        if ($streak >= 30) {
            $this->awardBadge($user, 'Super Constant');
        }
    }

    private function checkPointBadges(User $user): void
    {
        $points = $user->getTotalPoints();
        if ($points >= 1000) {
            $this->awardBadge($user, 'Milliardaire de Mitzvot');
        }
    }

    private function checkMissionBadges(User $user): void
    {
        $completedCount = $user->getCompletions()->count();
        if ($completedCount >= 10) {
            $this->awardBadge($user, 'Super Apprenant');
        }
    }

    private function awardBadge(User $user, string $badgeName): void
    {
        $badge = $this->badgeRepository->findOneBy(['name' => $badgeName]);
        if ($badge && !$user->getBadges()->contains($badge)) {
            $user->addBadge($badge);
            $this->requestStack->getSession()->getFlashBag()->add('success', "Nouveau Badge débloqué : $badgeName ! 🏆");
        }
    }

    private function addFlashMessage(int $points): void
    {
        $messages = [
            "Super travail ! Tu as gagné $points points ! 🚀",
            "Magnifique ! Continue comme ça ! +$points points ! ⭐",
            "Kol Hakavod ! Tu avances vers la Géoula ! +$points points ! 👑",
        ];

        $message = $messages[array_rand($messages)];
        $this->requestStack->getSession()->getFlashBag()->add('success', $message);
    }
}
