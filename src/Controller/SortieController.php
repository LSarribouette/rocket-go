<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\FiltreLieuSortieType;
use App\Form\FiltreSiteSortieType;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


//TODO : gerer le user connected AVEC LE #[isGranted('ROLE_USER')]

#[Route('/sortie', name: 'sortie')]

class SortieController extends AbstractController
{
    #[Route('/', name: '_dashboard')]
    public function dashboard(
        SortieRepository $sortieRepository,
        SiteRepository $siteRepository,
        Request $request
    ): Response
    {
        $now = new \DateTime('now', new DateTimeZone('Europe/Paris'));
        $sites = $siteRepository->findAll();
        $sorties = $sortieRepository->findAllOptimized();

        $tri = new Sortie();

        $triSiteForm = $this->createForm(FiltreSiteSortieType::class, $tri);
        $triSiteForm->handleRequest($request);
        if($triSiteForm->isSubmitted()) {
            $sorties = $sortieRepository->findBySiteOptimized($tri->getSite());
            dump($tri->getSite()->getNom());
            dump($sorties);
        }

        $triLieuForm = $this->createForm(FiltreLieuSortieType::class, $tri);
        $triLieuForm->handleRequest($request);
        if($triLieuForm->isSubmitted()) {
            $sorties = $sortieRepository->findByLieuOptimized($tri->getLieu());
            dump($tri->getLieu()->getNom());
            dump($sorties);
        }

        return $this->render(
            'sortie/dashboard.html.twig',
            compact("sorties", "now", "sites", "triSiteForm", "triLieuForm")
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
        SortieRepository $sortieRepository,
        SluggerInterface $slugger
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

                //upload file

                //handling the file
                /** @var UploadedFile $uploadedFile */
                $uploadedFile = $sortieForm['urlPhoto']->getData();
                $directory = $this->getParameter('kernel.project_dir').'/public/assets/media/user_files/sortie_picture';
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$uploadedFile->guessExtension();
                //try catch le move du fichier dans le user_files/sortie-picture directory
                try {
                    $uploadedFile->move($directory, $newFilename);
                } catch (FileException $e){
                    echo $e->getMessage();
                    $type = "danger";
                    $this->addFlash($type, "Erreur lors de l'upload");
                    return $this->redirectToRoute('sortie_create');
                }
                //on finit par renseigner l'urlPhoto dans la Sortie, je passe par strstr pour virer le chemin absolu
                $url = strstr($directory."/".pathinfo($newFilename)['basename'], 'assets/');
                $sortie->setUrlPhoto($url);
                //fin des travaux

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
        EtatRepository $etatRepository,
        int $id
    ): Response
    {
        $sortie = $sortieRepository->findOneBy(["id" => $id]);
        $etat = $etatRepository->findOneBy(["libelle"=>"annulée"]);
        $sortie->setEtat($etat);
        $em->flush();
        $type = "success";
        $message = "Vous avez annulé la sortie";
        $this->addFlash($type, $message);
        return $this->redirectToRoute('sortie_messorties');
    }

    #[Route('/publier/{id}', name:'_publier')]
    public function publier(
        SortieRepository $sortieRepository,
        EntityManagerInterface $em,
        EtatRepository $etatRepository,
        int $id
    ): Response
    {
        $sortie = $sortieRepository->findOneBy(["id" => $id]);
        $etat = $etatRepository->findOneBy(["libelle"=>"ouverte"]);
        $sortie->setEtat($etat);
        $em->flush();
        $type = "success";
        $message = "Vous avez publiée votre sortie ! Elle sera visible sur le réseau :)";
        $this->addFlash($type, $message);
        return $this->redirectToRoute('sortie_messorties');
    }

}
