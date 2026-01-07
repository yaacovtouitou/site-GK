<?php

namespace App\Controller\Api;

use App\Entity\Completion;
use App\Entity\Mission;
use App\Repository\CompletionRepository;
use App\Repository\MissionRepository;
use App\Service\GamificationManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/api/mission')]
class MissionApiController extends AbstractController
{
    #[Route('/toggle/{id}', name: 'api_mission_toggle', methods: ['POST'])]
    public function toggle(
        int $id,
        MissionRepository $missionRepository,
        CompletionRepository $completionRepository,
        EntityManagerInterface $em,
        GamificationManager $gamificationManager
    ): JsonResponse
    {
        $user = $this->getUser();
        $mission = $missionRepository->find($id);

        if (!$mission) {
            return $this->json(['error' => 'Mission not found'], 404);
        }

        $today = new \DateTime('today');
        $weekStart = new \DateTime('monday this week');

        // Check if already completed today (or relevant period)
        // Logic simplified: check if completed TODAY for daily, THIS WEEK for weekly
        $periodStart = $mission->getCategory() === 'weekly' ? $weekStart : $today;

        $completion = $completionRepository->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.mission = :mission')
            ->andWhere('c.completedAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('mission', $mission)
            ->setParameter('date', $periodStart)
            ->getQuery()
            ->getOneOrNullResult();

        $isCompleted = false;
        $pointsEarned = 0;

        if ($completion) {
            // Undo completion
            $em->remove($completion);
            $user->setTotalPoints(max(0, $user->getTotalPoints() - $mission->getPoints()));
            $isCompleted = false;
        } else {
            // Complete mission
            $completion = new Completion();
            $completion->setUser($user);
            $completion->setMission($mission);
            $completion->setCompletedAt(new \DateTime());
            $completion->setStatus('validated');
            $em->persist($completion);

            $user->setTotalPoints($user->getTotalPoints() + $mission->getPoints());
            $isCompleted = true;
            $pointsEarned = $mission->getPoints();
        }

        $em->persist($user);
        $em->flush();

        // Recalculate stats for response
        $dailyMissions = $missionRepository->findBy(['category' => 'daily']);
        $weeklyMissions = $missionRepository->findBy(['category' => 'weekly']);

        $completedDaily = $this->countCompleted($completionRepository, $user, $dailyMissions, $today);
        $completedWeekly = $this->countCompleted($completionRepository, $user, $weeklyMissions, $weekStart);

        return $this->json([
            'success' => true,
            'completed' => $isCompleted,
            'pointsEarned' => $pointsEarned,
            'totalPoints' => $user->getTotalPoints(),
            'completedDaily' => $completedDaily,
            'totalDaily' => count($dailyMissions),
            'completedWeekly' => $completedWeekly,
            'totalWeekly' => count($weeklyMissions),
            'message' => $isCompleted ? "Hazak ! Continue comme ça !" : "Mission annulée."
        ]);
    }

    private function countCompleted($repo, $user, $missions, $date)
    {
        if (empty($missions)) return 0;

        return $repo->createQueryBuilder('c')
            ->select('count(DISTINCT c.mission)')
            ->where('c.user = :user')
            ->andWhere('c.mission IN (:missions)')
            ->andWhere('c.completedAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('missions', $missions)
            ->setParameter('date', $date)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
