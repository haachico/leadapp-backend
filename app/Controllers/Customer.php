<?php

namespace App\Controllers;

use App\Models\CustomerModel;
use CodeIgniter\RESTful\ResourceController;


class Customer extends ResourceController
{
    protected $format = 'json';
    protected $userId = null;
    protected $role = null;

    /**
     * DRY JWT validation and user/role extraction
     * Returns null if authorized, or a fail response if not
     */
    protected function authorizeRequest()
    {
        $authHeader = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $jwt = $matches[1];
            try {
                $key = env('JWT_SECRET');
                if (!$key) {
                    return $this->failServerError('JWT_SECRET is not set in your .env file. Please set a strong secret key.');
                }
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

    public function index()
    {
        if ($fail = $this->authorizeRequest()) {
            return $fail;
        }
        $customerModel = new CustomerModel();
        if ($this->role === 'admin') {
            $customers = $customerModel->findAll();
        } elseif ($this->role === 'user') {
            $customers = $customerModel->where('assigned_to', $this->userId)->findAll();
        } else {
            return $this->failUnauthorized('Role not recognized');
        }
        return $this->respond($customers);
    }

    public function create()
    {
        if ($fail = $this->authorizeRequest()) {
            return $fail;
        }
        $rules = [
            'name'        => 'required',
            'email'       => 'required|valid_email',
            'assigned_to' => 'required|integer',
        ];
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        if (! $this->validate($rules, $data)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        $customerModel = new CustomerModel();
        $existing = $customerModel->where('email', $data['email'])->first();
        if ($existing) {
            return $this->failResourceExists('Customer email must be unique');
        }
        $customerModel->insert($data);
        $customer = $customerModel->find($customerModel->getInsertID());
        return $this->respondCreated($customer);
    }

    public function update($id = null)
    {
        if ($fail = $this->authorizeRequest()) {
            return $fail;
        }
        if ($id === null) {
            return $this->failValidationErrors('Customer ID is required');
        }
        $customerModel = new CustomerModel();
        $customer = $customerModel->find($id);
        if (! $customer) {
            return $this->failNotFound('Customer not found');
        }
        $data = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $rules = [
            'name'        => 'permit_empty',
            'email'       => 'permit_empty|valid_email',
            'phone'       => 'permit_empty',
            'lead_id'     => 'permit_empty|integer',
            'assigned_to' => 'permit_empty|integer',
        ];
        if (! $this->validate($rules, $data)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        if (isset($data['email'])) {
            $existing = $customerModel->where('email', $data['email'])
                                      ->where('id !=', $id)
                                      ->first();
            if ($existing) {
                return $this->failResourceExists('Customer email must be unique');
            }
        }
        $customerModel->update($id, $data);
        $updatedCustomer = $customerModel->find($id);
        return $this->respond($updatedCustomer);
    }

       public function delete($id = null)
    {
        if ($fail = $this->authorizeRequest()) {
            return $fail;
        }
        if ($id === null) {
            return $this->failValidationErrors('Customer ID is required');
        }
        $customerModel = new CustomerModel();
        $customer = $customerModel->find($id);
        if (! $customer) {
            return $this->failNotFound('Customer not found');
        }
        $customerModel->delete($id);
        return $this->respondDeleted(['id' => $id, 'message' => 'Customer deleted']);
    }


    
    
}
