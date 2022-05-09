<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lapak;
use App\Models\Paroki;
use App\Models\ParokiMysql;
use Illuminate\Http\Request;

class ParokiAPIController extends Controller
{
    protected $paroki;
    protected $lapak;

    /**
     * Create a new ParokiAPIController instance.
     *
     * @return void
     */
    public function __construct(Paroki $paroki, Lapak $lapak)
    {
        $this->paroki = $paroki;
        $this->lapak = $lapak;
    }

    public function showAllParoki()
    {
        # GET SEMUA DATA PAROKI

        return response()->json(["success" => true, "message" => "Berhasil Mendapatkan Data", "data" => $this->paroki->all(['_id', 'nama_paroki'])], 200);
    }

    protected function refreshParoki()
    {
        #GET DATA PAROKI DARI DATABASE E-COMMERCE GEMATEN
        $parokiMongoDB = $this->paroki->all();

        # UPDATE DATA PAROKI DARI MYSQL
        foreach ($parokiMongoDB as $parokis) {
            $data_paroki_mysql = ParokiMysql::where('paroki_id', $parokis['paroki_id_mysql'])->first();
            $data_paroki_mongoDB = $this->paroki->find($parokis['_id']);
            if ($data_paroki_mysql['paroki_nama'] != $data_paroki_mongoDB['nama_paroki']) {
                $data_paroki_mongoDB->nama_paroki = $data_paroki_mysql['paroki_nama'];
                $data_paroki_mongoDB->save();

                $data_lapak = $this->lapak->where('paroki_lapak.paroki_id', $parokis['_id'])->get(['_id']);
                $update_paroki_lapak = array(
                    "paroki_id" => $parokis['_id'],
                    "nama_paroki" => $data_paroki_mysql['paroki_nama']
                );
                foreach ($data_lapak as $lapak_paroki) {
                    $lapak = $this->lapak->find($lapak_paroki['_id']);
                    $lapak->paroki_lapak = $update_paroki_lapak;
                    $lapak->save();
                }
            }
        }

        return response()->json(['success' => true, "message" => "Berhasil Melakukan Update Paroki"], 200);
    }
}
