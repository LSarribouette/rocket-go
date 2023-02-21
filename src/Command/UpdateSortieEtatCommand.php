<?php

namespace App\Command;

use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'entity:update:sortie.etat',
    description: 'Command made for Updating Sortie.Etat, CHOOSE execution MODS: [A]rchive , [C]loture, [P]ast, or [M]anual, requires two other parameters then : Sortie.id and Etat.id',
    aliases: ['e:u:s-e']
)]
class UpdateSortieEtatCommand extends Command
{
    private $em;
    private $sortieRepository;
    private $etatRepository;
    private $_ARCHIVE_ETAT_ID;
    private $_CLOTURE_ETAT_ID;
    private $_PASSE_ETAT_ID;

    public function __construct(
        EntityManagerInterface $em,
        SortieRepository $sortieRepository,
        EtatRepository $etatRepository
    )
    {
        //variables des ID correspondant à l'entité Etat facile à atteindre
        $this->_ARCHIVE_ETAT_ID= $etatRepository->findOneBy(['libelle'=>'archivée'])->getId();
        $this->_PASSE_ETAT_ID= $etatRepository->findOneBy(['libelle'=>'passée'])->getId();
        $this->_CLOTURE_ETAT_ID= $etatRepository->findOneBy(['libelle'=>'clôturée'])->getId();
        $this->em = $em;
        $this->sortieRepository = $sortieRepository;
        $this->etatRepository = $etatRepository;
        parent::__construct();
    }

    /**
     * Generic function to update a sortie.etat in a sortie entity, taking two IDs in parameter
     * @param int $sortieID
     * @param int $etatID
     * @return \App\Entity\Sortie|null
     * @author TMpro-TeamRocket
     */
    protected function configure(): void
    {
        $this->setDescription('Update Sortie.etat, pass an ID for the row to update (in the Sortie Table) and an Etat.id')
            ->addArgument(
                'mode', InputArgument::REQUIRED,
                '[A]rchive , [C]loture, [P]ast, or [M]anual')
            ->addArgument('Sortie.id', InputArgument::OPTIONAL, 'The ID of the Sortie to update')
            ->addArgument('Etat.id', InputArgument::OPTIONAL, 'the ID of Etat you want to put in sortie.etat at the ID preselected')
        ;
    }
    protected function updateSortieWithNewEtat(int $sortieID, int $etatID){

        //finding targetted row in entity
        $rowToUpdate = $this->sortieRepository->findOneBy(['id'=>$sortieID]);
        //finding targetted Etat
        $newEtat = $this->etatRepository->findOneBy(['id'=>$etatID]);
        //executing querry
        $rowToUpdate->setEtat($newEtat);
        $this->em->flush();

        return $updatedRow = $this->sortieRepository->findOneBy(['id'=>$sortieID]);

    }
    protected function unrecognized(InputInterface $input, OutputInterface $output){
        $io = new SymfonyStyle($input, $output);
        $output->writeln('--!--!-- UNRECOGNIZED MOD --!--!--');
        $output->writeln('      ___ _ __ _ __ ___  _ __ ');
        $output->writeln('     / _ \ \'__| \'__/ _ \| \'__|');
        $output->writeln('    |  __/ |  | | | (_) | |  ');
        $output->writeln('     \___|_|  |_|  \___/|_|');
        $output->writeln('--!--!-- UNRECOGNIZED MOD --!--!--');
        $output->writeln(['please try Again with valid Argument :', '[A]rchive , [C]loture, [P]ast, or [M]anual']);
    }
    protected function manual(OutputInterface $output, $sortieID, $etatID){
        $output->writeln('--------- MANUAL MOD ---------');
        $output->writeln('Targetted row in Sortie, ID : '.$sortieID);
        $output->writeln('Etat to put in, ID: '.$etatID);
        $updatedRow = $this->updateSortieWithNewEtat($sortieID, $etatID);
        if(isset($updatedRow)){
            $output->writeln('Your entity has been updated : '.$updatedRow->getNom());
            $output->writeln('New Sortie.etat : '.$updatedRow->getEtat()->getLibelle());
        }
    }
    protected function archive(OutputInterface $output){
        $output->writeln('--------- ARCHIVE MOD ---------');
        //fetching all Sorties
        $olderThanAMonthSorties = $this->sortieRepository->findAllDateDebutOlderThanAMonth();
        //checking if already Archivée
        foreach ($olderThanAMonthSorties as $s) {
            if ($s->getEtat()->getId() != $this->_ARCHIVE_ETAT_ID){
                $olderThanAMonthAndNotArchivedSorties[] = $s;
            }
        }
        if(empty($olderThanAMonthAndNotArchivedSorties)){
            $output->writeln('No entries OLDER THAN 1 MONTH remain unArchived');
        } else{
            foreach ($olderThanAMonthAndNotArchivedSorties as $oldSortie){
                $this->updateSortieWithNewEtat($oldSortie->getId(), $this->_ARCHIVE_ETAT_ID);
                $output->writeln('entry found :'. $oldSortie->getNom() .' - Archiving...');
            }
            $output->writeln(count($olderThanAMonthAndNotArchivedSorties).' entries archivees');
        }
    }
    protected function cloture(OutputInterface $output){
        $output->writeln('--------- CLOTURE MOD ---------');
        $olderThanClotureSorties = $this->sortieRepository->findAllOlderThanCloture();
        //checking if already Cloturée
        foreach ($olderThanClotureSorties as $s) {
            if (
                $s->getEtat()->getId() != $this->_CLOTURE_ETAT_ID
                && $s->getEtat()->getId() != $this->_ARCHIVE_ETAT_ID
                && $s->getEtat()->getId() != $this->_PASSE_ETAT_ID){
                $olderThanClotureAndNotCloturedSorties[] = $s;
            }
        }
        if(empty($olderThanClotureAndNotCloturedSorties)){
            $output->writeln('No entries CLOTUREE in date remain unCloturee');
        } else{
            foreach ($olderThanClotureAndNotCloturedSorties as $toClotureSortie){
                $this->updateSortieWithNewEtat($toClotureSortie->getId(), $this->_CLOTURE_ETAT_ID);
                $output->writeln('entry found : '. $toClotureSortie->getNom() .' - Cloturing...');
            }
            $output->writeln(count($olderThanClotureAndNotCloturedSorties).' entries cloturees');
        }
    }
    protected function passe(OutputInterface $output){
        $output->writeln('--------- PASSE MOD ---------');
        $sorties = $this->sortieRepository->findAll();
        foreach ($sorties as $sortie) {
            $dateDebut = $sortie->getDateDebut();
            $durationInMinutes = $sortie->getDuree();
            $dateFin = $dateDebut->modify('+'.$durationInMinutes.' minutes');
            if($dateFin < new \DateTime()){
                $finishedSorties[] = $sortie;
            };
        }
        if(!empty($finishedSorties)){
            foreach ($finishedSorties as $s) {
                if ($s->getEtat()->getId() != $this->_PASSE_ETAT_ID && $s->getEtat()->getId() != $this->_ARCHIVE_ETAT_ID){
                    $ToFinishSorties[] = $s;
                }
            }
            if(empty($ToFinishSorties)){
                $output->writeln('No entries PASSEE in date remain unPassee');
            } else{
                foreach ($ToFinishSorties as $toFinishSortie){
                    $this->updateSortieWithNewEtat($toFinishSortie->getId(), $this->_PASSE_ETAT_ID);
                    $output->writeln('entry found : '. $toFinishSortie->getNom() .' - Finishing...');
                }
                $output->writeln(count($ToFinishSorties).' entries passees');
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            '',
            ' -----· • —– ٠ ✤ ٠ —– • ·-----',
            '⚜⚜  Updater of Sortie.etat  ⚜⚜ ',
            ' -----------------------------',
            '⚜ A Work of Art by TeamRocket ⚜ ',
            ' -----· • —– ٠ ✤ ٠ —– • ·-----',
            '',
        ]);

        $io = new SymfonyStyle($input, $output);

        $mode = $input->getArgument('mode');
        $sortieID = $input->getArgument('Sortie.id');
        $etatID = $input->getArgument('Etat.id');

        $output->writeln('------ Selected mode : '.$mode.' ------');
        //selection du mod par le user
        switch ($mode){
            case 'A' : $this->archive($output);
            break;
            case 'P': $this->passe($output);
            break;
            case 'C': $this->cloture($output);
            break;
            case 'M': $this->manual($output, $sortieID, $etatID);
            break;
            default: $this->unrecognized($input,$output);
            break;
        };

        if($mode=='A' || $mode=='P' || $mode=='C' || $mode=='M'){
            $io->success('Command Successfully Executed :) The Rocket-Go TEAM thanks you for using it');
            return Command::SUCCESS;
        } else {
            $io->error('Command FAILURE :( Try Again With Valid Parameters');
            return Command::FAILURE;
        }
    }
}
