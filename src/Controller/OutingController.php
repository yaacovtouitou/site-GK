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

        // Helper to find first image in folder
        $findCoverImage = function($folderName) {
            $dir = $this->getParameter('kernel.project_dir') . '/public/images/sorties/' . $folderName;

            if (!is_dir($dir)) {
                return 'https://via.placeholder.com/800x600?text=No+Image';
            }

            $finder = new Finder();
            $finder->files()->in($dir)->name(['*.jpg', '*.jpeg', '*.png', '*.webp'])->sortByName();

            foreach ($finder as $file) {
                // Return relative path starting with /images/...
                return '/images/sorties/' . $folderName . '/' . $file->getFilename();
            }

            return 'https://via.placeholder.com/800x600?text=No+Image';
        };

        // Mock data for past outings (memories) linked to folders
        $pastOutings = [
            [
                'id' => 'judaic park',
                'title' => 'Judaic Park - Mer de Sable',
                'image' => $findCoverImage('judaic park'),
                'description' => 'Une journée exceptionnelle au parc d\'attractions !'
            ],
            [
                'id' => 'latetedanslesnuages',
                'title' => 'La Tête dans les Nuages',
                'image' => $findCoverImage('latetedanslesnuages'),
                'description' => 'Jeux d\'arcade, réalité virtuelle et fun garanti !'
            ],
            [
                'id' => 'superfly',
                'title' => 'Superfly',
                'image' => $findCoverImage('superfly'),
                'description' => 'Trampolines, sauts et acrobaties pour se défouler.'
            ],
            [
                'id' => 'resto',
                'title' => 'Sortie Restaurant',
                'image' => $findCoverImage('resto'),
                'description' => 'Un délicieux repas tous ensemble !'
            ],
            [
                'id' => 'barque',
                'title' => 'Balade en Barque',
                'image' => $findCoverImage('barque'),
                'description' => 'Détente et rigolade sur le lac.'
            ],
            [
                'id' => 'lasergame',
                'title' => 'Laser Game',
                'image' => $findCoverImage('lasergame'),
                'description' => 'Une bataille épique entre amis !'
            ],
            [
                'id' => 'escapegame',
                'title' => 'Escape Game',
                'image' => $findCoverImage('escapegame'),
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
            'judaic park' => [
                'title' => 'Judaic Park - Mer de Sable',
                'description' => "Une journée mémorable à la Mer de Sable privatisée pour nous ! Manèges à sensations, spectacles de cascades et ambiance de folie dans le désert. Petits et grands ont profité du soleil et des attractions dans une atmosphère 100% casher et festive.",
            ],
            'latetedanslesnuages' => [
                'title' => 'La Tête dans les Nuages',
                'description' => "Une après-midi incroyable dans la plus grande salle de jeux d'Europe ! Au programme : simulateurs, jeux d'adresse, bowling et réalité virtuelle. Tout le monde s'est amusé comme des fous dans une ambiance survoltée.",
            ],
            'superfly' => [
                'title' => 'Superfly',
                'description' => "Ça a sauté dans tous les sens ! Trampolines géants, parcours ninja et bacs à mousse. Une sortie sportive et hilarante où chacun a pu tester ses talents d'acrobate en toute sécurité.",
            ],
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
                // Simple path construction since structure is flat now
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
