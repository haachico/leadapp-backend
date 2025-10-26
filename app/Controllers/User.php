<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;

class User extends ResourceController
{
    protected $format = 'json';
    protected $userId = null;
    protected $role = null;

    protected function authorizeAdmin()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $jwt = $matches[1];
            try {
                $key = env('JWT_SECRET') ?: 'your_secret_key';
                $decoded = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key($key, 'HS256'));
                $this->userId = $decoded->uid ?? null;
                $this->role = $decoded->role ?? null;
                if ($this->role !== 'admin') {
                    return $this->failForbidden('Admin access required');
                }
                return null;
            } catch (\Exception $e) {
                return $this->failUnauthorized('Invalid or expired token');
            }
        }
        return $this->failUnauthorized('Missing or invalid Authorization header');
    }

    // List all users (admin only)
    public function index()
    {
        if ($fail = $this->authorizeAdmin()) {
            return $fail;
        }
        $userModel = new UserModel();
        $users = $userModel->findAll();
        // Remove password from each user before sending
        foreach ($users as &$user) {
            unset($user['password']);
        }
        return $this->respond($users);
    }

    // Add a new user (admin only)
    public function create()
    {
        if ($fail = $this->authorizeAdmin()) {
            return $fail;
        }
        $rules = [
            'name'     => 'required',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required',
            'role'     => 'required|in_list[admin,user]',
        ];
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        if (! $this->validate($rules, $data)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
    $userModel = new UserModel();
    // Hash the password before storing
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    $userModel->insert($data);
    $user = $userModel->find($userModel->getInsertID());
    return $this->respondCreated($user);
    }

    // Edit/update user (admin only)
    public function update($id = null)
    {
        if ($fail = $this->authorizeAdmin()) {
            return $fail;
        }
        if ($id === null) {
            return $this->failValidationErrors('User ID is required');
        }
        $userModel = new UserModel();
        $user = $userModel->find($id);
        if (! $user) {
            return $this->failNotFound('User not found');
        }
        $data = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $rules = [
            'name'     => 'permit_empty',
            'email'    => 'permit_empty|valid_email',
            'password' => 'permit_empty',
            'role'     => 'permit_empty|in_list[admin,user]',
        ];
        if (! $this->validate($rules, $data)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        if (isset($data['email'])) {
            $existing = $userModel->where('email', $data['email'])->where('id !=', $id)->first();
            if ($existing) {
                return $this->failResourceExists('User email must be unique');
            }
        }
        if (isset($data['password'])) {
            // Hash the password if provided
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $userModel->update($id, $data);
        $updatedUser = $userModel->find($id);
        return $this->respond($updatedUser);
    }

      public function delete($id = null)
    {
        if ($fail = $this->authorizeAdmin()) {
            return $fail;
        }
        if ($id === null) {
            return $this->failValidationErrors('User ID is required');
        }
        $userModel = new UserModel();
        $user = $userModel->find($id);
        if (! $user) {
            return $this->failNotFound('User not found');
        }
        // Check for leads assigned to this user
        $leadModel = new \App\Models\LeadModel();
        $assignedLeads = $leadModel->where('assigned_to', $id)->countAllResults();
        if ($assignedLeads > 0) {
            return $this->failResourceExists('Cannot delete user: This user is assigned to one or more leads. Please reassign or remove those leads first.');
        }
        $userModel->delete($id);
        return $this->respondDeleted(['id' => $id, 'message' => 'User deleted']);
    }
}
