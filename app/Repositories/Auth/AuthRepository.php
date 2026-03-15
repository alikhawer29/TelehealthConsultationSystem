<?php

namespace App\Repositories\Auth;

use Throwable;
use App\Models\User;
use App\Models\Media;
use App\Models\Insurance;
use App\Models\ReminderSetting;
use App\Mail\UserRegisteredMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Repositories\Auth\AuthContract as AuthContract;

class AuthRepository  implements AuthContract
{

    protected $model;

    public function __construct(Model $model = null)
    {
        $this->model = $model;
    }

    public function setModel(Model $user)
    {
        $this->model = $user;

        return $this;
    }

    public function login(array $params, bool $rememberMe = false)
    {

        try {
            return $this->model->login($params, $rememberMe);
        } catch (\Throwable $e) {

            throw new \Exception($e->getMessage());
        }
    }



    public function register(array $params)
    {
        DB::beginTransaction();
        try {
            $result = $this->model->register($params);
            DB::commit();
            return $result;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function registerUser(array $params)
    {
        DB::beginTransaction();

        try {

            $user = new User();
            $user->first_name   = $params['first_name'];
            $user->last_name    = $params['last_name'];
            $user->email        = $params['email'];
            $user->country_code = $params['country_code'] ?? null;
            $user->phone        = $params['phone'] ?? null;
            $user->password     = $params['password']; // still hashed
            $user->flag_type     = $params['flag_type']; // still hashed
            $user->scan_type     = $params['scanType'] ?? null;

            $user->role         = 'user';
            $user->status       = 1;
            $user->save();

            ReminderSetting::create([
                'user_type' => 'user',
                'reminder_time' => '5_min',
                'reference_id' => $user->id
            ]);

            if (isset($params['insurance_name'])) {
                $insurance = Insurance::create([
                    'name' => $params['insurance_name'],
                    'card_number' => $params['insurance_card_number'],
                    'card_holder_name' => $params['insurance_card_holder_name'],
                    'user_id' => $user->id,
                ]);
            }

            if (isset($params['insurance_file'])) {
                Media::where('fileable_type', Insurance::class)
                    ->where('fileable_id', $insurance->id)
                    ->delete();

                $path = $this->uploadFile($params['insurance_file']);
                $file = $params['insurance_file'];
                $type = get_class($insurance);
                $path = $this->storeFile($path, $file, $insurance, $type);
            }

            if (isset($params['doc'])) {
                $path = $this->uploadFile($params['doc']);
                $file = $params['doc'];
                $type = 'App\Models\Passport';
                $path = $this->storeFile($path, $file, $user, $type, 'file');
            }


            // $zohoData = [
            //     'name'  => $user->first_name . ' ' . $user->last_name,
            //     'email' => $user->email,
            //     'role'  => $user->role,
            //     'id'  => $user->id,
            // ];

            // $zohoService = app(\App\Services\ZohoService::class); // or resolve via constructor
            // $response = $zohoService->createVisitor($zohoData);
            // // ✅ Save Zoho ID to created user
            // \Log::info('Zoho response create visitor:', $response);
            // \Log::info('Zoho response visitor data is :', $response['data']);
            // $user->update(['zoho_id' => $response['data']['id'] ?? null]);


            DB::commit();
            // ✅ Send welcome email
            Mail::to($user->email)->send(new UserRegisteredMail($user));
            return $user;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    //over ride call method
    protected function call($class, $silent = false, $parameters = [])
    {
        $seeder = app($class);

        if (!empty($parameters)) {
            $seeder->run($parameters['user_id']);
        } else {
            $seeder->run();
        }
    }

    protected function uploadFile($file)
    {
        return Storage::putFile('public/media', $file);
    }

    protected function storeFile($path, $file, $data, $type, $fieldName = 'image')
    {
        return Media::create([
            'path' => basename($path),
            'field_name' => $fieldName,
            'name' => $file->getClientOriginalName(),
            'fileable_type' => $type,
            'fileable_id' => $data->id,
        ]);
    }

    public function logout(): bool
    {
        try {
            return $this->model->logout();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function findLatest(array $relations = [])
    {
        try {
            return $this->model->latest()->first();
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
