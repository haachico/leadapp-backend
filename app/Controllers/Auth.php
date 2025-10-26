<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Auth extends ResourceController
{
    protected $format = 'json';

    public function login()
    {
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];
        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $userModel = new UserModel();
        $user = $userModel->where('email', $this->request->getVar('email'))->first();
        // Verify hashed password
        if (! $user || !password_verify($this->request->getVar('password'), $user['password'])) {
            return $this->failUnauthorized('Invalid email or password');
        }

    $key = env('JWT_SECRET') ?: 'your_secret_key';
        $payload = [
            'iss' => base_url(),
            'aud' => base_url(),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600, // 1 hour expiry
            'uid' => $user['id'],
            'role' => $user['role'],
            'email' => $user['email'],
        ];
        $token = JWT::encode($payload, $key, 'HS256');

        return $this->respond([
            'status' => 'success',
            'token'  => $token,
            'user'   => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);
    }
}
