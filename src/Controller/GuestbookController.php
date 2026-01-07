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
    public function index(Request $request, EntityManagerInterface $entityManager, GuestbookRepository $guestbookRepository): Response
    {
        $guestbook = new Guestbook();

        // Pre-fill pseudo if user is logged in
        if ($this->getUser()) {
            $guestbook->setPseudo($this->getUser()->getUserIdentifier());
        }

        $form = $this->createForm(GuestbookType::class, $guestbook);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($guestbook);
            $entityManager->flush();

            $this->addFlash('success', 'Ton message a été envoyé ! Il apparaîtra bientôt après validation. 💌');

            return $this->redirectToRoute('app_guestbook');
        }

        return $this->render('guestbook/index.html.twig', [
            'guestbookForm' => $form,
            'messages' => $guestbookRepository->findApprovedMessages(),
        ]);
    }

    #[Route('/admin/guestbook', name: 'admin_guestbook')]
    #[IsGranted('ROLE_ADMIN')] // Ensure you have a way to grant this role or remove for dev
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
