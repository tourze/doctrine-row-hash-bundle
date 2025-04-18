<?php

namespace DoctrineRowHashBundle\MessageHandler;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use DoctrineRowHashBundle\Message\RowHashMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RowHashHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(RowHashMessage $message): void
    {
        $columnNames = $message->getColumnNames();
        for ($i = 0; $i < count($columnNames); ++$i) {
            $columnNames[$i] = 'e.' . $columnNames[$i];
        }
        $selectString = implode(', ', $columnNames);
        $qb = $this->entityManager->createQueryBuilder()
            ->select($selectString)
            ->from($message->getClassName(), 'e')
            ->where('e.id = :id')
            ->setParameter('id', $message->getId())
            ->getQuery()->getResult();

        // 得到哈希
        $serializedEntity = serialize($qb[0]);
        $hashValue = hash('sha256', $serializedEntity);

        // 修改数据
        $sql = 'UPDATE ' . $message->getTableName() . ' SET row_hash = :value1 WHERE id = :id';
        $parameters = [
            'value1' => $hashValue,
            'id' => $message->getId(),
        ];
        $this->connection->executeStatement($sql, $parameters);
    }
}
