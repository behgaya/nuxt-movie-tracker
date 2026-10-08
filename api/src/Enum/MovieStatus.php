<?php

namespace App\Enum;

/** Which of a user's two lists a movie is in. Stored as its string value in watched_movie.status. */
enum MovieStatus: string
{
    case Watched = 'watched';
    case Want = 'want';
}
