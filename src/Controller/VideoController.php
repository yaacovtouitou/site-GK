<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VideoController extends AbstractController
{
    #[Route('/videos', name: 'app_videos')]
    public function index(): Response
    {
        $videos = [
            [
                'id' => 'paracha-semaine',
                'title' => 'Paracha de la Semaine',
                'description' => 'Découvre l\'histoire de la semaine en vidéo !',
                'url' => 'https://www.youtube.com/embed/toC92uHX1c8',
                'thumbnail' => 'https://img.youtube.com/vi/toC92uHX1c8/maxresdefault.jpg',
                'category' => 'Paracha',
                'duration' => '1 min'
            ]
        ];

        return $this->render('video/index.html.twig', [
            'videos' => $videos,
        ]);
    }
}
