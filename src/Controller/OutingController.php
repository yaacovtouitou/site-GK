<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OutingController extends AbstractController
{
    #[Route('/sorties', name: 'app_outings')]
    public function index(): Response
    {
        // Mock data for upcoming outings
        $upcomingOutings = [
            [
                'id' => 1,
                'title' => 'Grand Parc d\'Attraction',
                'date' => 'Dimanche 12 Mai • 10h00',
                'icon' => '🎡',
                'description' => 'Une journée inoubliable pleine de sensations fortes !'
            ]
        ];

        // Mock data for past outings (memories) linked to folders
        $pastOutings = [
            [
                'id' => 'resto', // ID is folder name for simplicity
                'title' => 'Sortie Restaurant',
                'image' => '/images/sorties/resto/1.jpg', // Assumes 1.jpg exists as cover
                'description' => 'Un délicieux repas tous ensemble !'
            ],
            [
                'id' => 'barque',
                'title' => 'Balade en Barque',
                'image' => '/images/sorties/barque/1.jpg',
                'description' => 'Détente et rigolade sur le lac.'
            ],
            [
                'id' => 'lasergame',
                'title' => 'Laser Game',
                'image' => '/images/sorties/lasergame/1.jpg',
                'description' => 'Une bataille épique entre amis !'
            ],
            [
                'id' => 'escapegame',
                'title' => 'Escape Game',
                'image' => '/images/sorties/escapegame/1.jpg',
                'description' => 'Enigmes résolues et mission accomplie !'
            ]
        ];

        return $this->render('outing/index.html.twig', [
            'upcomingOutings' => $upcomingOutings,
            'pastOutings' => $pastOutings,
        ]);
    }

    #[Route('/sorties/souvenirs/{id}', name: 'app_outings_gallery')]
    public function gallery(string $id): Response
    {
        // Map ID to folder and details
        $outingsData = [
            'resto' => [
                'title' => 'Sortie Restaurant',
                'description' => "Nous avons tous ressenti une ambiance joyeuse et décontractée dès notre arrivée. Pour couronner le tout, nous avons savouré de délicieux hamburgers qui ont ajouté une touche de kiff supplémentaire à notre expérience. C’était une journée mémorable remplie de bonne humeur et de joie.",
            ],
            'barque' => [
                'title' => 'Balade en Barque',
                'description' => "Nous avons visité le bois de Vincennes, un endroit magnifique rempli d’amusement et de détente. Nous avons dégusté de délicieuses pizzas et profité des barques sur le lac. La musique était incroyable et nous avons tous dansé et chanté ensemble. L’ambiance était remplie de bonne humeur et de rires. C’était une journée mémorable où nous avons vraiment apprécié chaque instant.",
            ],
            'lasergame' => [
                'title' => 'Laser Game',
                'description' => "Retour sur la sortie au Laser Game avec un mot de Torah. Deux heures de Laser game, un goûter et beaucoup de joie au retour.",
            ],
            'escapegame' => [
                'title' => 'Escape Game',
                'description' => "Les enfants ont participé à un Escape Game en plein air, où nous devions résoudre des énigmes. C’était à la fois stimulant et amusant, et nous avons tous adoré travailler en équipe pour trouver les indices. Après l’Escape Game, nous avons eu la chance de jouer au ping-pong et au foot dans une ambiance conviviale. C’était génial de pouvoir se détendre et s’amuser en plein air. Ensuite, nous avons dégusté de délicieuses pizzas, qui étaient absolument délicieuses. La journée s’est poursuivie avec une séance d’étude de la Torah. Pour terminer en beauté, nous avons eu un goûter avec des collations savoureuses et rafraîchissantes. Nous avons également pu écouter de la musique et danser, ce qui a ajouté une touche de joie et de bonne humeur à la journée.",
            ],
        ];

        if (!isset($outingsData[$id])) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }

        $outing = $outingsData[$id];
        $outing['photos'] = [];

        // Dynamically find images in the folder
        $directory = $this->getParameter('kernel.project_dir') . '/public/images/sorties/' . $id;

        if (is_dir($directory)) {
            $finder = new Finder();
            $finder->files()->in($directory)->name(['*.jpg', '*.jpeg', '*.png', '*.webp']);

            foreach ($finder as $file) {
                $outing['photos'][] = '/images/sorties/' . $id . '/' . $file->getFilename();
            }
        }

        // If no photos found, add a placeholder or handle gracefully
        if (empty($outing['photos'])) {
             $outing['photos'][] = 'https://via.placeholder.com/800x600?text=Pas+de+photos';
        }

        return $this->render('outing/gallery.html.twig', [
            'outing' => $outing,
        ]);
    }
}
