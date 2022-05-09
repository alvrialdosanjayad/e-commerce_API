<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

use App\Models\Produk;
use App\Models\Lapak;
use App\Models\Rating;
use App\Models\UserMongoDB;
use App\Models\Transaksi;

class RatingAPIController extends Controller
{
    protected $lapak, $produk, $transaksi, $rating, $user;

    /**
     * Create a new ProdukAPIController instance.
     *
     * @return void
     */
    public function __construct(Lapak $lapak, Produk $produk, Transaksi $transaksi, Rating $rating, UserMongoDB $user)
    {
        $this->lapak = $lapak;
        $this->produk = $produk;
        $this->transaksi = $transaksi;
        $this->rating = $rating;
        $this->user = $user;
    }

    public function showAllRating()
    {
        # GET SEMUA DATA RATING
        $rating = $this->rating->all();

        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $this->tampilRating($rating)], 200);
    }

    public function showRatingUser($user_id)
    {
        $cekUser = $this->user->where('_id', '=', $user_id)->first(['_id']);
        if (empty($cekUser)) {
            return response()->json(["success" => false, "message" => "User Tidak Ditemukan"], 404);
        }
        # GET RATING BERDASARKAN USER
        $rating = $this->rating->where("user_id", "=", $user_id)->get();
        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Rating Berdasarkan User", "data" => $this->tampilRating($rating)], 200);
    }

    protected function showRatingLapak($lapak_id)
    {
        $cekLapak = $this->lapak->where('_id', '=', $lapak_id)->first(['_id']);
        if (empty($cekLapak)) {
            return response()->json(["success" => false, "message" => "User Tidak Ditemukan"], 404);
        }

        # GET DATA PRODUK BERDASARKAN LAPAK UNTUK MENDAPATKAN RATING PRODUK
        $data_produk = $this->produk->where('lapak_produk.lapak_id', '=', $lapak_id)->get(['nama_produk', 'rating_produk']);
        $data_rating = array();
        foreach ($data_produk as $produk) {
            if (count($produk['rating_produk']) != 0) {
                foreach ($produk['rating_produk'] as $rating_produk) {
                    $rating_produk['nama_produk'] =  $produk['nama_produk'];
                    array_push($data_rating, $rating_produk);
                }
            }
        }
        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $data_rating], 200);
    }

    public function showRatingProduk($produk_id)
    {
        $cekProduk = $this->produk->where('_id', '=', $produk_id)->first(['_id']);
        if (empty($cekProduk)) {
            return response()->json(["success" => false, "message" => "User Tidak Ditemukan"], 404);
        }

        # GET RATING BERDASARKAN PRODUK
        $rating = $this->rating->where("produk_id", "=", $produk_id)->get();
        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Rating Berdasarkan Produk", "data" => $this->tampilRating($rating)], 200);
    }

    public function createRating(Request $request)
    {
        # VALIDATE RATING REQUEST
        $ratingValidate = Validator::make($request->all(), [
            'gambar_rating' => 'image',
            'produk_id' => 'required',
            'transaksi_id' => 'required',
            'user_id' => 'required',
            'value' => 'required',
            'catatan' => 'required'
        ]);

        if ($ratingValidate->fails()) {
            return response()->json(["success" => false, "message" => $ratingValidate->errors()], 422);
        } else {
            $validateDataRequest = $this->validateRequest($request);
            if (count($validateDataRequest) == 0) {
                $gambar_rating = null;
                if ($request->hasfile('gambar_rating')) {
                    $gambar_rating = $this->setGambarRating($request);
                }

                # CREATE TRANSAKSI
                $rating = $this->rating->create([
                    "transaksi_id" => $request->transaksi_id,
                    "produk_id" => $request->produk_id,
                    "gambar_rating" => $gambar_rating,
                    "user_id" => $request->user_id,
                    "value" => $request->value,
                    "catatan" => $request->catatan,
                    "status" => "UNVERIFIED",
                    "updated_date" => date('d-m-Y'),
                    "created_date" => date('d-m-Y')
                ]);

                # UPDATE TRANSAKSI
                $transaksi = $this->produk->find($rating->transaksi_id);
                $tmp_transaksi = array();
                foreach ($transaksi['produk'] as $produk_transaksi) {
                    if ($produk_transaksi['produk_id'] == $request->produk_id) {
                        $produk_transaksi['rating_produk'] = $rating->_id;
                    }
                    array_push($tmp_transaksi, $produk_transaksi);
                }
                $transaksi->produk = $tmp_transaksi;
                $transaksi->save();

                return response()->json(["success" => true, "message" => "Berhasil Menambahkan Rating Produk", "data" => $rating], 200);
            } else {
                return response()->json(["success" => false, "message" => $validateDataRequest], 422);
            }
        }
    }

    public function updateStatusRating(Request $request, $rating_id)
    {
        $rating = $this->rating->find($rating_id);

        if ($request->status_rating == 'VERIFIED') {

            $rating->status = $request->status_rating;
            $rating->updated_date = date('d-m-Y');
            $rating->save();

            $user = $this->getDataUser($rating['user_id']);

            # UPDATE PRODUK
            $produk = $this->produk->find($rating->produk_id);
            $produk->push('rating_produk', [
                'rating_id' => $rating->_id,
                'nama' => $user['nama_user'],
                'gambar_rating' => $rating->gambar_rating,
                'value' => $rating->value,
                'catatan' => $rating->catatan,
                'updated_at' =>  $rating->updated_date
            ]);
        } else if ($request->status_rating == 'REJECTED') {
            $transaksi = $this->transaksi->find($rating->transaksi_id);
            $tmp_transaksi = array();
            foreach ($transaksi['produk'] as $produk_transaksi) {
                if ($produk_transaksi['produk_id'] == $rating->produk_id) {
                    $produk_transaksi['rating_produk'] = null;
                }
                array_push($tmp_transaksi, $produk_transaksi);
            }
            $transaksi->produk = $tmp_transaksi;
            $transaksi->save();

            if ($rating->gambar_rating != null) {
                Storage::disk('hosting')->delete("rating-produk/" . $rating->gambar_rating);
            }
            $rating->delete();
        }

        return response()->json(["success" => true, "message" => "Berhasil Melakukan Update Rating"], 200);
    }

    protected function getDataUser($id_user)
    {
        $data_user = $this->user->find($id_user);
        $user = array(
            "user_id" => $id_user,
            "nama_user" => $data_user['nama']
        );

        return $user;
    }

    protected function setGambarRating($request)
    {
        $file_gambar = $request->file('gambar_rating');

        # MERUBAH NAMA GAMBAR DAN MENYIMPAN FILE KEDALAM DOLFER
        $nama_gambar = Str::random(15) . time() . '.' . $file_gambar->extension();
        $file_gambar->move(public_path('/rating-produk'),  $nama_gambar);

        return $nama_gambar;
    }

    protected function validateRequest($request)
    {
        $error = [];

        # GET DATA
        $data_transaksi = $this->transaksi->where('_id', $request->transaksi_id)->first(['_id']);
        $data_produk = $this->produk->where('_id', $request->produk_id)->first(['_id']);
        $data_user = $this->user->where('_id', $request->user_id)->first(['_id']);
        // $data_gambar = $request->hasfile('gambar_rating');

        # CEK DATA
        if (empty($data_transaksi)) {
            $error['transaksi_id'] = ["The transaksi id Not Found."];
        }
        if (empty($data_produk)) {
            $error['produk_id'] = ["The produk id Not Found."];
        }
        if (empty($data_user)) {
            $error['user_id'] = ["The user id Not Found."];
        }
        // if (!$data_gambar) {
        //     $error['gambar_rating'] = ["The gambar rating Not Found."];
        // }

        return $error;
    }

    protected function tampilRating($rating)
    {
        $data_rating = array();
        foreach ($rating as $rtng) {
            $data_produk = $this->produk->where('_id', $rtng['produk_id'])->first(['_id', 'nama_produk']);
            $data_user = $this->user->where('_id', $rtng['user_id'])->first(['_id', 'nama']);
            $tmp_transaksi['_id'] = $rtng['_id'];
            $tmp_transaksi['transaksi_id'] = $rtng['transaksi_id'];
            $tmp_transaksi['produk'] = $data_produk;
            $tmp_transaksi['user'] = ['user_id' => $data_user['_id'], 'nama_user' => $data_user['nama']];
            $tmp_transaksi['gambar_rating'] = $rtng['gambar_rating'];
            $tmp_transaksi['value'] = $rtng['value'];
            $tmp_transaksi['catatan'] = $rtng['catatan'];
            $tmp_transaksi['status'] = $rtng['status'];
            $tmp_transaksi['updated_date'] = $rtng['updated_date'];
            $tmp_transaksi['created_date'] = $rtng['created_date'];
            array_push($data_rating, $tmp_transaksi);
        }
        return $data_rating;
    }
}
