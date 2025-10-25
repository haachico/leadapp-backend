<?php

namespace App\Controllers;

use App\Models\LeadModel;
use CodeIgniter\RESTful\ResourceController;

class Lead extends ResourceController
{
    protected $format = 'json';

    public function index()
    {
        $leadModel = new LeadModel();

        $authHeader = $this->request->getHeaderLine('Authorization');
        $userId = null;
        $role = null;
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $jwt = $matches[1];
            try {
                $key = env('JWT_SECRET') ?: 'your_secret_key';
                $decoded = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key($key, 'HS256'));
                $userId = $decoded->uid ?? null;
                $role = $decoded->role ?? null;
            } catch (\Exception $e) {
                return $this->failUnauthorized('Invalid or expired token');
            }
        }

        // Debug: Show actual role and userId being checked
        // return $this->respond(['role' => $role, 'userId' => $userId]);

        if ($role === 'admin') {
            $leads = $leadModel->findAll();
        } elseif ($role === 'user') {
            $leads = $leadModel->where('assigned_to', $userId)->findAll();
        } else {
            return $this->failUnauthorized('Role not recognized');
        }

        return $this->respond($leads);
    }

    public function create()
    {
        $rules = [
            'name'        => 'required',
            'email'       => 'required|valid_email',
            'assigned_to' => 'required|integer',
        ];
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        if (! $this->validate($rules, $data)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $leadModel = new LeadModel();
        $existing = $leadModel->where('email', $data['email'])
                              ->where('project_id', $data['project_id'] ?? 1)
                              ->first();
        if ($existing) {
            return $this->failResourceExists('Lead email must be unique within the same project');
        }

        $data['status'] = $data['status'] ?? 'New';
        $data['project_id'] = $data['project_id'] ?? 1;
        $leadModel->insert($data);
        $lead = $leadModel->find($leadModel->getInsertID());
        return $this->respondCreated($lead);
    }

    public function update($id = null)
    {
        if ($id === null) {
            return $this->failValidationErrors('Lead ID is required');
        }
        $leadModel = new LeadModel();
        $lead = $leadModel->find($id);
        if (! $lead) {
            return $this->failNotFound('Lead not found');
        }

        $data = $this->request->getJSON(true) ?? $this->request->getRawInput();
        $rules = [
            'name'        => 'permit_empty',
            'email'       => 'permit_empty|valid_email',
            'phone'       => 'permit_empty',
            'status'      => 'permit_empty',
            'assigned_to' => 'permit_empty|integer',
            'project_id'  => 'permit_empty|integer',
        ];
        if (! $this->validate($rules, $data)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        // Check for unique email within project if email is being updated
        if (isset($data['email'])) {
            $existing = $leadModel->where('email', $data['email'])
                                  ->where('project_id', $data['project_id'] ?? $lead['project_id'])
                                  ->where('id !=', $id)
                                  ->first();
            if ($existing) {
                return $this->failResourceExists('Lead email must be unique within the same project');
            }
        }

        $leadModel->update($id, $data);
        $updatedLead = $leadModel->find($id);
        return $this->respond($updatedLead);
    }
}
