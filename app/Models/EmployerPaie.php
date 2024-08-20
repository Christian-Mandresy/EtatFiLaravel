<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployerPaie extends Model
{
    use HasFactory;

    protected $table = 'employerpaie';

    public $timestamps = false;

    // Define the fillable fields
    protected $fillable = [
        'idemployer',
        'idtransaction',
        'mois_cumul',
    ];

    // Define the relationship with Employer
    public function employer()
    {
        return $this->belongsTo(Employer::class, 'idemployer');
    }

    // Define the relationship with Transaction
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'idtransaction');
    }
}

