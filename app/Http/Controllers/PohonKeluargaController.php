<?php

namespace App\Http\Controllers;

use App\Models\AnggotaKeluarga;
use App\Models\Hubunganorangtua;
use App\Models\Pernikahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use phpseclib3\Net\SFTP;

class PohonKeluargaController extends Controller
{
    private function tampilx()
    {
        $id = '1';
        $root = AnggotaKeluarga::with([
            'pernikahanSebagaiSuami.istri',
            'pernikahanSebagaiIstri.suami',
            'anak',
            'orangTua'
        ])->findOrFail($id);

        return response()->json($this->formatTree($root));
    }

    public function tampil()
    {
        $id = request('id');
        $root = AnggotaKeluarga::with([
            'pernikahanSebagaiSuami.istri',
            'pernikahanSebagaiIstri.suami',
            'anak',
            'orangTua'
        ])->findOrFail($id);

        return response()->json($this->formatTree($root));
    }

    private function formatTree($orang)
    {
        $pasangan = $orang->pernikahanSebagaiSuami->first()?->istri
        ?? $orang->pernikahanSebagaiIstri->first()?->suami;

        $pernikahan =
        $orang->pernikahanSebagaiSuami->first()
        ?? $orang->pernikahanSebagaiIstri->first();

        $pernikahanId = is_object($pernikahan) ? $pernikahan->id : $pernikahan;

        return [
            'id' => $orang->id,
            'name' => $orang->nama,
            'kelamin' => $orang->kelamin,
            'anakke' => $orang->anakke,
            'tanggal_lahir' => $orang->tanggal_lahir,
            'pernikahan_id' => $pernikahanId,
            'alamat' => $orang->alamat,
            'kota' => $orang->kota,
            'nohp' => $orang->nohp,
            'parent_id' => $orang->orangTua->first()?->id,
            'photo' => $orang->foto,
            'spouse' => $pasangan ? [
                'id' => $pasangan->id,
                'name' => $pasangan->nama,
                'kelamin' => $pasangan->kelamin,
                'tanggal_lahir' => $pasangan->tanggal_lahir,
                'nohp' => $pasangan->nohp,
                'photo' => $pasangan->foto,
            ] : null,
            'children' => $orang->anak->map(function ($anak) {
                return $this->formatTree($anak);
            })
        ];
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
                $data = $request->validate(
                    [
                        'id' => 'nullable|integer',

                        'nama' => 'required|string|max:255',
                        'kelamin' => 'required|in:Laki-laki,Perempuan',
                        'tanggal_lahir' => 'required|date',
                        'alamat' => 'required|string|max:255',
                        'kota' => 'required|string|max:255',
                        'nohp' => 'required|string|max:20',
                        'anakke' => 'required|integer',
                        'parent_id' => 'required|integer',
                        'pernikahan_id' => 'nullable|integer',
                        'showSpouse' => 'nullable|boolean',
                        'spouse' => 'nullable|array',
                        // spouse wajib kalau showSpouse true
                        'spouse.id' => 'nullable|integer',
                        'spouse.nama' => 'required_if:showSpouse,true|nullable|string|max:255',
                        'spouse.kelamin' => 'required_if:showSpouse,true|nullable|in:Laki-laki,Perempuan',
                        'spouse.tanggal_lahir' => 'required_if:showSpouse,true|nullable|date',
                        'spouse.nohp' => 'required_if:showSpouse,true|nullable|string|max:20',
                    ],
                    [
                        // custom message
                        'nama.required' => 'Nama tidak boleh kosong',
                        'kelamin.required' => 'Jenis kelamin tidak boleh kosong',
                        'tanggal_lahir.required' => 'Tanggal lahir tidak boleh kosong',
                        'alamat.required' => 'Alamat tidak boleh kosong',
                        'kota.required' => 'Kota tidak boleh kosong',
                        'nohp.required' => 'No HP tidak boleh kosong',
                        'anakke.required' => 'Anak ke berapa wajib diisi',
                        'parent_id.required' => 'Parent ID wajib diisi',

                        'spouse.nama.required_if' => 'Nama pasangan wajib diisi',
                        'spouse.kelamin.required_if' => 'Jenis kelamin pasangan wajib diisi',
                        'spouse.tanggal_lahir.required_if' => 'Tanggal lahir pasangan wajib diisi',
                        'spouse.nohp.required_if' => 'No HP pasangan wajib diisi',
                    ]
                );


                // =========================
                // SIMPAN / UPDATE ANGGOTA
                // =========================
                $anggota = AnggotaKeluarga::updateOrCreate(
                    ['id' => $data['id'] ?? null],
                    [
                        'nama' => $data['nama'],
                        'kelamin' => $data['kelamin'],
                        'tanggal_lahir' => $data['tanggal_lahir'],
                        'alamat' => $data['alamat'] ?? null,
                        'kota' => $data['kota'] ?? null,
                        'nohp' => $data['nohp'] ?? null,
                        'anakke' => $data['anakke'] ?? null,
                        'parent_id' => $data['parent_id'] ?? null,
                        'foto' => $data['kelamin'] == 'Laki-laki' ? 'https://i.pravatar.cc/100?img=3' : 'https://i.pravatar.cc/100?img=5',
                    ]
                );
                $anggotaId = $anggota->id;
                $cariortu = Pernikahan::where('suami_id', $data['parent_id'])->first();


                // Simpan AYAH
                if ($cariortu->suami_id) {
                    Hubunganorangtua::updateOrCreate(
                        [
                            'anak_id' => $anggotaId,
                            'orang_tua_id' => $cariortu->suami_id,
                        ],
                        [
                            'peran' => 'ayah',
                        ]
                    );
                }

                // Simpan IBU
                if ($cariortu->istri_id) {
                    Hubunganorangtua::updateOrCreate(
                        [
                            'anak_id' => $anggotaId,
                            'orang_tua_id' => $cariortu->istri_id,
                        ],
                        [
                            'peran' => 'ibu',
                        ]
                    );
                }

                // =========================
                // HANDLE SPOUSE
                // =========================
                if (!empty($data['showSpouse']) && !empty($data['spouse']['nama'])) {

                    $spouse = AnggotaKeluarga::updateOrCreate(
                            ['id' => $data['spouse']['id'] ?? null],
                            [
                                'nama' => $data['spouse']['nama'],
                                'kelamin' => $data['spouse']['kelamin'],
                                'tanggal_lahir' => $data['spouse']['tanggal_lahir'],
                                'nohp' => $data['spouse']['nohp'] ?? null,
                                'alamat' => $data['alamat'] ?? null,
                                'kota' => $data['kota'] ?? null,
                                'parent_id' => $anggota->id,
                                'foto' => $data['spouse']['kelamin'] == 'Laki-laki' ? 'https://i.pravatar.cc/100?img=3' : 'https://i.pravatar.cc/100?img=5',
                            ]
                    );
                    if($anggota->kelamin == 'Laki-laki'){
                        $simpanpernikahan = Pernikahan::updateOrCreate(
                            [
                                'id' => $data['pernikahan_id'] ?? null,
                            ],
                            [
                                'suami_id' => $anggota->id,
                                'istri_id' => $spouse->id,
                            ]
                        );
                    }else{
                        $simpanpernikahan = Pernikahan::updateOrCreate(
                            [
                                'id' => $data['pernikahan_id'] ?? null,
                            ],
                            [
                                'suami_id' => $spouse->id,
                                'istri_id' => $anggota->id,
                            ]
                        );
                    }
                }


             DB::commit(); // ✅ semua berhasil
             $tampil = $this->tampilx();
             $cariortu = self::cariortu();
                return response()->json([
                    'success' => true,
                    'message' => 'Data anggota keluarga berhasil disimpan / diperbarui',
                    'data' => $tampil,
                    'ortu' => $cariortu
                ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server',
                'error' => $e->getMessage() // bisa dihapus kalau production
            ], 500);
        }
    }

    public static function cariortu()
    {
        $ortu = Pernikahan::leftJoin('anggota_keluarga as suami', 'suami.id', '=', 'pernikahan.suami_id')
        ->leftJoin('anggota_keluarga as istri', 'istri.id', '=', 'pernikahan.istri_id')
        ->select(
            'pernikahan.*',
            'suami.nama as nama_suami',
            'suami.id as suami_id',
            'istri.nama as nama_istri',
            'istri.id as istri_id'
        )
        ->get();
        return response()->json($ortu);
    }

    public function uploadfoto(Request $request)
    {
        $data = $request->validate([
            'id_anggota' => 'required|integer',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'spouse_id' => 'required|integer',
            'photospouse' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        $id_anggota = $data['id_anggota'];
        $spouse_id = $data['spouse_id'];
        $image = $request->file('photo');
        $imagespouse = $request->file('photospouse');
        $sftp = new SFTP('192.168.33.105', 22);
        if($image){
            $ext = $image->getClientOriginalExtension();
            $name = time().'.'.$image->getClientOriginalExtension();
            $folder = 'nasab/' . $id_anggota;

            if (!$sftp->login('root', 'sasa0102')) {
                throw new \Exception('Login failed');
            }

            $folder = '/www/wwwroot/storage/nasab/' . $id_anggota;
            if (!$sftp->is_dir($folder)) {
                $sftp->mkdir($folder, 0755, true);
            }

            $sftp->put("$folder/$name", file_get_contents($image));

            $data['foto'] = 'https://nasab.udumbara.my.id/nasab/'.$id_anggota.'/'.$name;
            $data['path'] = 'nasab/'.$id_anggota.'/'.$name;
            AnggotaKeluarga::where('id', $id_anggota)->update(
                [
                    'foto' => $data['foto'],
                    'path' => $data['path']
                ]
            );

        }

        if($imagespouse){
            $ext = $imagespouse->getClientOriginalExtension();
            $name = time().'.'.$imagespouse->getClientOriginalExtension();
            $folder = 'nasab/' . $spouse_id;

            if (!$sftp->login('root', 'sasa0102')) {
                throw new \Exception('Login failed');
            }

            $folder = '/www/wwwroot/storage/nasab/' . $spouse_id;
            if (!$sftp->is_dir($folder)) {
                $sftp->mkdir($folder, 0755, true);
            }

            $sftp->put("$folder/$name", file_get_contents($imagespouse));

            $data['foto'] = 'https://nasab.udumbara.my.id/nasab/'.$spouse_id.'/'.$name;
            $data['path'] = 'nasab/'.$spouse_id.'/'.$name;
            AnggotaKeluarga::where('id', $spouse_id)->update(
                [
                    'foto' =>$data['foto'],
                    'path' => $data['path']
                ]
            );
        }
        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil diunggah',
            'data' => $name
        ], 200);
    }
}
