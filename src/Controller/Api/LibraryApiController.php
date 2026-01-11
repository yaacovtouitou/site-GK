<?php

namespace App\Controller\Api;

use App\Entity\Completion;
use App\Entity\Mission;
use App\Repository\CompletionRepository;
use App\Repository\MissionRepository;
use App\Service\GamificationManager;
use App\Service\HebraicCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/api/library')]
class LibraryApiController extends AbstractController
{
    #[Route('/complete', name: 'api_library_complete', methods: ['POST'])]
    public function complete(
        EntityManagerInterface $em,
        GamificationManager $gamificationManager,
        HebraicCalendarService $calendarService,
        MissionRepository $missionRepository,
        CompletionRepository $completionRepository
    ): JsonResponse
    {
        $user = $this->getUser();

        // Get current Paracha name to identify the task uniquely per week
        $parachaInfo = $calendarService->getCurrentParacha();
        $currentParacha = $parachaInfo['name'];
        $missionTitle = 'Lecture Paracha: ' . $currentParacha;

        // Check if a mission for this specific Paracha exists, if not create it (or use a generic weekly one)
        // Strategy: Use a generic "Weekly Paracha Reading" mission but check completion for THIS week.

        $mission = $missionRepository->findOneBy(['title' => 'Lecture Paracha Hebdomadaire']);

        if (!$mission) {
            $mission = new Mission();
            $mission->setTitle('Lecture Paracha Hebdomadaire');
            $mission->setDescription('Lire la Paracha de la semaine');
            $mission->setPoints(50);
            $mission->setCategory('weekly'); // Important for reset logic
            $em->persist($mission);
            $em->flush();
        }

        // Check if already completed THIS WEEK
        $weekStart = new \DateTime('monday this week');

        $existingCompletion = $completionRepository->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.mission = :mission')
            ->andWhere('c.completedAt >= :date')
            ->setParameter('user', $user)
            ->setParameter('mission', $mission)
            ->setParameter('date', $weekStart)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingCompletion) {
            return $this->json([
                'success' => false,
                'message' => "Tu as déjà validé la lecture de cette semaine ! Reviens la semaine prochaine.",
                'alreadyCompleted' => true
            ]);
        }

        // Award points and mark as completed
        $gamificationManager->completeMission($user, $mission); // This handles points + completion record

        return $this->json([
            'success' => true,
            'pointsEarned' => $mission->getPoints(),
            'totalPoints' => $user->getTotalPoints(),
            'message' => "Hazak ! Tu as gagné " . $mission->getPoints() . " points !"
        ]);
    }
}
