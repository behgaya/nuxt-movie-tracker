<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

final class WatchedControllerTest extends ApiTestCase
{
    private const DUNE = ['id' => 438631, 'title' => 'Dune', 'poster_path' => '/dune.jpg', 'release_date' => '2021-09-15'];

    public function testAddIsIdempotentAndReturnsTheList(): void
    {
        $this->loginAs('alice');

        $this->request('POST', '/api/watched', self::DUNE);
        $list = $this->request('POST', '/api/watched', self::DUNE);

        self::assertResponseIsSuccessful();
        self::assertCount(1, $list);
        self::assertSame(['id', 'title', 'poster_path', 'release_date', 'watchedAt'], array_keys($list[0]));
        self::assertSame(self::DUNE, array_intersect_key($list[0], self::DUNE));
    }

    public function testAddValidatesTheMovie(): void
    {
        $this->loginAs('alice');

        $body = $this->request('POST', '/api/watched', ['id' => 1, 'title' => '  ']);
        self::assertResponseStatusCodeSame(400);
        self::assertSame('title must be a non-empty string', $body['message']);
    }

    public function testReviewSetsAndClearsRatingAndReview(): void
    {
        $this->loginAs('alice');
        $this->request('POST', '/api/watched', self::DUNE);

        $list = $this->request('PATCH', '/api/watched/438631', ['rating' => 5, 'review' => '  Loved it  ']);
        self::assertSame(5, $list[0]['rating']);
        self::assertSame('Loved it', $list[0]['review']);
        self::assertArrayHasKey('reviewedAt', $list[0]);

        $list = $this->request('PATCH', '/api/watched/438631', ['rating' => null, 'review' => '']);
        self::assertArrayNotHasKey('rating', $list[0]);
        self::assertArrayNotHasKey('review', $list[0]);

        $this->request('PATCH', '/api/watched/438631', ['rating' => 6]);
        self::assertResponseStatusCodeSame(400);

        $this->request('PATCH', '/api/watched/1', ['rating' => 3]);
        self::assertResponseStatusCodeSame(404);
    }

    public function testRemoveAndBulk(): void
    {
        $this->loginAs('alice');
        $this->request('POST', '/api/watched', self::DUNE);

        $list = $this->request('POST', '/api/watched/bulk', [
            'add' => [['id' => 1, 'title' => 'One'], ['id' => 2, 'title' => 'Two'], ['id' => 2, 'title' => 'Two again']],
            'remove' => [438631],
        ]);
        self::assertSame([2, 1], array_column($list, 'id'));

        $list = $this->request('DELETE', '/api/watched/1');
        self::assertSame([2], array_column($list, 'id'));
    }

    public function testBulkRejectsTooManyItems(): void
    {
        $this->loginAs('alice');

        $body = $this->request('POST', '/api/watched/bulk', ['remove' => range(1, 501)]);
        self::assertResponseStatusCodeSame(400);
        self::assertSame('add and remove can hold at most 500 items in total', $body['message']);
    }

    public function testListsAreSeparatedPerUser(): void
    {
        $this->loginAs('alice');
        $this->request('POST', '/api/watched', self::DUNE);

        $this->loginAs('bob');
        self::assertSame([], $this->request('GET', '/api/watched'));
    }
}
