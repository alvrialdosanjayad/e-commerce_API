<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class KategoriAPIController extends Controller
{

    protected $kategori, $produk;

    /**
     * Create a new KategoriAPIController instance.
     *
     * @return void
     */
    public function __construct(Kategori $kategori, Produk $produk)
    {
        $this->kategori = $kategori;
        $this->produk = $produk;
    }

    /**
     * Get All Data Kategori Produk.
     *
     * @return JsonResponse
     */
    public function showAllKategori()
    {
        #GET ALL DATA KATEGORI
        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $this->kategori->all()], 200);
    }

    public function createKategori(Request $request)
    {
        #VALIDATE KATEGORI REQUEST
        $kategoriValidate = Validator::make($request->all(), [
            'iconName' => 'required|max:15|unique:App\Models\Kategori,iconName',
            'iconText' => 'required'
        ]);

        if ($kategoriValidate->fails()) {
            return response()->json(["success" => false, "message" => $kategoriValidate->errors()], 422);
        } else {
            #CREATE KATEGORI
            $kategori = $this->kategori->create($kategoriValidate->validated());

            return response()->json(['success' => true, "message" => "Berhasil Menambahkan Kategori", 'data' => $kategori], 200);
        }
    }

    public function updateKategori(Request $request, $kategori_id)
    {
        # CEK DATA KATEGORI
        if (empty($this->kategori->where('_id', $kategori_id)->first(['_id']))) {
            return response()->json(["success" => false, "message" => "Kategori Tidak Ditemukan"], 404);
        }

        # VALIDATE KATEGORI REQUEST
        $kategoriValidate = Validator::make($request->all(), [
            'iconName' => 'required|max:15|unique:App\Models\Kategori,iconName,' . $kategori_id,
            'iconText' => 'required'
        ]);

        if ($kategoriValidate->fails()) {
            return response()->json(["success" => false, "message" => $kategoriValidate->errors()], 422);
        } else {
            # UPDATE KATEGORI
            $this->kategori->where('_id', $kategori_id)->update($kategoriValidate->validated());

            # UPDATE KATEGORI PADA PRODUK
            $this->updateKategoriProduk($request, $kategori_id);

            return response()->json(['success' => true, "message" => "Berhasil Melakukan Update Kategori"], 200);
        }
    }

    protected function updateKategoriProduk($request, $kategori_id)
    {
        $data_produk = $this->produk->where('kategori_produk.kategori_id', $kategori_id)->get(["_id"]);
        $update_kategori_produk = array(
            "kategori_id" => $kategori_id,
            "iconText" => $request->input('iconText'),
            "iconName" => $request->input('iconName')
        );

        foreach ($data_produk as $produk_kategori) {
            $produk = $this->produk->find($produk_kategori['_id']);
            $produk->kategori_produk = $update_kategori_produk;
            $produk->save();
        }
    }
}
