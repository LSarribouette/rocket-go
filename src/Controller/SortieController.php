<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\EtatRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SiteRepository;
use App\Repository\SortieRepository;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

//TODO : gerer le user connected AVEC LE #[isGranted('ROLE_USER')]

#[Route('/sortie', name: 'sortie')]

class SortieController extends AbstractController
{
    #[Route('/', name: '_dashboard')]
    public function dashboard(
        SortieRepository $sortieRepository,
    ): Response
    {
        $sorties = $sortieRepository->findAllOptimized();
        $now = new \DateTime('now', new DateTimeZone('Europe/Paris'));

        return $this->render(
            'sortie/dashboard.html.twig',
            compact("sorties", "now")
        );
    }

    #[Route('/mes-sorties', name: '_messorties')]
    public function dashboardMesSorties(
        SortieRepository $sortieRepository,
    ): Response
    {
        $sorties_organisateurice = $sortieRepository->findBy(["organisateur" => $this->getUser()]);
        $sorties_inscrite = $sortieRepository->findWhereRegistered($this->getUser()->getId());

        return $this->render(
            'sortie/mes-sorties.html.twig',
            compact("sorties_organisateurice", "sorties_inscrite")
        );
    }

    #[isGranted('ROLE_USER')]
    #[Route('/new', name: '_create')]
    public function create(
        EntityManagerInterface $em,
        Request $rq,
        ValidatorInterface $validator,
        EtatRepository $etatRepository,
    ): Response
    {
        $sortie = (new Sortie())
            ->setOrganisateur($this->getUser())
            ->setSite($this->getUser()->getSite());
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($rq);
        //recupération de l'organisateur == current user
        $sortie->setOrganisateur($this->getUser());
        //récupération de l'état "créée" qu'on attribue par défaut à une sortie quand on crée un formulaire
        $etat = $etatRepository->findOneBy(["libelle"=>"créée"]);
        $sortie->setEtat($etat);

        if($sortieForm->isSubmitted()){
            $errors = $validator->validate($sortie);
            if (count($errors) > 0) {
                $type = "danger";
                $this->addFlash($type, "Le formulaire n'a pas pu être envoyé. ");
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath().' - '.$error->getMessage();
                }
                foreach ($errorMessages as $errorMessage) {
                    $this->addFlash($type, $errorMessage);
                }
            }
            if($sortieForm->isValid()){
                $em->persist($sortie);
                $em->flush();
                $type = "success";
                $this->addFlash($type, "La sortie a bien été créée, n'oubliez pas de la publier dans [Mes sorties] :) ");
                return $this->redirectToRoute('sortie_dashboard');
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
        $now = new \DateTime('now', new DateTimeZone('Europe/Paris'));
        $sortie = $sortieRepository->findOneBy(["id" => $id]);
        $organisateur = $participantRepository->findOneOrganisateurById($sortie->getOrganisateur());
        return $this->render(
            'sortie/details.html.twig',
            compact("sortie", "organisateur", "now")
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
        $now = new \DateTime('now', new DateTimeZone('Europe/Paris'));
        $dateClotureInscription = $sortie->getDateCloture();
        //check si la date d'inscription n'est pas dépassé.
        if($dateClotureInscription < $now){
            //message de type non la date d'inscription est dépassé
            $type = "danger";
            $message = "Vous ne pouvez pas vous inscrire, les inscriptions sont fermées";
            $this->addFlash($type, $message);
            return $this->redirectToRoute('sortie_details', ['id'=>$id]);
        }

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
                return $this->redirectToRoute('sortie_details', ['id'=>$id]);
            }
        } else {
            //message de type non APUDPLACE
            $type = "danger";
            $message = "Il n'y a plus de place, l'inscription a échouée";
            $this->addFlash($type, $message);
            return $this->redirectToRoute('sortie_details', ['id'=>$id]);
        }
    }

    #[Route('/desistement/{id}', name: '_desistementParticipant')]
    public function desistement(
        SortieRepository $sortieRepository,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $em,
        int $id,
    ): Response{
        $sortie = $sortieRepository->findOneBy(["id" => $id]);
        $participant = $participantRepository->findOneBy(["email"=>$this->getUser()->getUserIdentifier()]);
        if($sortie->getParticipantsInscrits()->contains($participant)){
            $sortie->removeParticipantsInscrit($participant);
            $em->flush();
            //message de type ok
            $type = "success";
            $message = "Vous avez bien été désinscrit de la sortie !";
            $this->addFlash($type, $message);
            return $this->redirectToRoute('sortie_details', ['id'=>$id]);
        }else{
            //message de type non T DEJA INSCRI
            $type = "danger";
            $message = "Vous ne pouvez pas vous désinscrire sans avoir été inscrit !";
            $this->addFlash($type, $message);
            return $this->redirectToRoute('sortie_details', ['id'=>$id]);
        }

    }

    #[Route('/annuler/{id}', name: '_annuler')]
    public function annuler(
        SortieRepository $sortieRepository,
        EntityManagerInterface $em,
        ParticipantRepository $participantRepository,
        int $id
    ): Response
    {
        $sortie = $sortieRepository->findOneBy(["id" => $id]);
        //je récupère tous les participants à cette sortie
        $participants = $sortie->getParticipantsInscrits();
        //je supprime les participants, pour vider la table d'association sortie_participant
        foreach ($participants as $participant) {
            $sortie->removeParticipantsInscrit($participant);
            $em->flush();
        }
        $sortieRepository->remove($sortie);
        $em->flush();

        $type = "success";
        $message = "Vous avez supprimé le sortie";
        $this->addFlash($type, $message);
        return $this->redirectToRoute('sortie_dashboard');

    }

}
