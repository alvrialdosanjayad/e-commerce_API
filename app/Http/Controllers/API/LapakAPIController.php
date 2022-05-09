<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lapak;
use App\Models\Paroki;
use Illuminate\Http\Request;
use App\Models\Produk;
use App\Models\UserMongoDB;
use Illuminate\Support\Facades\Validator;

class LapakAPIController extends Controller
{
    protected $lapak;
    protected $produk;
    protected $paroki;

    /**
     * Create a new LapakAPIController instance.
     *
     * @return void
     */
    public function __construct(Lapak $lapak, Produk $produk, Paroki $paroki)
    {
        $this->lapak = $lapak;
        $this->produk = $produk;
        $this->paroki = $paroki;
    }

    //
    public function showAllLapak()
    {
        # GET SEMUA DATA LAPAK DENGAN SORTING ASCENDING
        $lapak_all = $this->lapak->orderBy('nama_lapak', 'ASC')->get(['_id', 'nama_lapak', 'paroki_lapak', 'status_lapak']);

        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $lapak_all], 200);
    }

    public function showDetailLapak($id_lapak)
    {
        # GET DETAIL LAPAK
        $detail_lapak = $this->lapak->find($id_lapak);
        if (empty($detail_lapak)) {
            return response()->json(["success" => false, "message" => "Data Tidak Ditemukan"], 404);
        } else {
            $data_user = UserMongoDB::where('_id', $detail_lapak['user_id'])->first(['nama']);
            $detail_lapak->rating_lapak = 0;
            $detail_lapak->penjualan_lapak = 0;
            $detail_lapak->nama_user = $data_user['nama'];
            if (count($detail_lapak->produk_lapak) != 0) {
                foreach ($detail_lapak->produk_lapak as $produk) {
                    $detail_lapak->penjualan_lapak += $produk['penjualan_produk'];
                }

                $average = collect();
                $data = $this->produk->where('lapak_produk.lapak_id', '=', $id_lapak)->get(['rating_produk']);
                foreach ($data as $ratings) {
                    foreach ($ratings->rating_produk as $rating) {
                        if (isset($rating)) {
                            $average->push($rating['value']);
                        }
                    }
                }

                $average->avg() == null ? $detail_lapak->rating_lapak = 0 : $detail_lapak->rating_lapak = $average->avg();
            }
            return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Detail Lapak", "data" => $detail_lapak], 200);
        }
    }

    public function showLapakByParoki($id_paroki)
    {
        # GET DATA LAPAK BERDASARKAN PAROKI
        $lapak_paroki = $this->lapak->where('paroki_lapak.paroki_id', '=', $id_paroki)->get(['_id', 'nama_lapak', 'paroki_lapak', 'status_lapak']);

        if (count($lapak_paroki) == 0) {
            return response()->json(["success" => false, "message" => "Data Tidak Ditemukan"], 404);
        } else {
            return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Lapak Berdasarkan Paroki", "data" => $lapak_paroki], 200);
        }
    }

    public function createLapak(Request $request)
    {
        # VALIDATE LAPAK REQUEST
        $lapakValidate = Validator::make($request->all(), [
            'user_id' => 'required',
            'nama_lapak' => 'required|unique:App\Models\Lapak,nama_lapak',
            'paroki_id' => 'required',
            'deskripsi_lapak' => 'required',
            'alamat_lapak.kecamatan' => 'required',
            'alamat_lapak.kelurahan' => 'required',
            'alamat_lapak.detail_alamat' => 'required',
            'alamat_lapak.longitude' => 'nullable',
            'alamat_lapak.latitude' => 'nullable',
            'no_telepon_lapak' => 'required|digits_between:9,13'
        ]);

        if ($lapakValidate->fails()) {
            return response()->json(["success" => false, "message" => $lapakValidate->errors()], 400);
        } else {
            $dataValidate = $this->cekDataValidate($request, "create");

            if (count($dataValidate) == 0) {
                $cekParoki = $this->paroki->where('_id', '=', $request->input('paroki_id'))->first(['_id', 'nama_paroki']);

                # CREATE LAPAK
                $lapak_create = $this->lapak->create([
                    'user_id' => $request->input('user_id'),
                    'nama_lapak' => $request->input('nama_lapak'),
                    'paroki_lapak' => array(
                        'paroki_id' => $cekParoki['_id'],
                        'nama_paroki' => $cekParoki['nama_paroki']
                    ),
                    'deskripsi_lapak' => $request->input('deskripsi_lapak'),
                    'alamat_lapak' => $request->input('alamat_lapak'),
                    'no_telepon_lapak' => $request->input('no_telepon_lapak'),
                    'produk_lapak' => array(),
                    'status_lapak' => "UNVERIFIED",
                    'catatan_lapak' => "",
                    'created_date' => date('d-m-Y'),
                    'updated_date' => date('d-m-Y')
                ]);

                return response()->json(['success' => true, 'message' => 'Berhasil Menambahkan Lapak', 'data' => $lapak_create], 200);
            } else {
                return response()->json(["success" => false, "message" => $dataValidate], 400);
            }
        }
    }

    public function updateLapak(Request $request, $lapak_id)
    {
        # CEK LAPAK
        $cekLapak = $this->lapak->where('_id', '=', $lapak_id)->first(['_id']);
        if (empty($cekLapak)) {
            return response()->json(["success" => false, "message" => "Lapak Tidak Ditemukan"], 400);
        }

        # VALIDATE LAPAK REQUEST
        $lapakValidate = Validator::make($request->all(), [
            'nama_lapak' => 'required|unique:App\Models\Lapak,nama_lapak,' . $lapak_id,
            'paroki_id' => 'required',
            'deskripsi_lapak' => 'required',
            'alamat_lapak.kecamatan' => 'required',
            'alamat_lapak.kelurahan' => 'required',
            'alamat_lapak.detail_alamat' => 'required',
            'alamat_lapak.longitude' => 'nullable',
            'alamat_lapak.latitude' => 'nullable',
            'no_telepon_lapak' => 'required|digits_between:9,13',
            'status_lapak' => 'required|in:UNVERIFIED,ACTIVE,INACTIVE'
        ]);

        if ($lapakValidate->fails()) {
            return response()->json(["success" => false, "message" => $lapakValidate->errors()], 400);
        } else {
            $dataValidate = $this->cekDataValidate($request, "update");

            if (count($dataValidate) == 0) {
                $cekParoki = $this->paroki->where('_id', '=', $request->input('paroki_id'))->first(['_id', 'nama_paroki']);

                # UPDATE LAPAK
                $this->lapak->where('_id', $lapak_id)->update([
                    'nama_lapak' => $request->input('nama_lapak'),
                    'paroki_lapak' => array(
                        'paroki_id' => $cekParoki['_id'],
                        'nama_paroki' => $cekParoki['nama_paroki']
                    ),
                    'deskripsi_lapak' => $request->input('deskripsi_lapak'),
                    'alamat_lapak' => $request->input('alamat_lapak'),
                    'no_telepon_lapak' => $request->input('no_telepon_lapak'),
                    'status_lapak' => $request->input('status_lapak'),
                    'catatan_lapak' => $request->input('catatan_lapak'),
                    'updated_date' => date('d-m-Y')
                ]);

                # UPDATE PRODUK
                $data_lapak = $this->lapak->where('_id', $lapak_id)->first(["nama_lapak", "no_telepon_lapak", "produk_lapak"]);
                $update_lapak_produk = array(
                    "lapak_id" => $lapak_id,
                    "nama_lapak" => $data_lapak['nama_lapak'],
                    "no_telepon_lapak" => $data_lapak['no_telepon_lapak']
                );

                foreach ($data_lapak['produk_lapak'] as $produk_lapak) {
                    $produk_update = $this->produk->find($produk_lapak['produk_id']);
                    $produk_update->lapak_produk = $update_lapak_produk;
                    $produk_update->save();
                }

                return response()->json(['success' => true, 'message' => 'Berhasil Melakukan Update Lapak'], 200);
            } else {
                return response()->json(["success" => false, "message" => $dataValidate], 400);
            }
        }
    }

    protected function cekDataValidate($request, $jns_fungsi)
    {
        $error = array();

        $cekParoki = $this->paroki->where('_id', '=', $request->input('paroki_id'))->first(['_id']);

        if ($jns_fungsi == "create") {
            $cekUser = UserMongoDB::where('_id', '=', $request->input('user_id'))->first(['_id']);
            if (empty($cekUser)) {
                $error['user_id'] = ["The user id Not Found."];
            }
        }

        if (empty($cekParoki)) {
            $error['paroki_id'] = ["The paroki id Not Found."];
        }

        return $error;
    }
}
