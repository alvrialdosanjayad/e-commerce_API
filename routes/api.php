<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\JWTAuth\AuthController;
use App\Http\Controllers\API\KategoriAPIController;
use App\Http\Controllers\API\KeranjangAPIController;
use App\Http\Controllers\API\ProdukAPIController;
use App\Http\Controllers\API\WilayahAPIController;
use App\Http\Controllers\API\LapakAPIController;
use App\Http\Controllers\API\ParokiAPIController;
use App\Http\Controllers\API\RatingAPIController;
use App\Http\Controllers\API\TransaksiAPIController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::middleware('api')->group(function () {

    //Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh-token', [AuthController::class, 'refresh']);
        Route::put('/update/nomor-telepon/{user_id}', [AuthController::class, 'updatePhoneNumber']);
    });

    //Kategori
    Route::prefix('kategori')->middleware('jwt.verify')->group(function () {
        Route::get('/get', [KategoriAPIController::class, 'showAllKategori']);
        Route::post('/create', [KategoriAPIController::class, 'createKategori']);
        Route::put('/update/{kategori_id}', [KategoriAPIController::class, 'updateKategori']);
    });

    //Paroki
    Route::prefix('paroki')->middleware('jwt.verify')->group(function () {
        Route::get('/get', [ParokiAPIController::class, 'showAllParoki']);
        Route::get('/refresh', [ParokiAPIController::class, 'refreshParoki']);
    });

    //Lapak
    Route::prefix('lapak')->middleware('jwt.verify')->group(function () {
        Route::get('/get', [LapakAPIController::class, 'showAllLapak']);
        Route::get('/detail/{id_lapak}/get', [LapakAPIController::class, 'showDetailLapak']);
        Route::get('/paroki/{id_paroki}/get', [LapakAPIController::class, 'showLapakByParoki']);
        Route::post('/create', [LapakAPIController::class, 'createLapak']);
        Route::put('/update/{lapak_id}', [LapakAPIController::class, 'updateLapak']);
    });

    //Produk
    Route::prefix('produk')->middleware('jwt.verify')->group(function () {
        Route::get('/get', [ProdukAPIController::class, 'showAllProduk']);
        Route::get('/detail/{id_produk}/get', [ProdukAPIController::class, 'showDetailProduk']);
        Route::post('/search-filter', [ProdukAPIController::class, 'filterAndSeacrhProduk']);
        Route::post('/create', [ProdukAPIController::class, 'createProduk']);
        Route::post('/update/{produk_id}', [ProdukAPIController::class, 'updateProduk']);
    });

    //Keranjang Belanja
    Route::prefix('keranjang-belanja')->middleware('jwt.verify')->group(function () {
        Route::post('/create', [KeranjangAPIController::class, 'createKeranjangBelanja']);
        Route::post('/add-produk', [KeranjangAPIController::class, 'addProduk']);
        Route::post('/delete-produk', [KeranjangAPIController::class, 'deleteProduk']);
        Route::post('/checkout', [KeranjangAPIController::class, 'checkoutKeranjangBelanja']);
        Route::get('/{user_id}/get', [KeranjangAPIController::class, 'showKeranjang']);
    });

    //Transaksi
    Route::prefix('transaksi')->middleware('jwt.verify')->group(function () {
        Route::get('/get', [TransaksiAPIController::class, 'showTransaksiAdmin']);
        Route::get('/lapak/{lapak_id}/get', [TransaksiAPIController::class, 'showTransaksiLapak']);
        Route::get('/user/{user_id}/get', [TransaksiAPIController::class, 'showTransaksiUser']);
        Route::put('/update-status/{transaksi_id}', [TransaksiAPIController::class, 'statusChange']);
    });

    //Rating
    Route::prefix('rating')->middleware('jwt.verify')->group(function () {
        Route::get('/get', [RatingAPIController::class, 'showAllRating']);
        Route::get('/user/{user_id}/get', [RatingAPIController::class, 'showRatingUser']);
        Route::get('/produk/{produk_id}/get', [RatingAPIController::class, 'showRatingProduk']);
        Route::get('/lapak/{lapak_id}/get', [RatingAPIController::class, 'showRatingLapak']);
        Route::post('/create', [RatingAPIController::class, 'createRating']);
        Route::put('/update/{rating_id}', [RatingAPIController::class, 'updateStatusRating']);
    });
});
