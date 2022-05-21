<?php

namespace App\Http\Controllers\API\JWTAuth;

use App\Http\Controllers\Controller;
use App\Models\Lapak;
use Illuminate\Http\Request;

use App\Models\UserMongoDB;
use App\Models\UserMysql;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{

    protected $user;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct(UserMongoDB $userMongoDB)
    {
        $this->user = $userMongoDB;
        $this->middleware('jwt.verify', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT Token via login.
     *
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required_if:type,manual|string|min:6',
            'type' => 'required|in:google,manual'
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()], 422);
        } else {
            $cekUserMongoDb = $this->user->where('email', $request->email)->first(['email']);
            $cekUserMysql = UserMysql::where('email', $request->email)->first();
            if ($request->type == 'manual') {
                if (isset($cekUserMysql)) {
                    if (isset($cekUserMongoDb)) {
                        $this->user->where('email', $cekUserMysql['email'])->update([
                            'nama' => $cekUserMysql['nama'],
                            'email' => $cekUserMysql['email'],
                            'password' => $cekUserMysql['password'],
                            'nomor_telepon' => $cekUserMysql['nomor_telepon']
                        ]);
                    } else {
                        $this->user->create($this->user->prepareCreateData($cekUserMysql));
                    }
                }
                try {
                    $credentials = request(['email', 'password']);
                    if (!$token = JWTAuth::attempt($credentials)) {
                        return response()->json(['success' => false, 'message' => 'Email atau Password Salah.'], 401);
                    }
                } catch (JWTException $e) {
                    return response()->json(['success' => false, 'message' => 'Gagal membuat token.'], 500);
                }
            } else {
                try {
                    if (empty($cekUserMysql)) {
                        return response()->json(['success' => false, 'message' => 'Email Tidak Terdaftar.'], 401);
                    }
                    $token = auth()->login($this->googleHandleLogin($cekUserMysql, $cekUserMongoDb, $request));
                } catch (JWTException $e) {
                    return response()->json(['success' => false, 'message' => 'Gagal membuat token.'], 500);
                }
            }
            //Token created, return with success response and jwt token
            return $this->createNewToken($token);
        }
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:App\Models\UserMongoDB,email',
            'password' => 'required|string|min:6',
            'role' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = UserMongoDB::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            //code...
            auth()->logout();
            return response()->json(['success' => true, 'message' => 'Berhasil Logout'], 200);
        } catch (JWTException $e) {
            return response()->json([
                "status" => false,
                "message" => "Failed to logout, please try again."
            ], 500);
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(JWTAuth::refresh());
    }

    public function updatePhoneNumber(Request $request, $user_id)
    {
        if (empty($this->user->where('_id', $user_id)->first(['_id']))) {
            return response()->json(["success" => false, "message" => "User Tidak Ditemukan"], 422);
        }

        $validator = Validator::make($request->all(), [
            'no_telepon' => 'required|digits_between:9,13'
        ]);

        if ($validator->fails()) {
            return response()->json(["success" => false, "message" => $validator->errors()], 422);
        } else {
            try {
                $this->user->where('_id', $user_id)->update(['nomor_telepon' => $request['no_telepon']]);
                return response()->json(["status" => true, "message" => "Berhasil Melakukan Update"], 200);
            } catch (JWTException $e) {
                return response()->json(["status" => false, "message" => "Terjadi Kesalahan"], 500);
            }
        }
    }

    protected function googleHandleLogin($userMysql, $userMongoDB, $request)
    {
        if (isset($userMysql)) {
            if (isset($userMongoDB)) {
                $this->user->where('email', $request['email'])->update([
                    'nama' => $userMysql['nama'],
                    'email' => $userMysql['email'],
                    'nomor_telepon' => $userMysql['nomor_telepon'],
                    'client_id' => $userMysql['client_id'],
                ]);
            } else {
                $userMongoDB = $this->user->create($this->user->prepareCreateData($userMysql));
            }
        }
        return $userMongoDB;
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        $user = $this->user->find(JWTAuth::user()->id);
        $lapak = Lapak::where('user_id', $user['_id'])->first(['_id', 'nama_lapak', 'status_lapak', 'catatan_lapak']);
        return response()->json([
            'success' => true,
            'message' => 'Berhasil Mendapatkan Data',
            'data' => array(
                'access_token' => $token,
                'token_type' => 'bearer',
                'user' => $user,
                'lapak' => $lapak
            )
        ], 200);
    }
}
