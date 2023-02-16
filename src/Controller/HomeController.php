<?php

namespace App\Controller;

use App\Form\ChangePasswordFormType;
use App\Form\ParticipantFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home_index')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/profile', name: 'home_profile')]
    public function seeProfile(): Response
    {
        return $this->render('home/profile.html.twig');
    }

    #[Route('/profile/modify', name: 'home_modify')]
    public function modifyProfile(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $participant = $this->getUser();
        $participantForm = $this->createForm(ParticipantFormType::class, $participant);
        $participantForm->handleRequest($request);
        if ($participantForm->isSubmitted() && $participantForm->isValid()) {
            $entityManager->persist($participant);
            $entityManager->flush();
            $this->addFlash('success', 'Informations du profil mises à jour !');
            return $this->redirectToRoute('home_profile');
        }
        return $this->render('home/modify.html.twig',
            compact('participantForm')
        );
    }

    #[Route('/profile/password', name: 'home_password')]
    public function modifyPassword(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $participant = $this->getUser();
        $passwordForm = $this->createForm(ChangePasswordFormType::class, $participant);
        $passwordForm->handleRequest($request);
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $entityManager->persist($participant);
            $entityManager->flush();
            $this->addFlash('success', 'Mot de passe mis à jour !');
            return $this->redirectToRoute('home_profile');
        }
        return $this->render('home/password.html.twig',
            compact('passwordForm')
        );
    }

    #[Route('/admin', name: 'home_administration')]
    public function adminPanel(): Response
    {
        return $this->render('home/administration.html.twig');
    }

}
