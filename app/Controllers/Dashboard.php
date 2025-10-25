<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\LeadModel;
use App\Models\CustomerModel;
use CodeIgniter\RESTful\ResourceController;

class Dashboard extends ResourceController
{
    protected $format = 'json';
    protected $userId = null;
    protected $role = null;

    protected function authorizeRequest()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $jwt = $matches[1];
            try {
                $key = env('JWT_SECRET') ?: 'your_secret_key';
                $decoded = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key($key, 'HS256'));
                $this->userId = $decoded->uid ?? null;
                $this->role = $decoded->role ?? null;
                return null;
            } catch (\Exception $e) {
                return $this->failUnauthorized('Invalid or expired token');
            }
        }
        return $this->failUnauthorized('Missing or invalid Authorization header');
    }

    public function summary()
    {
        if ($fail = $this->authorizeRequest()) {
            return $fail;
        }
        $leadModel = new LeadModel();
        $customerModel = new CustomerModel();
        $userModel = new UserModel();

        if ($this->role === 'admin') {
            $totalLeads = $leadModel->countAllResults();
            $totalCustomers = $customerModel->countAllResults();
            $totalUsers = $userModel->countAllResults();
            return $this->respond([
                'total_leads' => $totalLeads,
                'total_customers' => $totalCustomers,
                'total_users' => $totalUsers,
            ]);
        } elseif ($this->role === 'user') {
            $totalLeads = $leadModel->where('assigned_to', $this->userId)->countAllResults();
            $totalCustomers = $customerModel->where('assigned_to', $this->userId)->countAllResults();
            return $this->respond([
                'total_leads' => $totalLeads,
                'total_customers' => $totalCustomers,
            ]);
        } else {
            return $this->failForbidden('Role not recognized');
        }
    }
}
