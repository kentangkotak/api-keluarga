<?php

namespace App\Models;

use App\Models\Pernikahan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnggotaKeluarga extends Model
{
    use HasFactory;
    protected $table = 'anggota_keluarga';
    protected $guarded = ['id'];

    //  protected $fillable = [
    //     'nama',
    //     'foto',
    //     'kelamin',

    //     'tanggal_lahir'
    // ];

    // Relasi sebagai suami
    public function pernikahanSebagaiSuami()
    {
        return $this->hasMany(Pernikahan::class, 'suami_id');
    }

    // Relasi sebagai istri
    public function pernikahanSebagaiIstri()
    {
        return $this->hasMany(Pernikahan::class, 'istri_id');
    }

    // Anak-anak
    public function anak()
    {
         return $this->belongsToMany(
            AnggotaKeluarga::class,
            'hubungan_orang_tua',
            'orang_tua_id',
            'anak_id'
        )->orderBy('anggota_keluarga.anakke', 'asc');
    }

    // Orang tua
    public function orangTua()
    {
        return $this->belongsToMany(
            AnggotaKeluarga::class,
            'hubungan_orang_tua',
            'anak_id',
            'orang_tua_id'
        )->withPivot('peran');
    }
}
