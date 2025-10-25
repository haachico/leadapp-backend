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
}
