<?php

declare(strict_types=1);

namespace DoctrineRowHashBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\LockCommandBundle\Command\LockableCommand;

#[AsCommand(name: self::NAME, description: '检查指定数据是否被篡改')]
class VerifyRowHashCommand extends LockableCommand
{
    private const NAME = 'app:row-hash';

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
        if (!is_string($className)) {
            $output->writeln('<error>className argument must be a string</error>');

            return Command::FAILURE;
        }

        /** @var class-string $className */
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
        $repository = $this->entityManager->getRepository($className);
        $request = $repository->createQueryBuilder('e')
            ->select($selectString)
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()->getResult()
        ;

        // $oldHashValue = $request[0]->getRowHash();
        if (!is_array($request) || !isset($request[0])) {
            $output->writeln('<error>No result found</error>');

            return Command::FAILURE;
        }

        /** @var mixed $rawResult */
        $rawResult = $request[0];
        if (!is_array($rawResult) || !array_key_exists('rowHash', $rawResult)) {
            $output->writeln('<error>Invalid result structure or missing rowHash field</error>');

            return Command::FAILURE;
        }

        /** @var array<string, mixed> $resultRow */
        $resultRow = $rawResult;
        $oldHashValue = $resultRow['rowHash'];
        $resultRow['rowHash'] = null;
        $serializedEntity = serialize($resultRow);
        $newHashValue = hash('sha256', $serializedEntity);

        if ($oldHashValue !== $newHashValue) {
            $idString = is_string($id) || is_numeric($id) ? (string) $id : 'unknown';
            $output->writeln($idString . '数据经过篡改');
        }

        return Command::SUCCESS;
    }
}
