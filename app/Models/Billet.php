<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Billet extends Model
{
    use HasFactory;
    protected $table = 'billet';
    protected $fillable = ['idrakitra', 'billet', 'nombre'];
    public $timestamps = false; // Désactiver les colonnes created_at et updated_at

    public function rakitra()
    {
        return $this->belongsTo(Transaction::class, 'idrakitra', 'id');
    }
}
