<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;

final class FolderControllerTest extends ApiTestCase
{
    private const DUNE = ['id' => 438631, 'title' => 'Dune', 'poster_path' => '/dune.jpg', 'release_date' => '2021-09-15'];
    private const ALIEN = ['id' => 348, 'title' => 'Alien', 'poster_path' => null];

    public function testCreateListsFoldersByNameAndValidates(): void
    {
        $this->loginAs('alice');

        $this->request('POST', '/api/folders', ['name' => '  Horror night ']);
        $list = $this->request('POST', '/api/folders', ['name' => 'Favorites', 'isPublic' => true]);

        self::assertResponseIsSuccessful();
        self::assertSame(['Favorites', 'Horror night'], array_column($list, 'name'));
        self::assertSame(['id', 'name', 'isPublic', 'movieIds', 'posters'], array_keys($list[0]));
        self::assertTrue($list[0]['isPublic']);
        self::assertFalse($list[1]['isPublic']);

        $body = $this->request('POST', '/api/folders', ['name' => '   ']);
        self::assertResponseStatusCodeSame(400);
        self::assertSame('name must not be empty', $body['message']);

        $body = $this->request('POST', '/api/folders', ['name' => 'Favorites']);
        self::assertResponseStatusCodeSame(409);
        self::assertSame('You already have a folder with that name', $body['message']);
    }

    public function testAddAndRemoveMovies(): void
    {
        $this->loginAs('alice');
        $id = $this->request('POST', '/api/folders', ['name' => 'Sci-fi'])[0]['id'];

        $this->request('POST', "/api/folders/$id/movies", self::DUNE);
        $this->request('POST', "/api/folders/$id/movies", self::ALIEN);
        $list = $this->request('POST', "/api/folders/$id/movies", self::DUNE);
        self::assertSame([348, 438631], $list[0]['movieIds']); // by title; adding twice does nothing
        self::assertSame(['/dune.jpg'], $list[0]['posters']);

        $folder = $this->request('GET', "/api/folders/$id");
        self::assertSame(['Alien', 'Dune'], array_column($folder['movies'], 'title'));
        self::assertSame('alice', $folder['owner']);
        self::assertTrue($folder['isOwner']);

        $list = $this->request('DELETE', "/api/folders/$id/movies/348");
        self::assertSame([438631], $list[0]['movieIds']);
    }

    public function testBulkAddsAndRemovesManyMovies(): void
    {
        $this->loginAs('alice');
        $id = $this->request('POST', '/api/folders', ['name' => 'Mixed'])[0]['id'];
        $this->request('POST', "/api/folders/$id/movies", self::DUNE);

        $list = $this->request('POST', "/api/folders/$id/movies/bulk", [
            'add' => [['id' => 1, 'title' => 'One'], ['id' => 2, 'title' => 'Two'], ['id' => 2, 'title' => 'Two again']],
            'remove' => [438631],
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame([1, 2], $list[0]['movieIds']); // by title; the second "2" changes nothing

        $body = $this->request('POST', "/api/folders/$id/movies/bulk", ['remove' => range(1, 501)]);
        self::assertResponseStatusCodeSame(400);
        self::assertSame('add and remove can hold at most 500 items in total', $body['message']);

        $this->loginAs('bob');
        $this->request('POST', "/api/folders/$id/movies/bulk", ['add' => [self::ALIEN]]);
        self::assertResponseStatusCodeSame(404);
    }

    public function testAMovieInSeveralFoldersIsStoredOnce(): void
    {
        $this->loginAs('alice');
        $a = $this->request('POST', '/api/folders', ['name' => 'A'])[0]['id'];
        $this->request('POST', "/api/folders/$a/movies", self::DUNE);
        $this->loginAs('bob');
        $b = $this->request('POST', '/api/folders', ['name' => 'B'])[0]['id'];
        $this->request('POST', "/api/folders/$b/movies", self::DUNE);

        $connection = static::getContainer()->get('doctrine.dbal.default_connection');
        self::assertSame(1, (int) $connection->fetchOne('SELECT COUNT(*) FROM movie'));
        self::assertSame(2, (int) $connection->fetchOne('SELECT COUNT(*) FROM folder_movie'));
    }

    public function testRenameMakePublicAndDelete(): void
    {
        $this->loginAs('alice');
        $this->request('POST', '/api/folders', ['name' => 'Taken']);
        $id = $this->request('POST', '/api/folders', ['name' => 'Old'])[0]['id'];

        $this->request('PUT', "/api/folders/$id", ['name' => 'Taken']);
        self::assertResponseStatusCodeSame(409);

        // Keeping its own name is fine
        $list = $this->request('PUT', "/api/folders/$id", ['name' => 'Old', 'isPublic' => true]);
        self::assertTrue($list[0]['isPublic']);

        $list = $this->request('DELETE', "/api/folders/$id");
        self::assertSame(['Taken'], array_column($list, 'name'));
        $this->request('GET', "/api/folders/$id");
        self::assertResponseStatusCodeSame(404);
    }

    public function testOthersCanViewOnlyPublicFoldersAndNeverChangeThem(): void
    {
        $this->loginAs('alice');
        [$private, $public] = array_column([
            $this->request('POST', '/api/folders', ['name' => 'Private'])[0],
            $this->request('POST', '/api/folders', ['name' => 'Public', 'isPublic' => true])[1],
        ], 'id');

        $this->loginAs('bob');
        self::assertSame([], $this->request('GET', '/api/folders'));

        // A private folder looks the same as one that doesn't exist
        $body = $this->request('GET', "/api/folders/$private");
        self::assertResponseStatusCodeSame(404);
        self::assertSame('Folder not found', $body['message']);
        $this->request('POST', "/api/folders/$private/movies", self::DUNE);
        self::assertResponseStatusCodeSame(404);

        $folder = $this->request('GET', "/api/folders/$public");
        self::assertResponseIsSuccessful();
        self::assertFalse($folder['isOwner']);

        $this->request('POST', "/api/folders/$public/movies", self::DUNE);
        self::assertResponseStatusCodeSame(403);
        $this->request('PUT', "/api/folders/$public", ['name' => 'Mine now']);
        self::assertResponseStatusCodeSame(403);
        $this->request('DELETE', "/api/folders/$public");
        self::assertResponseStatusCodeSame(403);
    }

    public function testLoggedOutVisitorsCanOnlyViewPublicFolders(): void
    {
        $this->loginAs('alice');
        $private = $this->request('POST', '/api/folders', ['name' => 'Private'])[0]['id'];
        $public = $this->request('POST', '/api/folders', ['name' => 'Public', 'isPublic' => true])[1]['id'];
        $this->request('POST', '/api/auth/logout');

        $folder = $this->request('GET', "/api/folders/$public");
        self::assertResponseIsSuccessful();
        self::assertSame('Public', $folder['name']);
        self::assertFalse($folder['isOwner']);

        $this->request('GET', "/api/folders/$private");
        self::assertResponseStatusCodeSame(404);
        $this->request('GET', '/api/folders');
        self::assertResponseStatusCodeSame(401);
        $this->request('POST', "/api/folders/$public/movies", self::DUNE);
        self::assertResponseStatusCodeSame(401);
    }
}
