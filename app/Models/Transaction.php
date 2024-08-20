<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;
    protected $table = 'transaction';

    public $timestamps = false; // Désactiver les colonnes created_at et updated_at

    protected $fillable = [
        'idcaisse', 'montant', 'datetransaction', 'typetransaction', 'description',
    ];

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'idcaisse');
    }
}
