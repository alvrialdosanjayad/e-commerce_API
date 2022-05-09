<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use App\Models\Produk;
use App\Models\Lapak;
use App\Models\Paroki;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
//use MongoDB\BSON\Regex;

class ProdukAPIController extends Controller
{
    protected $lapak, $produk, $paroki, $kategori;

    /**
     * Create a new ProdukAPIController instance.
     *
     * @return void
     */
    public function __construct(Lapak $lapak, Produk $produk, Paroki $paroki, Kategori $kategori)
    {
        $this->lapak = $lapak;
        $this->produk = $produk;
        $this->paroki = $paroki;
        $this->kategori = $kategori;
    }

    public function showAllProduk()
    {
        #GET PRODUK BERDASARKAN LAPAK YANG AKTIF
        $lapak_all = $this->lapak->where('status_lapak', 'ACTIVE')->where('status_lapak', 'ACTIVE')->get(['produk_lapak', 'user_id']);

        if (count($lapak_all) == 0) {
            return response()->json(["success" => true, "message" => "Data Tidak Ditemukan", 'data' => []], 200);
        } else {
            # VARIABEL UNTUK MENAMPUNG PRODUK YANG AKAN MENJADI RESPONSE
            $semuaProduk = collect();
            foreach ($lapak_all as $lap) {
                foreach ($lap->produk_lapak as $lp) {
                    $lp['user_id'] = $lap['user_id'];
                    $semuaProduk->push($lp);
                }
            }
            return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $semuaProduk], 200);
        }
    }

    public function showDetailProduk($id_produk)
    {
        #GET DETAIL PRODUK
        $detail_produk = $this->produk->find($id_produk);

        if (empty($detail_produk)) {
            return response()->json(["success" => false, "message" => "Data tidak Ditemukan"], 404);
        } else {
            return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Detail Produk", "data" => $detail_produk], 200);
        }
    }

    public function createProduk(Request $request)
    {
        # VALIDATE PRODUK REQUEST
        $produk = Validator::make($request->all(), [
            'lapak_id' => 'required',
            'kategori_id' => 'required',
            'nama_produk' => 'required|unique:App\Models\Produk,nama_produk',
            'gambar_produk' => 'required|array',
            'gambar_produk.*' => 'image|mimes:jpeg,png',
            'deskripsi_produk' => 'required',
            'berat_produk' => 'required|numeric|min:1',
            'harga_produk' => 'required|numeric|min:1',
            'stok_produk' => 'required|numeric|min:1',
            'kondisi_produk' => 'required',
            'keamanan_produk' => 'required'
        ]);

        if ($produk->fails()) {
            return response()->json(["success" => false, "message" => $produk->errors()], 422);
        } else {
            $validateDataRequest = $this->cekDataRequest($request, null);
            if (count($validateDataRequest) == 0) {
                $dataLapak = $this->lapak->where('_id', $request->lapak_id)->first(['_id', 'nama_lapak', 'no_telepon_lapak']);
                $dataKategori = $this->kategori->where('_id', $request->kategori_id)->first();

                # CREATE PRODUK
                $produk_create = $this->produk->create([
                    'lapak_produk' => array(
                        "lapak_id" => $dataLapak['_id'],
                        "nama_lapak" => $dataLapak['nama_lapak'],
                        "no_telepon_lapak" => $dataLapak['no_telepon_lapak']
                    ),
                    'kategori_produk' => array(
                        "kategori_id" => $dataKategori['_id'],
                        "iconText" => $dataKategori['iconText'],
                        "iconName" => $dataKategori['iconName']
                    ),
                    'nama_produk' => $request->input('nama_produk'),
                    'gambar_produk' => $this->addGambarProduk($request),
                    'deskripsi_produk' => $request->input('deskripsi_produk'),
                    'berat_produk' => $request->input('berat_produk'),
                    'harga_produk' => $request->input('harga_produk'),
                    'stok_produk' => $request->input('stok_produk'),
                    'kondisi_produk' => $request->input('kondisi_produk'),
                    'keamanan_produk' => $request->input('keamanan_produk'),
                    'merek_produk' => $request->input('merek_produk'),
                    'variasi_produk' => json_decode($request->input('variasi_produk')[0], true),
                    'penjualan_produk' => 0,
                    'rating_produk' => array(),
                    'deleted_date' => null,
                    'created_date' => date('d-m-Y'),
                    'updated_date' => date('d-m-Y')
                ]);

                # TAMBAH PRODUK DILAPAK
                $lapak_produk = $this->lapak->find($produk_create->lapak_produk['lapak_id']);
                $lapak_produk->push('produk_lapak', [
                    'produk_id' => $produk_create->_id,
                    'kategori_produk' => $produk_create->kategori_produk,
                    'nama_produk' => $produk_create->nama_produk,
                    'gambar_produk' => $produk_create->gambar_produk,
                    'harga_produk' => $produk_create->harga_produk,
                    'stok_produk' => $produk_create->stok_produk,
                    'penjualan_produk' => $produk_create->penjualan_produk,
                ]);

                return response()->json(['success' => true, 'message' => "Produk Berhasil Ditambahkan", 'data' => $produk_create]);
            } else {
                return response()->json(["success" => false, "message" => $validateDataRequest], 400);
            }
        }
    }

    public function updateProduk(Request $request, $produk_id)
    {
        # CEK PRODUK
        $cekProduk = $this->produk->where('_id', $produk_id)->first(['_id', 'lapak_produk.lapak_id']);
        if (empty($cekProduk)) {
            return response()->json(["success" => false, "message" => "Produk Tidak Ditemukan"], 404);
        }

        #VALIDATE PRODUK REQUEST
        $produk = Validator::make($request->all(), [
            'kategori_id' => 'required',
            'nama_produk' => 'required|unique:App\Models\Produk,nama_produk,' . $produk_id,
            'hapus_gambar' => 'array|max:3',
            'gambar_produk' => 'array|max:3',
            'gambar_produk.*' => 'image|mimes:jpg,png',
            'deskripsi_produk' => 'required',
            'berat_produk' => 'required|numeric|min:1',
            'harga_produk' => 'required|numeric|min:1',
            'stok_produk' => 'required|numeric|min:1',
            'kondisi_produk' => 'required',
            'keamanan_produk' => 'required'
        ]);

        if ($produk->fails()) {
            return response()->json(["success" => false, "message" => $produk->errors()], 422);
        } else {
            $validateDataRequest = $this->cekDataRequest($request, $produk_id);
            if (count($validateDataRequest) == 0) {
                $dataKategori = $this->kategori->where('_id', $request->kategori_id)->first();

                # UPDATE PRODUK
                $this->produk->where('_id', $produk_id)->update([
                    'kategori_produk' => array(
                        "kategori_id" => $dataKategori['_id'],
                        "iconText" => $dataKategori['iconText'],
                        "iconName" => $dataKategori['iconName']
                    ),
                    'nama_produk' => $request->input('nama_produk'),
                    'gambar_produk' => $this->updateGambarProduk($request, $produk_id),
                    'deskripsi_produk' => $request->input('deskripsi_produk'),
                    'berat_produk' => $request->input('berat_produk'),
                    'harga_produk' => $request->input('harga_produk'),
                    'stok_produk' => (int) $request->input('stok_produk'),
                    'kondisi_produk' => $request->input('kondisi_produk'),
                    'keamanan_produk' => $request->input('keamanan_produk'),
                    'merek_produk' => $request->input('merek_produk'),
                    'variasi_produk' => json_decode($request->input('variasi_produk')[0]),
                    'updated_date' => date('d-m-Y')
                ]);

                # UPDATE PRODUK DILAPAK
                $produk_detail = $this->produk->where('_id', $produk_id)->first(['kategori_produk', 'nama_produk', 'harga_produk', 'stok_produk', 'gambar_produk']);
                $lapak_detail = $this->lapak->find($cekProduk['lapak_produk.lapak_id']);
                $produkLapak = array();
                foreach ($lapak_detail['produk_lapak'] as $lpk_produk) {
                    if ($lpk_produk['produk_id'] == $produk_id) {
                        $lpk_produk['kategori_produk'] = $produk_detail['kategori_produk'];
                        $lpk_produk['nama_produk'] = $produk_detail['nama_produk'];
                        $lpk_produk['harga_produk'] = $produk_detail['harga_produk'];
                        $lpk_produk['stok_produk'] = (int) $produk_detail['stok_produk'];
                        $lpk_produk['gambar_produk'] = $produk_detail['gambar_produk'];
                    }
                    array_push($produkLapak, $lpk_produk);
                }
                $lapak_detail->produk_lapak = $produkLapak;
                $lapak_detail->save();

                return response()->json(['success' => true, 'message' => "Berhasil Melakukan Update Produk"], 200);
            } else {
                return response()->json(["success" => false, "message" => $validateDataRequest], 400);
            }
        }
    }

    public function filterAndSeacrhProduk(Request $request)
    {
        # VALIDATE FILTER SEARCH REQUEST
        // $searchFilterValidate = Validator::make($request->all(), [
        //     'min_harga' => 'numeric',
        //     'max_harga' => 'numeric'
        // ]);

        // if ($searchFilterValidate->fails()) {
        //     return response()->json(["success" => false, "message" => $searchFilterValidate->errors()], 422);
        // }

        $lapak_active = $this->lapak->where('status_lapak', 'ACTIVE')->get(['produk_lapak', 'paroki_lapak.paroki_id', 'user_id']);

        $data_search_filter = array(
            "produk" => collect(),
            "lapak" => array()
        );
        foreach ($lapak_active as $lap) {
            foreach ($lap->produk_lapak as $lp) {
                $lp['paroki'] = $lap["paroki_lapak"]["paroki_id"];
                $lp['user_id'] = $lap["user_id"];
                $data_search_filter["produk"]->push($lp);
            }
        }

        if ($request->has('search')) {
            $data = $this->searchProdukMobile($request->search, $data_search_filter["produk"]);
            $data_search_filter["produk"] =  $data['produk'];
            $data_search_filter["lapak"] = $data['lapak'];
        }

        if ($request->has('id_paroki')) {
            if (isset($request->id_paroki)) {
                $cekParoki = $this->paroki->where('_id', $request->id_paroki)->first(['_id']);
                if (empty($cekParoki)) {
                    return response()->json(["success" => false, "message" => "Paroki Tidak Ditemukan"], 404);
                }
                $produk_temp = collect();
                foreach ($data_search_filter["produk"] as $prdk) {
                    if ($prdk['paroki'] == $request->id_paroki) {
                        $produk_temp->push($prdk);
                    }
                }
                $data_search_filter["produk"] = $produk_temp;
            }
        }

        if ($request->has('id_kategori')) {
            if (isset($request->id_kategori)) {
                $cekKategori = $this->kategori->where('_id', $request->id_kategori)->first(['_id']);
                if (empty($cekKategori)) {
                    return response()->json(["success" => false, "message" => "Kategori Tidak Ditemukan"], 404);
                }
                $produk_temp = collect();
                foreach ($data_search_filter["produk"] as $prdk) {
                    if ($prdk['kategori_produk']['kategori_id'] == $request->id_kategori) {
                        $produk_temp->push($prdk);
                    }
                }
                $data_search_filter["produk"] = $produk_temp;
            }
        }


        if ($request->min_harga != "MIN" && $request->max_harga != "MAX" && isset($request->min_harga) && isset($request->max_harga)) {
            $produk_temp = collect();
            $harga_min = (int) $request->min_harga;
            $harga_max = (int) $request->max_harga;
            foreach ($data_search_filter["produk"] as $prdk) {
                if ($prdk["harga_produk"] >= $harga_min && $prdk["harga_produk"] <= $harga_max) {
                    $produk_temp->push($prdk);
                }
            }
            $data_search_filter["produk"] = $produk_temp;
        }

        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $data_search_filter], 200);
    }

    protected function addGambarProduk($request)
    {
        $gambar = array();
        if ($request->hasfile('gambar_produk')) {
            foreach ($request->file('gambar_produk') as $file) {
                $name = Str::random(15) . time() . '.' . $file->extension();
                $file->move(public_path('/gambar-produk'),  $name);
                array_push($gambar, $name);
            }
        }
        return $gambar;
    }

    protected function updateGambarProduk($request, $produk_id)
    {
        $response_gambar_produk = $this->produk->where('_id', $produk_id)->first(['gambar_produk']);
        $gambarDB = $response_gambar_produk['gambar_produk'];
        $newGambar = array();

        if ($request->has('hapus_gambar')) {
            foreach ($request->hapus_gambar as $hapus_gambar) {
                if (!empty($hapus_gambar)) {
                    $indexGambar = array_search($hapus_gambar, $gambarDB);
                    array_splice($gambarDB, $indexGambar, 1);
                    Storage::disk('hosting')->delete("gambar-produk/" . $hapus_gambar);
                }
            }
        }

        foreach ($gambarDB as $gmbrDB) {
            // $newNameGambar = $request->nama_produk . ' - ' . rand(1, 100) . time() . ".jpg";
            // rename(public_path('/gambar-produk') . "/" . $gmbrDB, public_path('/gambar-produk') . "/" . $newNameGambar);
            array_push($newGambar, $gmbrDB);
        }

        if ($request->hasfile('gambar_produk')) {
            foreach ($request->file('gambar_produk') as $file) {
                $name = Str::random(15) . time() . '.' . $file->extension();
                $file->move(public_path('/gambar-produk'),  $name);
                array_push($newGambar, $name);
            }
        }

        return $newGambar;
    }

    protected function cekDataRequest($request, $produk_id)
    {
        $error = [];
        $cekKategoriProduk = $this->kategori->where('_id', $request->kategori_id)->first();

        if ($produk_id != null) {
            $cekGambarProduk = $this->produk->where("_id", $produk_id)->first(['gambar_produk']);
            $totalGambar = count($cekGambarProduk['gambar_produk']);
            if ($request->has('hapus_gambar')) {
                foreach ($request->hapus_gambar as $hapus_gambar) {
                    if (!empty($hapus_gambar)) {
                        if (in_array($hapus_gambar, $cekGambarProduk['gambar_produk'])) {
                            $totalGambar--;
                        } else {
                            $error['hapus_gambar'] = ["The hapus gambar Not Found."];
                            break;
                        }
                    }
                }
            }

            if ($totalGambar <= 0 && !$request->hasFile('gambar_produk')) {
                $error['gambar_produk'] = ["The gambar produk field is required."];
            }
        } else {
            $cekLapakProduk = $this->lapak->where('_id', $request->lapak_id)->first(['_id']);
            if (empty($cekLapakProduk)) {
                $error['lapak_id'] = ["The lapak id Not Found."];
            }
        }

        if (empty($cekKategoriProduk)) {
            $error['kategori_id'] = ["The kategori id Not Found."];
        }

        return $error;
    }

    protected function searchProdukMobile($dataSearch, $produk)
    {
        $data_search = array();

        $result_produk = collect();
        foreach ($produk as $prdk) {
            if (preg_match("/" . $dataSearch . "/i", $prdk['nama_produk']) == 1) {
                $result_produk->push($prdk);
            }
        }
        // Cara lain melakukan regex: new Regex('.*' . $dataSearch, 'i')
        $result_lapak = $this->lapak->where('nama_lapak', 'regex', '/.*' . $dataSearch . '/i')->where('status_lapak', '=', 'ACTIVE')->get(['_id', 'nama_lapak', 'paroki_lapak', 'status_lapak']);

        $data_search['produk'] = $result_produk;
        $data_search['lapak'] = $result_lapak;

        return $data_search;
    }
}
