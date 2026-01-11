<?php

namespace App\Controller;

use App\Repository\GuestbookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(GuestbookRepository $guestbookRepository): Response
    {
        // Mock data for news slider
        $newsItems = [
            [
                'id' => '1',
                'title' => 'Nouvelle aventure Torah disponible!',
                'description' => 'Explore les histoires passionnantes de la Paracha avec des quiz interactifs',
                'image' => 'https://images.unsplash.com/photo-1640544634610-fac94ff37c21?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxqZXdpc2glMjBjaGlsZHJlbiUyMGxlYXJuaW5nJTIwaGFwcHl8ZW58MXx8fHwxNzY3NjY2NDAxfDA&ixlib=rb-4.1.0&q=80&w=1080',
                'category' => 'Nouveau'
            ],
            [
                'id' => '2',
                'title' => 'Célébrons Yud Aleph Nissan!',
                'description' => 'Participe à nos événements spéciaux et gagne des badges exclusifs',
                'image' => 'https://images.unsplash.com/photo-1582462638280-8b2ed3db71ea?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxqZXdpc2glMjB0cmFkaXRpb24lMjBraWRzJTIwaGFwcHl8ZW58MXx8fHwxNzY3NjY2NDAyfDA&ixlib=rb-4.1.0&q=80&w=1080',
                'category' => 'Événement'
            ],
            [
                'id' => '3',
                'title' => 'Top du Leaderboard cette semaine',
                'description' => 'Rejoins le défi hebdomadaire et grimpe au sommet du classement!',
                'image' => 'https://images.unsplash.com/photo-1763198216825-06c985c712a2?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxmdXR1cmlzdGljJTIwdGVjaG5vbG9neSUyMGtpZHN8ZW58MXx8fHwxNzY3NjY2NDAxfDA&ixlib=rb-4.1.0&q=80&w=1080',
                'category' => 'Gaming'
            ]
        ];

        $quickAccessTiles = [
            ['id' => 'gaming', 'title' => 'Gaming Zone', 'description' => 'Jeux & Défis', 'icon' => 'gamepad', 'color' => 'from-purple-500 to-pink-500', 'route' => 'app_gaming_zone'],
            ['id' => 'videos', 'title' => 'Vidéos IA', 'description' => 'Histoires animées', 'icon' => 'video', 'color' => 'from-blue-500 to-cyan-500', 'route' => 'app_videos'],
            ['id' => 'paracha', 'title' => 'Paracha Connect', 'description' => 'Lectures & Quiz', 'icon' => 'book', 'color' => 'from-vibrant-orange to-gold', 'route' => 'app_library'],
            ['id' => 'calendar', 'title' => 'Calendrier', 'description' => 'Dates importantes', 'icon' => 'calendar', 'color' => 'from-green-500 to-emerald-500', 'route' => 'app_calendar'],
            ['id' => 'missions', 'title' => 'Missions Quotidiennes', 'description' => 'Complète tes défis', 'icon' => 'sparkles', 'color' => 'from-yellow-500 to-orange-500', 'route' => 'app_missions'],
            ['id' => 'badges', 'title' => 'Mes Badges', 'description' => 'Collection & Trophées', 'icon' => 'trophy', 'color' => 'from-gold to-royal-blue', 'route' => 'app_dashboard']
        ];

        // Fetch latest 10 approved guestbook messages
        $testimonials = $guestbookRepository->findBy(['isApproved' => true], ['createdAt' => 'DESC'], 10);

        // If no testimonials, add mocks for display
        if (empty($testimonials)) {
            $testimonials = [
                (object)['pseudo' => 'Sarah', 'message' => 'Géoula Kids est génial ! J\'adore les jeux et les missions.', 'createdAt' => new \DateTime('-2 days')],
                (object)['pseudo' => 'David', 'message' => 'Merci pour ce site incroyable. J\'apprends beaucoup sur la Torah.', 'createdAt' => new \DateTime('-5 days')],
                (object)['pseudo' => 'Maman de Léa', 'message' => 'Une plateforme ludique et éducative parfaite pour nos enfants.', 'createdAt' => new \DateTime('-1 week')],
                (object)['pseudo' => 'Yossef', 'message' => 'Les vidéos sont super bien faites, bravo !', 'createdAt' => new \DateTime('-2 weeks')],
                (object)['pseudo' => 'Famille Cohen', 'message' => 'Nos enfants se régalent chaque jour avec les missions.', 'createdAt' => new \DateTime('-3 weeks')],
                (object)['pseudo' => 'Rivka', 'message' => 'J\'ai enfin compris la Paracha grâce à vous !', 'createdAt' => new \DateTime('-1 month')],
            ];
        }

        return $this->render('home/index.html.twig', [
            'newsItems' => $newsItems,
            'quickAccessTiles' => $quickAccessTiles,
            'testimonials' => $testimonials
        ]);
    }
}
