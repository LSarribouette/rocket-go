<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

//TODO : gerer le user connected AVEC LE #[isGranted('ROLE_USER')]

#[Route('/sortie', name: 'sortie')]

class SortieController extends AbstractController
{
    #[Route('/', name: '_dashboard')]
    public function dashboard(
        SortieRepository $sortieRepository
    ): Response
    {
        $sorties = $sortieRepository->findAll();

        return $this->render(
            'sortie/dashboard.html.twig',
            compact("sorties")
        );
    }
    #[Route('/new', name: '_create')]
    public function create(
        EntityManagerInterface $em,
        Request $rq
    ): Response
    {
        $sortie = new Sortie();
        $sortie->setOrganisateur($this->getUser());
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($rq);
        if($sortieForm->isSubmitted() && $sortieForm->isValid()){
            $em->persist($sortie);
            $em->flush();
            return $this->redirectToRoute('sortie_dashboard');
        }
        return $this->render(
            'sortie/new.html.twig',
            compact("sortieForm")
        );
    }
}
