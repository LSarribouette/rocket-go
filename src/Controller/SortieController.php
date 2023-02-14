<?php

namespace App\Controller;

use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sortie', name: 'sortie')]

class SortieController extends AbstractController
{
    //TODO : gerer le user connected
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

}
