<?php

namespace DoctrineRowHashBundle\Tests\Command;

use Doctrine\Persistence\Mapping\MappingException;
use DoctrineRowHashBundle\Command\VerifyRowHashCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(VerifyRowHashCommand::class)]
#[RunTestsInSeparateProcesses]
final class VerifyRowHashCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // Command test setup
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(VerifyRowHashCommand::class);

        return new CommandTester($command);
    }

    public function testArgumentClassName(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute([
                'className' => 'NonExistentClass',
                'id' => '1',
            ]);
            self::fail('Expected MappingException to be thrown');
        } catch (MappingException $e) {
            $this->assertStringContainsString('NonExistentClass', $e->getMessage());
        }
    }

    public function testArgumentId(): void
    {
        $commandTester = $this->getCommandTester();

        try {
            $commandTester->execute([
                'className' => 'NonExistentClass',
                'id' => '999',
            ]);
            self::fail('Expected MappingException to be thrown');
        } catch (MappingException $e) {
            $this->assertStringContainsString('NonExistentClass', $e->getMessage());
        }
    }
}
