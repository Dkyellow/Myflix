<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\User;

class AuthController {
    public function me(Request $request): void {
        $user = Session::getUser();
        Response::json([
            'authenticated' => $user !== null,
            'user' => $user,
            'csrf_token' => Session::getCsrfToken()
        ]);
    }

    public function login(Request $request): void {
        $email = $request->input('email');
        $password = $request->input('password');

        if (!$email || !$password) {
            Response::error('Email and password are required', 422);
        }

        $user = User::findByEmail($email);
        if (!$user || !User::verifyPassword($password, $user['password_hash'])) {
            Response::error('Invalid email or password', 401);
        }

        Session::setUser($user);
        Response::success([
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar_color' => $user['avatar_color']
            ]
        ], 'Logged in successfully');
    }

    public function register(Request $request): void {
        $username = trim($request->input('username', ''));
        $email = trim($request->input('email', ''));
        $password = $request->input('password', '');

        if (strlen($username) < 2) {
            Response::error('Username must be at least 2 characters', 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address', 422);
        }
        if (strlen($password) < 6) {
            Response::error('Password must be at least 6 characters', 422);
        }

        if (User::findByEmail($email)) {
            Response::error('An account with this email already exists', 409);
        }
        if (User::findByUsername($username)) {
            Response::error('This username is already taken', 409);
        }

        $user = User::create($username, $email, $password);
        Session::setUser($user);

        Response::success([
            'user' => $user
        ], 'Account created successfully');
    }

    public function logout(Request $request): void {
        Session::remove('user');
        Response::success([], 'Logged out successfully');
    }
}
