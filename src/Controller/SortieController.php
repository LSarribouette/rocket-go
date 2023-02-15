<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
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
        Request $rq,
        EtatRepository $etatRepository,
    ): Response
    {
        $sortie = new Sortie();
        $sortie->setOrganisateur($this->getUser());
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($rq);
        //recupération de l'organisateur == current user
        $sortie->setOrganisateur($this->getUser());
        //récupération de l'état "créée" qu'on attribue par défaut à une sortie quand on crée un formulaire
        $etat = $etatRepository->findOneBy(["libelle"=>"créée"]);
        $sortie->setEtat($etat);


        if($sortieForm->isSubmitted()){
            var_dump("AAAAAAAAAAAAAAAAAAA");
            $dureeInterval = $sortieForm->get("duree")->getData();
            var_dump($dureeInterval);

            if($sortieForm->isValid()){
                $em->persist($sortie);
                $em->flush();
                return $this->redirectToRoute('sortie_dashboard');
            } else {
                var_dump($sortie);
            }
        }

        return $this->render(
            'sortie/new.html.twig',
            compact("sortieForm")
        );
    }
}
