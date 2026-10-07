<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;

final class MovieControllerTest extends ApiTestCase
{
    /** @var list<string> URLs the API asked TMDB for */
    private array $tmdbCalls = [];

    protected function setUp(): void
    {
        parent::setUp();

        static::getContainer()->set('tmdb.client', new MockHttpClient(function (string $method, string $url) {
            $this->tmdbCalls[] = $url;

            return str_contains($url, '/movie/999')
                ? new JsonMockResponse(['status_message' => 'Not found'], ['http_code' => 404])
                : new JsonMockResponse(['page' => 1, 'results' => [['id' => 1, 'title' => 'Dune']], 'total_pages' => 1, 'total_results' => 1]);
        }, 'https://api.themoviedb.org'));

        $this->loginAs('alice');
    }

    public function testSearchCallsTmdbOnceThenUsesTheCache(): void
    {
        $body = $this->request('GET', '/api/movies?q=dune&page=2');
        $this->request('GET', '/api/movies?q=dune&page=2');

        self::assertResponseIsSuccessful();
        self::assertSame('Dune', $body['results'][0]['title']);
        self::assertCount(1, $this->tmdbCalls);
        self::assertStringContainsString('/3/search/movie?query=dune&page=2&api_key=test-api-key', $this->tmdbCalls[0]);
    }

    public function testPopularWhenThereIsNoQuery(): void
    {
        $this->request('GET', '/api/movies');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('/3/movie/popular?page=1', $this->tmdbCalls[0]);
    }

    public function testRejectsPagesOutsideTmdbRange(): void
    {
        $body = $this->request('GET', '/api/movies?page=501');

        self::assertResponseStatusCodeSame(400);
        self::assertSame('page must be an integer between 1 and 500', $body['message']);
    }

    public function testUnknownMovieIs404(): void
    {
        $body = $this->request('GET', '/api/movies/999');

        self::assertResponseStatusCodeSame(404);
        self::assertSame('Movie not found', $body['message']);
    }
}
