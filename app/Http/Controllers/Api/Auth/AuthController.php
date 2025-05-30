<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Traits\CustomResponseTrait;

class AuthController extends Controller
{
    use CustomResponseTrait;

    public function register(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->customResponse($validator->errors(), 422);
            }
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // $user->assignRole('admin');
            return $this->customResponse('User registered successfully', 200, ['user' => $user]);
        }catch(\Exception $e){
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return $this->customResponse($validator->errors(), 422);
            }

            if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return $this->customResponse('Invalid credentials', 401);
            }

            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->customResponse('User logged in successfully', 200, ['user' => $user, 'token' => $token]);
        }catch(\Exception $e){
            return $this->customResponse($e->getMessage(), 500);
        }
    }

    public function logout(Request $request)
    {
        try{
            $request->user()->currentAccessToken()->delete();
            return $this->customResponse('User logged out successfully', 200);
        }catch(\Exception $e){
            return $this->customResponse($e->getMessage(), 500);
        }
    }


}
