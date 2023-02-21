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
    description: 'Update Etat in Sortie entity',
    aliases: ['e:u:s-e']
)]
class UpdateSortieEtatCommand extends Command
{
    private $em;
    private $sortieRepository;
    private $etatRepository;
    public function __construct(
        EntityManagerInterface $em,
        SortieRepository $sortieRepository,
        EtatRepository $etatRepository
    )
    {
        $this->em = $em;
        $this->sortieRepository = $sortieRepository;
        $this->etatRepository = $etatRepository;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Update Sortie.etat, pass an ID for the row to update (in the Sortie Table) and an Etat.id')
            ->addArgument('Sortie.id', InputArgument::REQUIRED, 'The ID of the Sortie to update')
            ->addArgument('Etat.id', InputArgument::REQUIRED, 'the ID of Etat you want to put in sortie.etat at the ID preselected')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            ' -----· • —– ٠ ✤ ٠ —– • ·-----',
            '⚜⚜  Updater of Sortie.etat  ⚜⚜ ',
            ' -----------------------------',
            '⚜ A Work of Art by TeamRocket ⚜ ',
            ' -----· • —– ٠ ✤ ٠ —– • ·-----',

        ]);

        $io = new SymfonyStyle($input, $output);

        $sortieID = $input->getArgument('Sortie.id');
        $etatID = $input->getArgument('Etat.id');

        $output->writeln('Targetted row in Sortie, ID : '.$sortieID);
        $output->writeln('Etat to put in, ID: '.$etatID);

        //finding targetted row in entity
        $rowToUpdate = $this->sortieRepository->findOneBy(['id'=>$sortieID]);
        //finding targetted Etat
        $newEtat = $this->etatRepository->findOneBy(['id'=>$etatID]);

        $output->writeln('You have updated : '.$rowToUpdate->getNom());
        $output->writeln('initial Sortie.etat : '.$rowToUpdate->getEtat()->getLibelle());
        $output->writeln('You choose to set the new Etat : '.$newEtat->getLibelle());

        $rowToUpdate->setEtat($newEtat);
        $this->em->flush();

        $updatedRow = $this->sortieRepository->findOneBy(['id'=>$sortieID]);
        $output->writeln('your entity has been updated : '.$updatedRow->getNom());
        $output->writeln('Etat : '.$updatedRow->getEtat()->getLibelle());

        $io->success('Command Successfully Executed :) The Rocket-Go TEAM thanks you for using it');

        return Command::SUCCESS;
    }
}
