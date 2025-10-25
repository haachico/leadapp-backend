<?php

namespace App\Controllers;

use App\Models\LeadModel;
use CodeIgniter\RESTful\ResourceController;

class Lead extends ResourceController
{
    protected $format = 'json';

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
