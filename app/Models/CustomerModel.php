<?php

namespace App\Models;

use CodeIgniter\Model;

class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name', 'email', 'phone', 'lead_id', 'assigned_to', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;
    protected $validationRules = [
        'name'        => 'required',
        'email'       => 'required|valid_email',
        'assigned_to' => 'required|integer',
    ];
}
