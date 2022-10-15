<?php

namespace SKukunin\DatabaseActivityBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use SKukunin\DatabaseActivityBundle\Database\ConnectionInterface;
use SKukunin\DatabaseActivityBundle\Service\ChangeLogService;
use SKukunin\DatabaseActivityBundle\Tests\User;
use Symfony\Component\Security\Core\Security;

class ChangeLogServiceTest extends TestCase
{
    private $connection;
    private $service;

    public function setUp(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $this->service = new ChangeLogService($connection);
        $this->connection = $connection;
    }

    public function testLogEntityInsert(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with($this->anything(), $this->anything(), ChangeLogService::ACTION_ADD, '-')
        ;

        $this->service->logEntityInsert('user', 1);
    }

    public function testLogEntityUpdate(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with($this->anything(), $this->anything(), ChangeLogService::ACTION_UPDATE, '-')
        ;

        $this->service->logEntityUpdate('user', 1, 'username',
            'admin', 'test_user');
    }

    public function testLogEntityDelete(): void
    {
        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with($this->anything(), $this->anything(), ChangeLogService::ACTION_DELETE, '-')
        ;

        $this->service->logEntityDelete('user', 1);
    }

    public function testLogWithUser(): void
    {
        $user = new User('test@gmail.com');
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $this->service->setSecurity($security);

        $this->connection
            ->expects($this->once())
            ->method('insert')
            ->with($this->anything(), $this->anything(), ChangeLogService::ACTION_DELETE, 'test@gmail.com')
        ;

        $this->service->logEntityDelete('user', 1);
    }
}