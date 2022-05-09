<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\KeranjangBelanja;
use App\Models\Lapak;
use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\UserMongoDB;

use Illuminate\Support\Facades\Validator;

class KeranjangAPIController extends Controller
{
    protected $lapak, $produk, $keranjangBelanja, $transaksi;

    /**
     * Create a new ProdukAPIController instance.
     *
     * @return void
     */
    public function __construct(Lapak $lapak, Produk $produk, KeranjangBelanja $keranjangBelanja, Transaksi $transaksi)
    {
        $this->lapak = $lapak;
        $this->produk = $produk;
        $this->keranjangBelanja = $keranjangBelanja;
        $this->transaksi = $transaksi;
    }


    //
    public function createKeranjangBelanja(Request $request)
    {
        # VALIDATE KERANJANG REQUEST
        $keranjangValidate = Validator::make($request->all(), [
            'user_id' => 'required'
        ]);

        if ($keranjangValidate->fails()) {
            return response()->json(["success" => false, "message" => $keranjangValidate->errors()], 422);
        } else {
            $dataValidate = $this->validateDataRequest($request->input('user_id'), $request);

            if (count($dataValidate) == 0) {
                $keranjang_belanja = $this->keranjangBelanja->where("user_id", "=", $request->user_id)->first();

                if (empty($keranjang_belanja)) {
                    $keranjangBelanja = $this->keranjangBelanja->create([
                        'user_id' => $request->input('user_id'),
                        'lapak' => array()
                    ]);

                    return response()->json(["success" => true, "message" => "Berhasil Menambahkan Keranjang Baru", "data" => $keranjangBelanja]);
                } else {
                    return response()->json(["success" => false, "message" => "Keranjang Sudah Ada"], 400);
                }
            } else {
                return response()->json(["success" => false, "message" => $dataValidate], 400);
            }
        }
    }

    public function addProduk(Request $request)
    {
        $keranjangValidate = Validator::make($request->all(), [
            'user_id' => 'required',
            'produk_id' => 'required',
            'jumlah_produk' => 'required|integer|min:1'
        ]);

        if ($keranjangValidate->fails()) {
            return response()->json(["success" => false, "message" => $keranjangValidate->errors()], 422);
        } else {
            $dataValidate = $this->validateDataRequest($request->input('user_id'), $request);

            if (count($dataValidate) == 0) {
                $produk = $this->produk->where('_id', $request->input('produk_id'))->first(['lapak_produk', 'nama_produk', 'gambar_produk', 'harga_produk']);
                $keranjang = $this->keranjangBelanja->where("user_id", $request->user_id)->first();
                if (isset($keranjang)) {
                    if ($this->cekLapak($keranjang['lapak'], $produk['lapak_produk']['lapak_id'])) {
                        if ($this->cekProduk($keranjang['lapak'], $produk['lapak_produk']['lapak_id'], $produk['_id'])) {
                            return $this->tambahJumlahProduk($keranjang, $keranjang['lapak'], $request, $produk);
                        } else {
                            return $this->tambahProduk($keranjang, $keranjang['lapak'], $request, $produk);
                        }
                    } else {
                        return $this->tambahLapak($keranjang, $keranjang['lapak'], $request, $produk);
                    }
                } else {
                    return response()->json(["success" => false, 'message' => "Keranjang tidak ditemukan"], 400);
                }
            } else {
                return response()->json(["success" => false, "message" => $dataValidate], 400);
            }
        }
    }

    public function deleteProduk(Request $request)
    {
        $keranjangValidate = Validator::make($request->all(), [
            'user_id' => 'required',
            'lapak_id' => 'required',
            'jenis_hapus' => 'required|in:lapak,produk,jumlah_produk',
            'produk_id' => 'required_if:jenis_hapus,produk,jumlah_produk',
        ]);

        if ($keranjangValidate->fails()) {
            return response()->json(["success" => false, "message" => $keranjangValidate->errors()], 422);
        } else {
            $dataValidate = $this->validateDataRequest($request->input('user_id'), $request);

            if (count($dataValidate) == 0) {
                $keranjang = $this->keranjangBelanja->where("user_id", "=", $request->user_id)->first();
                if (isset($keranjang)) {
                    if ($this->cekLapak($keranjang['lapak'], $request->lapak_id)) {
                        if ($request->jenis_hapus == 'lapak') {
                            return $this->deleteLapak($keranjang, $keranjang['lapak'], $request);
                        } else {
                            if ($this->cekProduk($keranjang['lapak'], $request->lapak_id, $request->produk_id)) {
                                if ($request->jenis_hapus == 'produk') {
                                    return $this->hapusProduk($keranjang, $keranjang['lapak'], $request);
                                } else {
                                    return $this->deleteJumlahProduk($keranjang, $keranjang['lapak'], $request);
                                }
                            }
                        }
                    }
                } else {
                    return response()->json(['success' => false, 'message' => "Keranjang tidak ditemukan"], 400);
                }
            } else {
                return response()->json(["success" => false, "message" => $dataValidate], 400);
            }
        }
    }

    public function showKeranjang($user_id)
    {
        $keranjang_belanja = $this->keranjangBelanja->where("user_id", "=", $user_id)->first();

        if (isset($keranjang_belanja)) {
            return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $this->tampilProduk($keranjang_belanja)], 200);
        } else {
            return response()->json(["success" => false, "message" => "Keranjang Tidak Ditemukan"], 404);
        }
    }

    public function checkoutKeranjangBelanja(Request $request)
    {
        #VALIDATE KERANJANG REQUEST
        $keranjangValidate = Validator::make($request->all(), [
            'user_id' => 'required',
            'lapak_id' => 'required',
            'produk' => 'required'
        ]);

        if ($keranjangValidate->fails()) {
            return response()->json(["success" => false, "message" => $keranjangValidate->errors()], 422);
        } else {
            $dataValidate = $this->validateDataRequest($request->input('user_id'), $request);

            if (count($dataValidate) == 0) {
                $keranjang = $this->keranjangBelanja->where("user_id", "=", $request->input('user_id'))->first();
                $arrayToko = $keranjang['lapak'];
                for ($i = 0; $i < count($arrayToko); $i++) {
                    if ($request->lapak_id == $arrayToko[$i]['lapak_id']) {
                        $produkDB = $arrayToko[$i]['produk'];
                        for ($j = 0; $j < count($produkDB); $j++) {
                            foreach ($request->produk as $produkRequest) {
                                if ($produkRequest['produk_id'] == $produkDB[$j]['produk_id']) {
                                    array_splice($produkDB, $j, 1);
                                }
                            }
                        }
                        $arrayToko[$i]['produk'] = $produkDB;
                        if (empty($arrayToko[$i]['produk'])) {
                            array_splice($arrayToko, $i, 1);
                        }
                    }
                }

                $keranjang->lapak = $arrayToko;
                $keranjang->save();

                $tmp_produk = array();
                foreach ($request->produk as $produk) {
                    $data_produk = $this->produk->where('_id', $produk['produk_id'])->first(['harga_produk']);
                    $produk['harga_produk'] = $data_produk['harga_produk'];
                    $produk['rating_produk'] = null;
                    array_push($tmp_produk, $produk);
                }

                $this->transaksi->create([
                    'user_id' => $request->input('user_id'),
                    'lapak_id' => $request->input('lapak_id'),
                    'produk' => $tmp_produk,
                    'status' => "PROSES",
                    'expired_date' => date('d-m-Y', strtotime("+5 day")),
                    'updated_date' => date('d-m-Y'),
                    'created_date' => date('d-m-Y')
                ]);

                return response()->json(["success" => true, "message" => "Berhasil Melakukan Checkout Produk"], 200);
            } else {
                return response()->json(["success" => false, "message" => $dataValidate], 400);
            }
        }
    }

    protected function validateDataRequest($user_id, $request)
    {
        $error = array();

        $cekUser = UserMongoDB::where('_id', $user_id)->first(['_id']);
        if (empty($cekUser)) {
            $error['user_id'] = ["The user id Not Found."];
        }

        if ($request->has('produk_id') && $request->has('jenis_hapus')) {
            $cekProduk = $this->produk->where('_id', $request->produk_id)->first(['_id']);
            if ($request->has('jenis_hapus')) {
                if ($request->jenis_hapus != 'lapak') {
                    if (empty($cekProduk)) {
                        $error['produk_id'] = ["The produk id Not Found."];
                    }
                }
            } else {
                if (empty($cekProduk)) {
                    $error['produk_id'] = ["The produk id Not Found."];
                }
            }
        }

        if ($request->has('lapak_id')) {
            $cekLapak = $this->lapak->where('_id', $request->lapak_id)->first(['_id']);
            if (empty($cekLapak)) {
                $error['lapak_id'] = ["The lapak id Not Found."];
            }
        }

        return $error;
    }

    protected function tambahJumlahProduk($keranjang, $arrayToko, $request, $produk)
    {
        for ($i = 0; $i < count($arrayToko); $i++) {
            if ($produk['lapak_produk']['lapak_id'] == $arrayToko[$i]['lapak_id']) {
                for ($j = 0; $j < count($arrayToko[$i]['produk']); $j++) {
                    if ($produk['_id'] == $arrayToko[$i]['produk'][$j]['produk_id']) {
                        $arrayToko[$i]['produk'][$j]['jumlah_produk'] += $request->jumlah_produk;
                    }
                }
            }
        }
        $keranjang->lapak = $arrayToko;
        $keranjang->save();
        return response()->json(["success" => true, "message" => "Berhasil Menambahkan Produk", "data" => $this->tampilProduk($keranjang)], 200);
    }

    protected function tambahProduk($keranjang, $arrayToko, $request, $produk)
    {
        for ($i = 0; $i < count($arrayToko); $i++) {
            if ($produk['lapak_produk']['lapak_id'] == $arrayToko[$i]['lapak_id']) {
                $produk = collect([
                    'produk_id' => $produk['_id'],
                    'jumlah_produk' => (int) $request->jumlah_produk
                ])->toArray();
                array_push($arrayToko[$i]['produk'], $produk);
            }
        }
        $keranjang->lapak = $arrayToko;
        $keranjang->save();
        return response()->json(["success" => true, "message" => "Berhasil Menambahkan Produk", "data" => $this->tampilProduk($keranjang)], 200);
    }

    protected function tambahLapak($keranjang, $arrayToko, $request, $produk)
    {
        $toko = array(
            'lapak_id' => $produk['lapak_produk']['lapak_id'],
            'produk' => array(array(
                'produk_id' => $produk['_id'],
                'jumlah_produk' => (int) $request->jumlah_produk
            ))
        );
        array_push($arrayToko, $toko);

        $keranjang->lapak = $arrayToko;
        $keranjang->save();
        return response()->json(["success" => true, "message" => "Berhasil Menambahkan Produk", "data" => $this->tampilProduk($keranjang)], 200);
    }

    protected function deleteJumlahProduk($keranjang, $arrayToko, $request)
    {
        for ($i = 0; $i < count($arrayToko); $i++) {
            if ($request->lapak_id == $arrayToko[$i]['lapak_id']) {
                $produkDB = $arrayToko[$i]['produk'];
                for ($j = 0; $j < count($produkDB); $j++) {
                    if ($request->produk_id == $produkDB[$j]['produk_id']) {
                        $produkDB[$j]['jumlah_produk'] -= 1;
                        if ($produkDB[$j]['jumlah_produk'] <= 0) {
                            array_splice($produkDB, $j, 1);
                        }
                    }
                }
                $arrayToko[$i]['produk'] = $produkDB;
                if (empty($arrayToko[$i]['produk'])) {
                    array_splice($arrayToko, $i, 1);
                }
            }
        }
        $keranjang->lapak = $arrayToko;
        $keranjang->save();

        return response()->json(["success" => true, "message" => "Berhasil Menghapus Produk", "data" => $this->tampilProduk($keranjang)], 200);
    }

    protected function hapusProduk($keranjang, $arrayToko, $request)
    {
        for ($i = 0; $i < count($arrayToko); $i++) {
            if ($request->lapak_id == $arrayToko[$i]['lapak_id']) {
                $produkDB = $arrayToko[$i]['produk'];
                for ($j = 0; $j < count($produkDB); $j++) {
                    if ($request->produk_id == $produkDB[$j]['produk_id']) {
                        array_splice($produkDB, $j, 1);
                    }
                }
                $arrayToko[$i]['produk'] = $produkDB;
                if (empty($arrayToko[$i]['produk'])) {
                    array_splice($arrayToko, $i, 1);
                }
            }
        }
        $keranjang->lapak = $arrayToko;
        $keranjang->save();

        return response()->json(["success" => true, "message" => "Berhasil Menghapus Produk", "data" => $this->tampilProduk($keranjang)], 200);
    }

    protected function deleteLapak($keranjang, $arrayToko, $request)
    {
        $dataBaruToko = array();
        for ($j = 0; $j < count($arrayToko); $j++) {
            if ($request->lapak_id != $arrayToko[$j]['lapak_id']) {
                $toko = $arrayToko[$j];
                array_push($dataBaruToko, $toko);
            }
        }
        $keranjang->lapak = $dataBaruToko;
        $keranjang->save();
        return response()->json(["success" => true, "message" => "Berhasil Menghapus Produk", "data" => $this->tampilProduk($keranjang)], 200);
    }

    protected function cekLapak($arrayToko, $lapak)
    {
        $toko = false;
        if (count($arrayToko) != 0) {
            foreach ($arrayToko as $arrToko) {
                if ($lapak == $arrToko['lapak_id']) {
                    $toko = true;
                }
            }
        }
        return $toko;
    }

    protected function cekProduk($arrayToko, $lapak, $produk)
    {
        # code...
        $produkvalidate = false;
        if (count($arrayToko) != 0) {
            foreach ($arrayToko as $arrToko) {
                if ($lapak == $arrToko['lapak_id']) {
                    foreach ($arrToko['produk'] as $prdk) {
                        if ($produk == $prdk['produk_id']) {
                            $produkvalidate = true;
                        }
                    }
                }
            }
        }
        return $produkvalidate;
    }

    protected function tampilProduk($keranjang)
    {
        $data_keranjang = array();
        foreach ($keranjang['lapak'] as $lapak) {
            $data_lapak = $this->lapak->where('_id', $lapak['lapak_id'])->first(['nama_lapak', 'no_telepon_lapak']);
            $tmp_lapak['lapak_id'] = $lapak['lapak_id'];
            $tmp_lapak['nama_lapak'] = $data_lapak['nama_lapak'];
            $tmp_lapak['no_telepon_lapak'] = $data_lapak['no_telepon_lapak'];
            $tmp_lapak['produk'] = array();
            foreach ($lapak['produk'] as $produk) {
                $data_produk = $this->produk->where('_id', $produk['produk_id'])->first(['nama_produk', 'gambar_produk', 'harga_produk']);
                $tmp_produk['produk_id'] = $produk['produk_id'];
                $tmp_produk['nama_produk'] = $data_produk['nama_produk'];
                $tmp_produk['gambar_produk'] = $data_produk['gambar_produk'];
                $tmp_produk['harga_produk'] = $data_produk['harga_produk'];
                $tmp_produk['jumlah_produk'] = $produk['jumlah_produk'];
                array_push($tmp_lapak['produk'], $tmp_produk);
            }
            array_push($data_keranjang, $tmp_lapak);
        }
        $keranjang['lapak'] = $data_keranjang;

        return $keranjang;
    }
}
