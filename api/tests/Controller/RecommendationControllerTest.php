<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class RecommendationControllerTest extends ApiTestCase
{
    /** What the fake TMDB recommends for each movie id */
    private const TMDB = [
        1 => [10, 11, 2],
        2 => [11, 12],
        3 => [13],
    ];

    /** @var list<string> URLs the API asked TMDB for */
    private array $tmdbCalls = [];

    protected function setUp(): void
    {
        parent::setUp();

        static::getContainer()->set('tmdb.client', new MockHttpClient(function (string $method, string $url) {
            $this->tmdbCalls[] = $url;
            preg_match('#/movie/(\d+)/recommendations#', $url, $match);
            $ids = self::TMDB[(int) $match[1]] ?? null;

            return null === $ids
                ? new JsonMockResponse(['status_message' => 'Not found'], ['http_code' => 404])
                : new JsonMockResponse(['results' => array_map(fn (int $id) => ['id' => $id, 'title' => "Movie $id", 'poster_path' => null, 'overview' => '', 'vote_average' => 7.5], $ids)]);
        }, 'https://api.themoviedb.org'));

        $this->loginAs('alice');
    }

    public function testNothingWatchedMeansNoRecommendations(): void
    {
        self::assertSame([], $this->request('GET', '/api/recommendations'));
        self::assertResponseIsSuccessful();
        self::assertCount(0, $this->tmdbCalls);
    }

    public function testRanksByHowManyAndHowWellRatedSeedsRecommendIt(): void
    {
        $this->request('POST', '/api/watched/bulk', ['add' => [
            ['id' => 1, 'title' => 'Loved'],
            ['id' => 2, 'title' => 'Unrated'],
            ['id' => 3, 'title' => 'Disliked'],
        ]]);
        $this->request('PATCH', '/api/watched/1', ['rating' => 5]);
        $this->request('PATCH', '/api/watched/3', ['rating' => 1]);

        $list = $this->request('GET', '/api/recommendations');

        self::assertResponseIsSuccessful();
        // 11 comes from both seeds; 10 from the 5-star seed beats 12 from the unrated one; 2 is already watched
        self::assertSame([11, 10, 12], array_column($list, 'id'));
        self::assertSame(['Loved', 'Unrated'], $list[0]['because']);
        self::assertSame(['id', 'title', 'poster_path', 'release_date', 'overview', 'vote_average', 'because'], array_keys($list[0]));
        // A movie rated 1 is never used as a seed
        self::assertCount(0, array_filter($this->tmdbCalls, fn (string $url) => str_contains($url, '/movie/3/')));
    }

    public function testSkipsSeedsTmdbDoesNotKnow(): void
    {
        $this->request('POST', '/api/watched/bulk', ['add' => [['id' => 1, 'title' => 'Known'], ['id' => 999, 'title' => 'Gone']]]);

        $list = $this->request('GET', '/api/recommendations');

        self::assertResponseIsSuccessful();
        self::assertSame([10, 11, 2], array_column($list, 'id'));
    }

    public function testRequiresLogin(): void
    {
        $this->request('POST', '/api/auth/logout');
        $this->request('GET', '/api/recommendations');

        self::assertResponseStatusCodeSame(401);
    }
}
