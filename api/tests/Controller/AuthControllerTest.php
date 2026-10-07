<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

final class AuthControllerTest extends ApiTestCase
{
    public function testRegisterLogsInAndNormalizesTheUsername(): void
    {
        $body = $this->request('POST', '/api/auth/register', ['username' => ' Alice ', 'password' => 'password123']);
        self::assertResponseIsSuccessful();
        self::assertSame(['username' => 'alice'], $body);

        self::assertSame(['user' => ['username' => 'alice']], $this->request('GET', '/api/auth/me'));
    }

    public function testRegisterRejectsInvalidAndTakenUsernames(): void
    {
        $body = $this->request('POST', '/api/auth/register', ['username' => 'a!', 'password' => 'password123']);
        self::assertResponseStatusCodeSame(400);
        self::assertSame('Username must be 3–30 characters: letters, numbers or _', $body['message']);

        $body = $this->request('POST', '/api/auth/register', ['username' => 'alice', 'password' => 'short']);
        self::assertResponseStatusCodeSame(400);
        self::assertSame('Password must be at least 8 characters', $body['message']);

        $this->request('POST', '/api/auth/register', ['username' => 'alice', 'password' => 'password123']);
        $body = $this->request('POST', '/api/auth/register', ['username' => 'ALICE', 'password' => 'password123']);
        self::assertResponseStatusCodeSame(409);
        self::assertSame('That username is already taken', $body['message']);
    }

    public function testLoginAndLogout(): void
    {
        $this->request('POST', '/api/auth/register', ['username' => 'alice', 'password' => 'password123']);
        $this->request('POST', '/api/auth/logout');
        self::assertResponseStatusCodeSame(204);
        self::assertSame(['user' => null], $this->request('GET', '/api/auth/me'));

        $body = $this->request('POST', '/api/auth/login', ['username' => 'alice', 'password' => 'wrong-password']);
        self::assertResponseStatusCodeSame(401);
        self::assertSame('Invalid username or password', $body['message']);

        // Usernames are case-insensitive
        $body = $this->request('POST', '/api/auth/login', ['username' => 'Alice', 'password' => 'password123']);
        self::assertResponseIsSuccessful();
        self::assertSame(['username' => 'alice'], $body);
    }

    public function testApiRoutesRequireLogin(): void
    {
        foreach ([['GET', '/api/watched'], ['GET', '/api/movies'], ['POST', '/api/watched/bulk']] as [$method, $uri]) {
            $this->request($method, $uri);
            self::assertResponseStatusCodeSame(401, "$method $uri");
        }
    }
}
