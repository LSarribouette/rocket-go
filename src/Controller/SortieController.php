<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

//TODO : gerer le user connected AVEC LE #[isGranted('ROLE_USER')]

#[Route('/sortie', name: 'sortie')]

class SortieController extends AbstractController
{
    #[Route('/', name: '_dashboard')]
    public function dashboard(
        SortieRepository $sortieRepository,
    ): Response
    {
        $sorties = $sortieRepository->findAll();
        $nowAsDateTimeObject = new \DateTime('now', new DateTimeZone('Europe/Paris'));

        return $this->render(
            'sortie/dashboard.html.twig',
            compact("sorties", "nowAsDateTimeObject")
        );
    }
    #[isGranted('ROLE_USER')]
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
//            $dureeInterval = $sortieForm->get("duree")->getData();
//            $duree = $dureeInterval['days']*24*60 + $dureeInterval['hours']*60 + $dureeInterval['minutes'];
//            $sortie->setDuree($duree);

//          verif de la cohérence des dates
            $dateDebutEvenement = $sortieForm->get('dateDebut')->getData();
            $nowAsDateTimeObject = new \DateTime('now', new DateTimeZone('Europe/Paris'));
            $dateClotureInscription = $sortieForm->get('dateCloture')->getData();
//          la date de Debut d'évenement ne peut pas etre inferieure à maintenant
            if($dateDebutEvenement < $nowAsDateTimeObject){
                $type = "danger";
                $this->addFlash($type, "Le formulaire n'a pas pu être envoyé. ");
                $message = "La date de Début de l'évênement ne peux pas être antérieur à maintenant !";
                $this->addFlash($type, $message);
                return $this->redirectToRoute('sortie_create');
            }
//          La date de Cloture d'inscription ne peut pas etre inferieure à maintenant
            if($dateClotureInscription < $nowAsDateTimeObject){
                $type = "danger";
                $this->addFlash($type, "Le formulaire n'a pas pu être envoyé. ");
                $message = "La date de Cloture d'Inscription de l'évênement ne peux pas être antérieur à maintenant !";
                $this->addFlash($type, $message);
                return $this->redirectToRoute('sortie_create');
            }
//          dateClotureInscription ne peut pas être supérieur à dateDebutEvenement
            if($dateClotureInscription > $dateDebutEvenement){
                $type = "danger";
                $this->addFlash($type, "Le formulaire n'a pas pu être envoyé. ");
                $message = "La date de Cloture d'Inscription ne peut pas être supérieur à la date de début d'évênement !";
                $this->addFlash($type, $message);
                return $this->redirectToRoute('sortie_create');
            }
//          Check de la durée
            $dureeEvenement = $sortieForm->get('duree')->getData();
//          la durée ne peut pas etre négative
            if($dureeEvenement <= 0){
                $type = "danger";
                $this->addFlash($type, "Le formulaire n'a pas pu être envoyé. ");
                $message = "La durée de l'êvenement doit être strictement positive !";
                $this->addFlash($type, $message);
                return $this->redirectToRoute('sortie_create');
            }
            //la durée ne peut pas excéder un mois (en minute)
            $dureeEvenement = $sortieForm->get('duree')->getData();
            if($dureeEvenement > 43800){
                $type = "danger";
                $this->addFlash($type, "Le formulaire n'a pas pu être envoyé. ");
                $message = "La durée de l'êvenement ne peut pas excéder un mois !";
                $this->addFlash($type, $message);
                return $this->redirectToRoute('sortie_create');
            }
//          Check du nombre de participant
            $nbInscriptionsMax = $sortieForm->get('nbInscriptionsMax')->getData();
            if($nbInscriptionsMax <= 1){
                $type = "danger";
                $this->addFlash($type, "Le formulaire n'a pas pu être envoyé. ");
                $message = "Vous allez vraiment y aller tout seul ?";
                $this->addFlash($type, $message);
                return $this->redirectToRoute('sortie_create');
            }

//TODO : facto ce code de type tartine, voire faire une function checkForm()

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

    #[Route('/details/{id}', name: '_details')]
    public function details(
        SortieRepository $sortieRepository,
        ParticipantRepository $participantRepository,
        int $id
    ): Response
    {
        $sortie = $sortieRepository->findOneBy(["id" => $id]);
        $organisateur = $participantRepository->findOneOrganisateurById($sortie->getOrganisateur());
        return $this->render(
            'sortie/details.html.twig',
            compact("sortie", "organisateur")
        );
    }
    #[Route('/sinscrire/{id}', name: '_inscrireParticipant')]
    public function inscrireParticipant(
        SortieRepository $sortieRepository,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $em,
        int $id,
    ): Response
    {
        $sortie = $sortieRepository->findOneBy(["id" => $id]);
        $participant = $participantRepository->findOneBy(["email"=>$this->getUser()->getUserIdentifier()]);
        //check si il reste de la place pour s"inscrire
        if(
            ($sortie->getParticipantsInscrits()->count()) < ($sortie->getNbInscriptionsMax())
        ){
            //Check si currentUser est déjà inscrit
            if(
                $sortie->getParticipantsInscrits()->contains($participant)
            ) {
                //message de type non T DEJA INSCRI
                $type = "danger";
                $message = "Vous semblez déjà inscrit à cette activité";
                $this->addFlash($type, $message);
                return $this->redirectToRoute('sortie_details', ['id'=>$id]);

            } else {
                $sortie->addParticipantsInscrit($participant);
                $em->persist($sortie);
                $em->flush();
                //message de type ok
                $type = "success";
                $message = "Vous avez bien été inscrit !";
                $this->addFlash($type, $message);
                return $this->redirectToRoute('sortie_dashboard');
            }
        } else {
            //message de type non APLUDPLACE
            $type = "danger";
            $message = "Il n'y a plus de place, l'inscription a échouée";
            $this->addFlash($type, $message);
            return $this->redirectToRoute('sortie_dashboard');
        }
    }

}
