<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKeluarga;
use Illuminate\Http\Request;

class PohonKeluargaController extends Controller
{
    public function tampil()
    {
        $id = request('id');
        $root = AnggotaKeluarga::with([
            'pernikahanSebagaiSuami.istri',
            'pernikahanSebagaiIstri.suami',
            'anak'
        ])->findOrFail($id);

        return response()->json($this->formatTree($root));
    }

    private function formatTree($orang)
    {
        $pasangan = $orang->pernikahanSebagaiSuami->first()?->istri
        ?? $orang->pernikahanSebagaiIstri->first()?->suami;

        return [
            'id' => $orang->id,
            'name' => $orang->nama,
             'kelamin' => $orang->kelamin,
            'photo' => $orang->foto,
            'spouse' => $pasangan ? [
                'id' => $pasangan->id,
                'name' => $pasangan->nama,
                'kelamin' => $pasangan->kelamin,
                'photo' => $pasangan->foto,
            ] : null,
            'children' => $orang->anak->map(function ($anak) {
                return $this->formatTree($anak);
            })
        ];
    }

   public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'kelamin' => 'required|in:Laki-laki,Perempuan',
            'tanggal_lahir' => 'required|date',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle upload foto
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('anggota', 'public');
            $data['foto'] = $path;
        }

        $anggota = AnggotaKeluarga::create($data);

        return response()->json([
            'message' => 'Data anggota keluarga berhasil disimpan',
            'data' => $anggota
        ], 201);
    }
}
