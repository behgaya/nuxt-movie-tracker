<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

final class WantControllerTest extends ApiTestCase
{
    private const DUNE = ['id' => 438631, 'title' => 'Dune', 'poster_path' => '/dune.jpg', 'release_date' => '2021-09-15'];
    private const ALIEN = ['id' => 348, 'title' => 'Alien', 'poster_path' => null];

    public function testAddIsIdempotentAndKeepsWantedMoviesOutOfTheWatchedList(): void
    {
        $this->loginAs('alice');

        $this->request('POST', '/api/want', self::DUNE);
        $list = $this->request('POST', '/api/want', self::DUNE);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $list);
        self::assertSame(['id', 'title', 'poster_path', 'release_date', 'addedAt'], array_keys($list[0]));
        self::assertSame([], $this->request('GET', '/api/watched'));
    }

    public function testAddValidatesTheMovie(): void
    {
        $this->loginAs('alice');

        $body = $this->request('POST', '/api/want', ['id' => 1, 'title' => '  ']);
        self::assertResponseStatusCodeSame(400);
        self::assertSame('title must be a non-empty string', $body['message']);
    }

    public function testWatchingAWantedMovieMovesIt(): void
    {
        $this->loginAs('alice');
        $this->request('POST', '/api/want', self::DUNE);
        $this->request('POST', '/api/want', self::ALIEN);

        $watched = $this->request('POST', '/api/watched', self::DUNE);
        self::assertSame([438631], array_column($watched, 'id'));
        self::assertArrayHasKey('watchedAt', $watched[0]);
        self::assertArrayNotHasKey('addedAt', $watched[0]);
        self::assertSame([348], array_column($this->request('GET', '/api/want'), 'id'));

        // Bulk add moves wanted movies too
        $watched = $this->request('POST', '/api/watched/bulk', ['add' => [self::ALIEN]]);
        self::assertSame([348, 438631], array_column($watched, 'id'));
        self::assertSame([], $this->request('GET', '/api/want'));
    }

    public function testAWatchedMovieCannotBeWanted(): void
    {
        $this->loginAs('alice');
        $this->request('POST', '/api/watched', self::DUNE);

        self::assertSame([], $this->request('POST', '/api/want', self::DUNE));
        self::assertCount(1, $this->request('GET', '/api/watched'));
    }

    public function testEachListOnlyRemovesAndReviewsItsOwnMovies(): void
    {
        $this->loginAs('alice');
        $this->request('POST', '/api/want', self::DUNE);

        // The watched routes leave a wanted movie alone
        $this->request('PATCH', '/api/watched/438631', ['rating' => 5]);
        self::assertResponseStatusCodeSame(404);
        $this->request('DELETE', '/api/watched/438631');
        $this->request('POST', '/api/watched/bulk', ['remove' => [438631]]);
        self::assertCount(1, $this->request('GET', '/api/want'));

        self::assertSame([], $this->request('DELETE', '/api/want/438631'));
    }

    public function testListsAreSeparatedPerUserAndNeedALogin(): void
    {
        $this->request('GET', '/api/want');
        self::assertResponseStatusCodeSame(401);

        $this->loginAs('alice');
        $this->request('POST', '/api/want', self::DUNE);

        $this->loginAs('bob');
        self::assertSame([], $this->request('GET', '/api/want'));
    }
}
