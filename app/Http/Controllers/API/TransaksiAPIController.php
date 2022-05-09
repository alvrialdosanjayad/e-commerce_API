<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Lapak;
use App\Models\Transaksi;
use App\Models\Produk;
use App\Models\UserMongoDB;

class TransaksiAPIController extends Controller
{
    protected $lapak, $produk, $transaksi;

    /**
     * Create a new ProdukAPIController instance.
     *
     * @return void
     */
    public function __construct(Lapak $lapak, Produk $produk, Transaksi $transaksi)
    {
        $this->lapak = $lapak;
        $this->produk = $produk;
        $this->transaksi = $transaksi;
    }

    public function showTransaksiAdmin()
    {
        # CEK EXPIRED DATE TRANSAKSI
        $this->checkStatusTransaksi();

        # GET SEMUA DATA TRANSAKSI
        $transaksi = $this->transaksi->all();

        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $this->tampilTransaksi($transaksi)], 200);
    }

    public function showTransaksiLapak($lapak_id)
    {
        $cekLapak = $this->lapak->where('_id', '=', $lapak_id)->first(['_id']);
        if (empty($cekLapak)) {
            return response()->json(["success" => false, "message" => "Lapak Tidak Ditemukan"], 404);
        }

        # CEK EXPIRED DATE TRANSAKSI
        $this->checkStatusTransaksi();

        # GET DATA TRANSAKSI BERDASARKAN LAPAK
        $transaksi = $this->transaksi->where("lapak_id", "=", $lapak_id)->get();
        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $this->tampilTransaksi($transaksi)], 200);
    }

    public function showTransaksiUser($user_id)
    {
        $cekUser = UserMongoDB::where('_id', '=', $user_id)->first(['_id']);
        if (empty($cekUser)) {
            return response()->json(["success" => false, "message" => "User Tidak Ditemukan"], 404);
        }

        # CEK EXPIRED DATE TRANSAKSI
        $this->checkStatusTransaksi();

        # GET DATA TRANSAKSI BERDASARKAN USER
        $transaksi = $this->transaksi->where("user_id", "=", $user_id)->get();
        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $this->tampilTransaksi($transaksi)], 200);
    }

    public function statusChange(Request $request, $transaksi_id)
    {
        #CEK TRANSAKSI
        $cekTransaksi = $this->transaksi->where('_id', '=', $transaksi_id)->first(['_id']);
        if (empty($cekTransaksi)) {
            return response()->json(["success" => false, "message" => "Transaksi Tidak Ditemukan"], 404);
        }

        # VALIDATE TRANSAKSI REQUEST
        $transaksiValidate = Validator::make($request->all(), [
            'status' => 'required|in:PROSES,SELESAI,DIBATALKAN'
        ]);

        if ($transaksiValidate->fails()) {
            return response()->json(["success" => false, "message" => $transaksiValidate->errors()], 422);
        } else {
            # UPDATE STATUS TRANSAKSI
            $this->updateTransaksi($transaksi_id, $request->status);

            return response()->json(["success" => true, "message" => "Berhasil Melakukan Update Transaksi"], 200);
        }
    }

    protected function checkStatusTransaksi()
    {
        $transaksi = $this->transaksi->all();

        if (count($transaksi) != 0) {
            for ($i = 0; $i < count($transaksi); $i++) {
                if (date("d-m-Y") >= $transaksi[$i]['expired_date'] && $transaksi[$i]['status'] == "PROSES") {
                    $transaksiUpdate = $this->transaksi->find($transaksi[$i]['_id']);
                    $transaksiUpdate->status = "DIBATALKAN";
                    $transaksiUpdate->updated_date = date('d-m-Y');
                    $transaksiUpdate->save();
                }
            }
        }
    }

    protected function updateTransaksi($transaksi_id, $status_transaksi)
    {
        $transaksi = $this->transaksi->find($transaksi_id);
        $transaksi->status = $status_transaksi;
        $transaksi->updated_date = date('d-m-Y');
        $transaksi->save();

        if ($status_transaksi == "SELESAI") {
            $updateLapak = $this->lapak->find($transaksi['lapak_id']);
            $tmp_produk_lapak = array();
            $tmp_produk_transaksi = array();
            foreach ($transaksi['produk'] as $produk_transaksi) {
                $updateProduk = $this->produk->find($produk_transaksi['produk_id']);
                $temp_stok_produk = $updateProduk->stok_produk - $produk_transaksi['jumlah_produk'];
                $temp_penjualan = $updateProduk->penjualan_produk + $produk_transaksi['jumlah_produk'];
                if ($temp_stok_produk <= 0) {
                    $temp_stok_produk = 0;
                }
                $updateProduk->stok_produk = $temp_stok_produk;
                $updateProduk->penjualan_produk = $temp_penjualan;
                $updateProduk->save();
                array_push($tmp_produk_transaksi, ["produk_id" => $produk_transaksi['produk_id'], "penjualan_produk" => $temp_penjualan, "stok_produk" => $temp_stok_produk]);
            }
            foreach ($updateLapak['produk_lapak'] as $produk_lapak) {
                foreach ($tmp_produk_transaksi as $produk_transaksi) {
                    if ($produk_lapak['produk_id'] == $produk_transaksi['produk_id']) {
                        $produk_lapak['stok_produk'] = $produk_transaksi['stok_produk'];
                        $produk_lapak['penjualan_produk'] = $produk_transaksi['penjualan_produk'];
                    }
                }
                array_push($tmp_produk_lapak, $produk_lapak);
            }

            $updateLapak->produk_lapak = $tmp_produk_lapak;
            $updateLapak->save();
        }
    }

    protected function tampilTransaksi($transaksi)
    {
        $data_transaksi = array();
        foreach ($transaksi as $trnsksi) {
            $data_lapak = $this->lapak->where('_id', $trnsksi['lapak_id'])->first(['nama_lapak']);
            $data_user = UserMongoDB::where('_id', $trnsksi['user_id'])->first(['_id', 'nama']);
            $tmp_transaksi['_id'] = $trnsksi['_id'];
            $tmp_transaksi['user'] = $data_user;
            $tmp_transaksi['lapak_id'] = $trnsksi['lapak_id'];
            $tmp_transaksi['nama_lapak'] = $data_lapak['nama_lapak'];
            $tmp_transaksi['produk'] = array();
            $tmp_transaksi['status'] = $trnsksi['status'];
            $tmp_transaksi['expired_date'] = $trnsksi['expired_date'];
            $tmp_transaksi['updated_date'] = $trnsksi['updated_date'];
            $tmp_transaksi['created_date'] = $trnsksi['created_date'];
            foreach ($trnsksi['produk'] as $produk) {
                $data_produk = $this->produk->where('_id', $produk['produk_id'])->first(['nama_produk', 'gambar_produk']);
                $tmp_produk['produk_id'] = $produk['produk_id'];
                $tmp_produk['nama_produk'] = $data_produk['nama_produk'];
                $tmp_produk['gambar_produk'] = $data_produk['gambar_produk'];
                $tmp_produk['harga_produk'] = $produk['harga_produk'];
                $tmp_produk['jumlah_produk'] = $produk['jumlah_produk'];
                $tmp_produk['rating_produk'] = $produk['rating_produk'];
                array_push($tmp_transaksi['produk'], $tmp_produk);
            }
            array_push($data_transaksi, $tmp_transaksi);
        }
        return $data_transaksi;
    }
}
