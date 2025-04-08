<?php

namespace DoctrineRowHashBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\LockCommandBundle\Command\LockableCommand;

#[AsCommand(name: 'app:row-hash', description: '检查指定数据是否被篡改')]
class VerifyRowHashCommand extends LockableCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('className', InputArgument::OPTIONAL, description: 'className');
        $this->addArgument('id', InputArgument::OPTIONAL, description: '数据id');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $className = $input->getArgument('className');

        $metaData = $this->entityManager->getClassMetadata($className);
        $columnNames = $metaData->getFieldNames();

        $id = $input->getArgument('id');

        //        $request = $this->entityManager->createQueryBuilder()->select('e')->from($className, 'e')
        //            ->where('e.id =:id')
        //            ->setParameter('id', $id)
        //            ->getQuery()->getResult();

        for ($i = 0; $i < count($columnNames); ++$i) {
            $columnNames[$i] = 'e.' . $columnNames[$i];
        }
        $selectString = implode(', ', $columnNames);
        $request = $this->entityManager->createQueryBuilder()
            ->select($selectString)
            ->from($className, 'e')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()->getResult();

        // $oldHashValue = $request[0]->getRowHash();
        $oldHashValue = $request[0]['rowHash'];
        $request[0]['rowHash'] = null;
        $serializedEntity = serialize($request[0]);
        $newHashValue = hash('sha256', $serializedEntity);

        if ($oldHashValue != $newHashValue) {
            $output->writeln($id . '数据经过篡改');
        }

        return Command::SUCCESS;
    }
}
