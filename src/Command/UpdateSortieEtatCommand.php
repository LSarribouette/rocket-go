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
    description: 'Command made for Updating Sortie.Etat, CHOOSE MOD TO EXECUTE: [C]loturée, [E]n_cours, [P]assée , [A]rchivée,[R]obot, or [M]anual requires two other parameters then : Sortie.id and Etat.id',
    aliases: ['e:u:s-e']
)]
class UpdateSortieEtatCommand extends Command
{
    private $em;
    private $sortieRepository;
    private $etatRepository;
    private $_ARCHIVE_ETAT_ID;
    private $_CLOTURE_ETAT_ID;
    private $_ENCOURS_ETAT_ID;
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
        $this->_ENCOURS_ETAT_ID= $etatRepository->findOneBy(['libelle'=>'en cours'])->getId();
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

    /**
     * Simple function to display Error Ascii Art in case of bad user input while calling this command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function unrecognized(InputInterface $input, OutputInterface $output){
        $io = new SymfonyStyle($input, $output);
        $output->writeln('--!--!-- UNRECOGNIZED MOD --!--!--');
        $output->writeln('      ___ _ __ _ __ ___  _ __ ');
        $output->writeln('     / _ \ \'__| \'__/ _ \| \'__|');
        $output->writeln('    |  __/ |  | | | (_) | |  ');
        $output->writeln('     \___|_|  |_|  \___/|_|');
        $output->writeln('--!--!-- UNRECOGNIZED MOD --!--!--');
        $output->writeln(['please try Again with valid Argument :', '[C]loturée, [E]n_cours, [P]assée ,[A]rchivée, [R]obot, or [M]anual']);
    }

    /**
     * Update a sortie.etat with two parameters, manually
     * @param OutputInterface $output
     * @param $sortieID
     * @param $etatID
     * @return void
     */
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

    /**
     * Function to set automatically the Etat.id 'cloturée' in all the corresponding sorties
     * @param OutputInterface $output
     * @return void
     */
    protected function cloture(OutputInterface $output){
        $output->writeln('--------- CLOTURE MOD ---------');
        $olderThanClotureSorties = $this->sortieRepository->findAllOlderThanCloture();
        //checking if already cloturée, en cours, passée, or archivée
        foreach ($olderThanClotureSorties as $s) {
            if (
                $s->getEtat()->getId() != $this->_CLOTURE_ETAT_ID
                && $s->getEtat()->getId() != $this->_ENCOURS_ETAT_ID
                && $s->getEtat()->getId() != $this->_PASSE_ETAT_ID
                && $s->getEtat()->getId() != $this->_ARCHIVE_ETAT_ID){
                $olderThanClotureSortiesToUpdate[] = $s;
            }
        }
        if(empty($olderThanClotureSortiesToUpdate)){
            $output->writeln('No entry to update as "cloturée" ');
        } else{
            foreach ($olderThanClotureSortiesToUpdate as $toClotureSortie){
                $this->updateSortieWithNewEtat($toClotureSortie->getId(), $this->_CLOTURE_ETAT_ID);
                $output->writeln('entry found : '. $toClotureSortie->getNom() .' - UPDATING as "cloturée"...');
            }
            $output->writeln(count($olderThanClotureSortiesToUpdate).' entries updated as "cloturées" ');
        }
    }

    /**
     * Function to set automatically the Etat.id 'en cours' in all the corresponding sorties
     * @param OutputInterface $output
     * @return void
     */
    protected function encours(OutputInterface $output){
        $output->writeln('--------- ENCOURS MOD ---------');
        $sorties = $this->sortieRepository->findAll();
        foreach ($sorties as $sortie) {
            $dateDebut = $sortie->getDateDebut();
            $durationInMinutes = $sortie->getDuree();
// c'est nul, ça CHANGE EFFECTIVEMENT dateDebut donc on va pas faire ça
//          $dateFin = $dateDebut->modify('+' . $durationInMinutes . ' minutes');
            $now = new \DateTime();
            if ($dateDebut <= $now && $dateDebut->modify('+'.$durationInMinutes.' minutes') >= $now) {
                $activeSorties[] = $sortie;
            }
        }
        if(!empty($activeSorties)){
            foreach ($activeSorties as $s) {
                if ($s->getEtat()->getId() != $this->_ENCOURS_ETAT_ID
                    && $s->getEtat()->getId() != $this->_PASSE_ETAT_ID
                    && $s->getEtat()->getId() != $this->_ARCHIVE_ETAT_ID){
                    $activeSortiesToUpdate[] = $s;
                }
            }
            if(empty($activeSortiesToUpdate)){
                $output->writeln('No entry to update as "en cours" ');
            } else{
                foreach ($activeSortiesToUpdate as $activeSortieToUpdate){
                    $this->updateSortieWithNewEtat($activeSortieToUpdate->getId(), $this->_ENCOURS_ETAT_ID);
                    $output->writeln('entry found : '. $activeSortieToUpdate->getNom() .' - UPDATING as "en cours"...');
                }
                $output->writeln(count($activeSortiesToUpdate).' entries updated as "en cours" ');
            }
        }else{
            $output->writeln('No Sortie is active at this time');
        }
    }

    /**
     * Function to set automatically the Etat.id 'passée' in all the corresponding sorties
     * @param OutputInterface $output
     * @return void
     */
    protected function passe(OutputInterface $output){
        $output->writeln('--------- PASSE MOD ---------');
        $sorties = $this->sortieRepository->findAll();
        foreach ($sorties as $sortie) {
            $dateDebut = $sortie->getDateDebut();
            $durationInMinutes = $sortie->getDuree();
// c'est nul, ça CHANGE EFFECTIVEMENT dateDebut donc on va pas faire ça
//          $dateFin = $dateDebut->modify('+'.$durationInMinutes.' minutes');
            if($dateDebut->modify('+'.$durationInMinutes.' minutes') < new \DateTime()){
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
                $output->writeln('No entry to update as "passée" ');
            } else{
                foreach ($ToFinishSorties as $toFinishSortie){
                    $this->updateSortieWithNewEtat($toFinishSortie->getId(), $this->_PASSE_ETAT_ID);
                    $output->writeln('entry found : '. $toFinishSortie->getNom() .' - UPDATING as "passée"...');
                }
                $output->writeln(count($ToFinishSorties).' entries updated as "passée" ');
            }
        }
    }

    /**
     * Function to set automatically the Etat.id 'archivée' in all the corresponding sorties
     * @param OutputInterface $output
     * @return void
     */
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
            $output->writeln('No entry OLDER THAN 1 MONTH at this time');
        } else{
            foreach ($olderThanAMonthAndNotArchivedSorties as $oldSortie){
                $this->updateSortieWithNewEtat($oldSortie->getId(), $this->_ARCHIVE_ETAT_ID);
                $output->writeln('entry found :'. $oldSortie->getNom() .' - UPDATING as "archivée"...');
            }
            $output->writeln(count($olderThanAMonthAndNotArchivedSorties).' entries updated as "archivée" ');
        }
    }

    /**
     * Function to execute all of the above, likelly to be use by a task manager on your server :)
     * @param OutputInterface $output
     * @return void
     */
    protected function robot(OutputInterface $output){
        $output->writeln('--------- ROBOT MOD MADE FOR AUTOMATION ---------');
        $output->writeln('---- ROBOT STARTING TO WORK ON YOUR DATABASE ----');
        $this->cloture($output);
        $this->encours($output);
        $this->passe($output);
        $this->archive($output);
        $output->writeln('--------- ROBOT MOD FINISHED ---------');
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
            case 'E': $this->encours($output);
            break;
            case 'M': $this->manual($output, $sortieID, $etatID);
            break;
            case 'R': $this->robot($output, $sortieID, $etatID);
            break;
            default: $this->unrecognized($input,$output);
            break;
        };

        if($mode=='A' || $mode=='P' || $mode=='C' || $mode=='E'|| $mode=='M'|| $mode=='R'){
            $io->success('Command Successfully Executed :) The Rocket-Go TEAM thanks you for using it');
            return Command::SUCCESS;
        } else {
            $io->error('Command FAILURE :( Try Again With Valid Parameters');
            return Command::FAILURE;
        }
    }
}
