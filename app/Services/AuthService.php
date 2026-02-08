<?php
namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService 
{
    /**
     * Register a new user.
     * 
     * @param array $data
     * @return void
     */
    public function register (array $data) : void
    {
        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone_number' => $data['phone_number'],
                'address' => $data['address'],
                'password' => bcrypt($data['password']),
            ]);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * Login a user.
     * 
     * @param array $data
     * @return array
     */
    public function login (array $data) : array
    {
        if (! $token = Auth::attempt(
            ['email' => $data['email'], 'password' => $data['password']], $data['remember'] ?? false
        )){
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.']
            ]);
        }

        return [
            'user' => Auth::user(),
            'token' => $token,
        ];
    }
}