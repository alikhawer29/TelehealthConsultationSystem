<?php

namespace App\Core\Traits;

use Exception;
use App\Models\Chat;
use App\Models\User;
use App\Models\Message;
use App\Models\UserBranch;
use App\Models\UserLoginLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Http\Resources\UserLoginResource;


trait Authable
{

    protected function extendValidation(): array
    {
        $valid = true;
        $message = null;

        return [$valid, $message];
    }

    public function login(array $params)
    {
        $email      = $params['email'] ?? null;
        $password   = $params['password'] ?? null;
        $role       = $params['role'] ?? null;
        $deviceId   = $params['device_id'] ?? null;
        $deviceType = $params['device_type'] ?? null;

        if (!$email || !$password) {
            throw new \Exception('Email and password are required.');
        }

        try {
            // ======================================================
            // Handle Admin Login Separately
            // ======================================================
            if ($role === 'admin') {
                // Assuming you have an Admin model
                $admin = \App\Models\Admin::where('email', $email)->with('file:id,path,fileable_id,fileable_type')->first();

                if (!$admin) {
                    throw new \Exception('Invalid email address.');
                }

                if (!Hash::check($password, $admin->password)) {
                    throw new \Exception('Invalid password.');
                }

                $abilities = ['admin'];

                $token = $admin->createToken('admin-device', $abilities);
                $model = $token->accessToken;
                $model->device_token = $deviceId;
                $model->device_type  = $deviceType;
                $model->save();

                return [
                    'role'  => 'admin',
                    'token' => $token->plainTextToken,
                    'user'  => $admin
                ];
            }

            // ======================================================
            // Handle User/Doctor/Nurse/Physician
            // ======================================================
            // Only look up by hash to avoid MAC errors
            $roleLookup = $this->where('email_hash', hash('sha256', strtolower(trim($email))))
                ->select('role')
                ->first();

            if (!$roleLookup) {
                throw new \Exception('Invalid email address.');
            }

            $role = $roleLookup->role;

            $query = $this->where('email_hash', hash('sha256', strtolower(trim($email))))
                ->where('role', $role);

            $user = $query->with('file:id,path,fileable_id,fileable_type')->first();

            if (!$user) {
                throw new \Exception('Invalid email address.');
            }

            // Load role-specific relations
            match ($user->role) {
                'doctor'    => $user->load('education', 'license.file', 'sessionType'),
                'user'      => $user->load('familyMembers', 'insurance.file:id,path,fileable_id,fileable_type'),
                'nurse',
                'physician' => $user->load('education', 'license.file'),
                default     => null
            };

            if (!Hash::check($password, $user->password)) {
                throw new \Exception('Invalid password.');
            }

            // Perform extended validation if necessary
            list($valid, $message) = $user->extendValidation();
            if (!$valid) {
                throw new \Exception($message);
            }

            // Token abilities
            $abilities = match (true) {
                $user->isUser()      => ['user'],
                $user->isDoctor()    => ['doctor'],
                $user->isNurse()     => ['nurse'],
                $user->isPhysician() => ['physician'],
                default              => ['user']
            };

            $token = $user->createToken('user-device', $abilities);
            $model = $token->accessToken;
            $model->device_token = $deviceId;
            $model->device_type  = $deviceType;
            $model->save();

            return [
                'role'  => $user->role,
                'token' => $token->plainTextToken,
                'user'  => $user
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }



    public function register(array $params)
    {
        try {
            return $this->fill($params)->save();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function logout()
    {
        try {
            return $this->currentAccessToken()->delete();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
