<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Boots a client against an empty test database (app_test) and adds JSON helpers */
abstract class ApiTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot(); // keep container overrides (e.g. a mocked TMDB client) between requests

        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $connection->executeStatement('TRUNCATE "user", watched_movie RESTART IDENTITY CASCADE');
        static::getContainer()->get('cache.app')->clear();
    }

    protected function request(string $method, string $uri, ?array $body = null): array
    {
        $this->client->request($method, $uri, server: ['CONTENT_TYPE' => 'application/json'], content: null === $body ? null : json_encode($body));

        return json_decode($this->client->getResponse()->getContent() ?: 'null', true) ?? [];
    }

    protected function loginAs(string $username): User
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = new User($username, 'not-used');
        $em->persist($user);
        $em->flush();
        $this->client->loginUser($user);

        return $user;
    }
}
