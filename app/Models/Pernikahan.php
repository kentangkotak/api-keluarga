<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pernikahan extends Model
{
    use HasFactory;
    protected $table = 'pernikahan';

    protected $fillable = [
        'suami_id',
        'istri_id',
        'tanggal_nikah'
    ];

    public function suami()
    {
        return $this->belongsTo(AnggotaKeluarga::class, 'suami_id');
    }

    public function istri()
    {
        return $this->belongsTo(AnggotaKeluarga::class, 'istri_id');
    }
}
