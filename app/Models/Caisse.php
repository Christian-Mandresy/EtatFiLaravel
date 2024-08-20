<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Caisse extends Model
{
    use HasFactory;

    protected $table = 'caisse';

    public $timestamps = false; // Désactiver les colonnes created_at et updated_at


    protected $fillable = [
        'nom', 'solde',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'idcaisse');
    }
}
