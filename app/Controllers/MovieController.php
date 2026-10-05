<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Movie;
use App\Models\UserList;

class MovieController {
    public function home(Request $request): void {
        $featured = Movie::getFeatured();
        $trending = Movie::getByCategory('trending', 10);
        $popular = Movie::getByCategory('popular', 10);
        $action = Movie::getByCategory('action', 10);
        $drama = Movie::getByCategory('drama', 10);
        $user = Session::getUser();
        $sessionId = Session::getId();
        $myList = UserList::getMovies($user['id'] ?? null, $sessionId);
        $uploads = Movie::getUploads(12);

        Response::view('home', [
            'title' => 'MyFlix — Watch Movies Together',
            'featured' => $featured,
            'trending' => $trending,
            'popular' => $popular,
            'action' => $action,
            'drama' => $drama,
            'uploads' => $uploads,
            'myList' => $myList,
            'user' => $user
        ]);
    }

    public function detail(Request $request, string $idOrSlug): void {
        $movie = is_numeric($idOrSlug) ? Movie::findById((int)$idOrSlug) : Movie::findBySlug($idOrSlug);
        if (!$movie) {
            if ($request->isJson()) {
                Response::error('Movie not found', 404);
            }
            Response::view('404', ['title' => 'Movie Not Found - MyFlix'], 404);
        }

        $user = Session::getUser();
        $sessionId = Session::getId();
        $inList = UserList::isInList($user['id'] ?? null, $sessionId, $movie['id']);

        if ($request->isJson()) {
            $canDelete = $user
                && !empty($movie['owner_user_id'])
                && (int)$movie['owner_user_id'] === (int)$user['id'];

            Response::json(array_merge($movie, [
                'in_my_list' => $inList,
                'can_delete' => (bool)$canDelete
            ]));
        }

        Response::view('movie-detail', [
            'title' => $movie['title'] . ' — MyFlix',
            'movie' => $movie,
            'inList' => $inList,
            'user' => $user
        ]);
    }

    public function search(Request $request): void {
        $query = $request->query('q', '');
        if (trim($query) === '') {
            Response::json(['results' => []]);
        }
        $movies = Movie::search($query, 20);
        Response::json(['results' => $movies, 'count' => count($movies)]);
    }

    public function toggleMyList(Request $request): void {
        $movieId = (int)$request->input('movie_id', 0);
        if ($movieId <= 0) {
            Response::error('Invalid movie ID', 422);
        }

        $movie = Movie::findById($movieId);
        if (!$movie) {
            Response::error('Movie not found', 404);
        }

        $user = Session::getUser();
        $sessionId = Session::getId();
        $inList = UserList::toggle($user['id'] ?? null, $sessionId, $movieId);

        Response::json([
            'success' => true,
            'in_list' => $inList,
            'movie' => $movie,
            'message' => $inList ? 'Added to My List' : 'Removed from My List'
        ]);
    }

    public function getMyList(Request $request): void {
        $user = Session::getUser();
        $sessionId = Session::getId();
        $movies = UserList::getMovies($user['id'] ?? null, $sessionId);
        Response::json(['movies' => $movies]);
    }
}
