<?php

namespace App\Controller;

use App\Entity\Mission;
use App\Repository\CompletionRepository;
use App\Repository\MissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class MissionController extends AbstractController
{
    #[Route('/missions', name: 'app_missions')]
    public function index(
        MissionRepository $missionRepository,
        CompletionRepository $completionRepository,
        EntityManagerInterface $em
    ): Response
    {
        $user = $this->getUser();

        // Check if we have missions, if not create some defaults (Quick Fix for Dev)
        if ($missionRepository->count([]) === 0) {
            $this->createDefaultMissions($em);
        }

        // Fetch all missions
        $allMissions = $missionRepository->findAll();

        // Fetch completions for today/this week to mark missions as completed
        // For simplicity in this view, we'll check if completed TODAY for daily missions
        // and THIS WEEK for weekly missions.

        $today = new \DateTime('today');
        $weekStart = new \DateTime('monday this week');

        $missionsData = [];
        foreach ($allMissions as $mission) {
            $isCompleted = false;

            if ($mission->getCategory() === 'daily') {
                $completion = $completionRepository->createQueryBuilder('c')
                    ->where('c.user = :user')
                    ->andWhere('c.mission = :mission')
                    ->andWhere('c.completedAt >= :date')
                    ->setParameter('user', $user)
                    ->setParameter('mission', $mission)
                    ->setParameter('date', $today)
                    ->getQuery()
                    ->getOneOrNullResult();
                $isCompleted = $completion !== null;
            } elseif ($mission->getCategory() === 'weekly') {
                $completion = $completionRepository->createQueryBuilder('c')
                    ->where('c.user = :user')
                    ->andWhere('c.mission = :mission')
                    ->andWhere('c.completedAt >= :date')
                    ->setParameter('user', $user)
                    ->setParameter('mission', $mission)
                    ->setParameter('date', $weekStart)
                    ->getQuery()
                    ->getOneOrNullResult();
                $isCompleted = $completion !== null;
            } else {
                // Special/One-time: check if ever completed
                $completion = $completionRepository->findOneBy(['user' => $user, 'mission' => $mission]);
                $isCompleted = $completion !== null;
            }

            // Transform entity to array structure expected by template (or update template to use entity methods)
            // Here we keep array structure for compatibility with existing template logic
            $missionsData[] = [
                'id' => $mission->getId(),
                'title' => $mission->getTitle(),
                'description' => $mission->getDescription(),
                'points' => $mission->getPoints(),
                'completed' => $isCompleted,
                'category' => $mission->getCategory()
            ];
        }

        $dailyMissions = array_filter($missionsData, fn($m) => $m['category'] === 'daily');
        $weeklyMissions = array_filter($missionsData, fn($m) => $m['category'] === 'weekly');
        $specialMissions = array_filter($missionsData, fn($m) => $m['category'] === 'special');

        $completedDaily = count(array_filter($dailyMissions, fn($m) => $m['completed']));
        $completedWeekly = count(array_filter($weeklyMissions, fn($m) => $m['completed']));

        // Total points earned from missions (could be fetched from user->getTotalPoints() but that includes games etc.)
        // Let's just show user total points
        $totalPoints = $user->getTotalPoints();

        return $this->render('mission/index.html.twig', [
            'dailyMissions' => $dailyMissions,
            'weeklyMissions' => $weeklyMissions,
            'specialMissions' => $specialMissions,
            'completedDaily' => $completedDaily,
            'totalDaily' => count($dailyMissions),
            'completedWeekly' => $completedWeekly,
            'totalWeekly' => count($weeklyMissions),
            'totalPoints' => $totalPoints
        ]);
    }

    private function createDefaultMissions(EntityManagerInterface $em): void
    {
        $defaults = [
            ['title' => 'Lire le Chéma', 'desc' => 'Récite le Chéma Israël le matin', 'points' => 10, 'cat' => 'daily'],
            ['title' => 'Donner la Tsedaka', 'desc' => 'Mets une pièce dans la boîte', 'points' => 5, 'cat' => 'daily'],
            ['title' => 'Ranger sa chambre', 'desc' => 'Aide tes parents à la maison', 'points' => 20, 'cat' => 'daily'],
            ['title' => 'Étudier la Paracha', 'desc' => 'Lis un passage de la Paracha', 'points' => 50, 'cat' => 'weekly'],
            ['title' => 'Allumer les bougies', 'desc' => 'Avant Chabbat (pour les filles)', 'points' => 30, 'cat' => 'weekly'],
            ['title' => 'Apprendre un Passouk', 'desc' => 'Apprends un verset par cœur', 'points' => 100, 'cat' => 'special'],
        ];

        foreach ($defaults as $data) {
            $m = new Mission();
            $m->setTitle($data['title']);
            $m->setDescription($data['desc']);
            $m->setPoints($data['points']);
            $m->setCategory($data['cat']);
            $em->persist($m);
        }
        $em->flush();
    }
}
