<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Ne peut pas être vide.')]
    #[Assert\Length(min: 2, max: 30, minMessage: 'Trop court, minimum 2 caractères.', maxMessage: 'Trop Long, maximum 30 caracteres.')]
    #[ORM\Column(length: 30)]
    private ?string $nom = null;


    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThan('today', message: 'Le Début de l\'activité doit être supérieur à maintenant.')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[Assert\Type("Integer", message: 'La durée doit être exprimée en nombre entier.')]
    #[Assert\GreaterThan(0, message: "La durée doit être strictement supérieure à 0 Minutes")]
    #[Assert\LessThanOrEqual(43800, message: 'La sortie ne peut pas durer plus d\'1 mois. C\'est pas mal déjà 1 mois, non ?')]
    #[ORM\Column(nullable: true)]
    private ?int $duree = null;

    #[Assert\GreaterThan('today', message: 'La date de cLoture d`\'inscription doit être supérieur à maintenant.')]
    #[Assert\LessThanOrEqual(propertyPath: "dateDebut", message: "La date de cloture d'inscription doit être antérieure à la date ou débute la sortie.")]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCloture = null;

    #[Assert\Type("Integer", message: 'Le nombre de participant.e doit être exprimé en nombre entier.')]
    #[Assert\GreaterThan(1, message: "Le nombre de participant.e doit être strictement supérieur à 1.")]
    #[Assert\LessThanOrEqual(100, message: "Le nombre de participant.e doit être inférieur à 101, c'est beaucoup déjà 100 non ?")]
    #[ORM\Column]
    private ?int $nbInscriptionsMax = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionInfos = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $urlPhoto = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etat $etat = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lieu $lieu = null;

    #[ORM\ManyToOne(inversedBy: 'sortiesOrganisees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participant $organisateur = null;

    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'inscriptions')]
    private Collection $participantsInscrits;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): self
    {
        $this->duree = $duree;

        return $this;
    }

    public function getDateCloture(): ?\DateTimeInterface
    {
        return $this->dateCloture;
    }

    public function setDateCloture(\DateTimeInterface $dateCloture): self
    {
        $this->dateCloture = $dateCloture;

        return $this;
    }

    public function getNbInscriptionsMax(): ?int
    {
        return $this->nbInscriptionsMax;
    }

    public function setNbInscriptionsMax(int $nbInscriptionsMax): self
    {
        $this->nbInscriptionsMax = $nbInscriptionsMax;

        return $this;
    }

    public function getDescriptionInfos(): ?string
    {
        return $this->descriptionInfos;
    }

    public function setDescriptionInfos(?string $descriptionInfos): self
    {
        $this->descriptionInfos = $descriptionInfos;

        return $this;
    }


    public function getUrlPhoto(): ?string
    {
        return $this->urlPhoto;
    }

    public function setUrlPhoto(?string $urlPhoto): self
    {
        $this->urlPhoto = $urlPhoto;

        return $this;
    }

    public function getEtat(): ?Etat
    {
        return $this->etat;
    }

    public function setEtat(?Etat $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): self
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Participant $organisateur): self
    {
        $this->organisateur = $organisateur;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipantsInscrits(): Collection
    {
        return $this->participantsInscrits;
    }

    public function addParticipantsInscrit(Participant $participantsInscrit): self
    {
        if (!$this->participantsInscrits->contains($participantsInscrit)) {
            $this->participantsInscrits->add($participantsInscrit);
        }

        return $this;
    }

    public function removeParticipantsInscrit(Participant $participantsInscrit): self
    {
        $this->participantsInscrits->removeElement($participantsInscrit);

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): self
    {
        $this->site = $site;

        return $this;
    }
}
