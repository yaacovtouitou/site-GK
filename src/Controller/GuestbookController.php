<?php

namespace App\Controller;

use App\Entity\Guestbook;
use App\Form\GuestbookType;
use App\Repository\GuestbookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class GuestbookController extends AbstractController
{
    #[Route('/guestbook', name: 'app_guestbook')]
    public function index(GuestbookRepository $guestbookRepository): Response
    {
        // Only display approved messages, no form handling anymore
        return $this->render('guestbook/index.html.twig', [
            'messages' => $guestbookRepository->findApprovedMessages(),
        ]);
    }

    #[Route('/admin/guestbook', name: 'admin_guestbook')]
    #[IsGranted('ROLE_ADMIN')]
    public function admin(GuestbookRepository $guestbookRepository): Response
    {
        return $this->render('guestbook/admin.html.twig', [
            'pendingMessages' => $guestbookRepository->findBy(['isApproved' => false], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/guestbook/approve/{id}', name: 'admin_guestbook_approve')]
    #[IsGranted('ROLE_ADMIN')]
    public function approve(Guestbook $guestbook, EntityManagerInterface $entityManager): Response
    {
        $guestbook->setIsApproved(true);
        $entityManager->flush();

        $this->addFlash('success', 'Message validé !');

        return $this->redirectToRoute('admin_guestbook');
    }

    #[Route('/admin/guestbook/delete/{id}', name: 'admin_guestbook_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Guestbook $guestbook, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($guestbook);
        $entityManager->flush();

        $this->addFlash('success', 'Message supprimé !');

        return $this->redirectToRoute('admin_guestbook');
    }
}
