<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Talks to the TMDB v3 API. The token only ever lives on the server.
 * Responses are cached; failed calls throw, so errors are never cached.
 */
final class TmdbClient
{
    public function __construct(
        #[Target('tmdb.client')]
        private readonly HttpClientInterface $client,
        private readonly CacheInterface $cache,
        #[Autowire('%env(TMDB_TOKEN)%')]
        private readonly string $token,
    ) {
    }

    /** Popular movies, or search results when $query is set (cached for one hour) */
    public function movies(string $query, int $page): array
    {
        $key = 'tmdb.movies.'.md5($query).'.'.$page;

        return $this->cache->get($key, function (ItemInterface $item) use ($query, $page) {
            $item->expiresAfter(3600);

            return '' === $query
                ? $this->get('/3/movie/popular', ['page' => $page])
                : $this->get('/3/search/movie', ['query' => $query, 'page' => $page]);
        });
    }

    /** One movie with credits and videos (cached for one day: details rarely change) */
    public function movie(int $id): array
    {
        return $this->cache->get('tmdb.movie.'.$id, function (ItemInterface $item) use ($id) {
            $item->expiresAfter(86400);

            return $this->get('/3/movie/'.$id, ['append_to_response' => 'credits,videos']);
        });
    }

    private function get(string $path, array $query): array
    {
        // v4 read access tokens are JWTs (contain dots); v3 API keys are 32-char hex strings
        $isV4 = str_contains($this->token, '.');

        try {
            return $this->client->request('GET', $path, [
                'query' => $isV4 ? $query : $query + ['api_key' => $this->token],
                'auth_bearer' => $isV4 ? $this->token : null,
            ])->toArray();
        } catch (HttpExceptionInterface $e) {
            if (404 === $e->getResponse()->getStatusCode()) {
                throw new NotFoundHttpException('Movie not found', $e);
            }
            throw new HttpException(502, 'TMDB is not available right now', $e);
        } catch (ExceptionInterface $e) {
            throw new HttpException(502, 'TMDB is not available right now', $e);
        }
    }
}
