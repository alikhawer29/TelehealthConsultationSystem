<?php

namespace App\Repositories\Account;

use App\Models\User;
use App\Models\Media;
use App\Models\Vendor;
use GuzzleHttp\Client;
use App\Models\Address;
use App\Models\License;
use App\Models\Education;
use App\Models\Favourite;
use App\Models\Insurance;
use App\Models\Commissions;
use App\Models\SessionType;
use App\Models\BuyerDetails;
use App\Models\FamilyMember;
use App\Models\DriverCompany;
use App\Models\StoreLocation;
use App\Models\CompanyDetails;
use App\Models\SupplierDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\CompanyDeliveryDetails;
use App\Models\SupplierDeliveryAddress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Repositories\Account\AccountRepositoryContract;

class AccountRepository extends BaseRepository implements AccountRepositoryContract
{
    protected $model;


    public function setModel(Model $model)
    {
        $this->model = $model;

        return $this;
    }


    public function getProfile($relations = [])
    {

        return $this->model->loadCount($this->countRelations)->load($relations);
    }

    public function get($relations = [])
    {

        return $this->model->loadCount($this->countRelations)->load($relations);
    }


    public function updateProfile(array $params)
    {

        try {
            $data = $this->model->fill($params)->save();
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateUser(array $params)
    {
        DB::beginTransaction();

        try {
            $user = request()->user(); // Authenticated user
            $userId = $user->id;

            // ✅ Update user profile
            // $this->model->where('id', $userId)->update([
            //     'first_name'    => $params['first_name'],
            //     'last_name'     => $params['last_name'],
            //     'country_code'  => $params['country_code'],
            //     'flag_type'  => $params['flag_type'],
            //     'phone'         => $params['phone'],
            // ]);

            $user = User::findOrFail($userId);

            $user->fill([
                'first_name'   => $params['first_name'],
                'last_name'    => $params['last_name'],
                'country_code' => $params['country_code'] ?? null,
                'phone'        => $params['phone'] ?? null,
                'flag_type'    => $params['flag_type'], // still hashed/encrypted via mutator
            ]);

            $user->save();

            // // ✅ Update or create insurance
            // $insurance = Insurance::firstOrNew(['user_id' => $userId]);
            // $insurance->fill([
            //     'name'               => $params['insurance_name'],
            //     'card_number'        => $params['insurance_card_number'],
            //     'card_holder_name'   => $params['insurance_card_holder_name'],
            // ])->save();

            // ✅ Update user profile file
            if (!empty($params['file'])) {
                Media::where('fileable_type', User::class)
                    ->where('fileable_id', $userId)
                    ->delete();

                $filePath = $this->uploadFile($params['file']);
                $this->storeFile($filePath, $params['file'], $userId, User::class);
            }

            // ✅ Update insurance file
            // if (!empty($params['insurance_file'])) {
            //     Media::where('fileable_type', Insurance::class)
            //         ->where('fileable_id', $insurance->id)
            //         ->delete();

            //     $filePath = $this->uploadFile($params['insurance_file']);
            //     $this->storeFile($filePath, $params['insurance_file'], $insurance->id, Insurance::class);
            // }

            // ✅ Commit if everything is successful
            DB::commit();

            return $user->refresh()->load('insurance');
        } catch (\Throwable $th) {
            DB::rollBack(); // ❌ Revert changes

            throw $th;
        }
    }

    public function insurance(array $params)
    {
        try {
            // Get the authenticated user
            $user = request()->user();

            // Update or create insurance
            $insurance = Insurance::updateOrCreate(
                ['user_id' => $user->id], // Match by user ID
                [
                    'name' => $params['insurance_name'],
                    'card_number' => $params['insurance_card_number'],
                    'card_holder_name' => $params['insurance_card_holder_name'],
                    'user_id' => $user->id,
                    'status' => "0",
                ]
            );

            // Handle Insurance file
            if (isset($params['insurance_file'])) {
                Media::where('fileable_type', Insurance::class)
                    ->where('fileable_id', $insurance->id)
                    ->delete();

                $path = $this->uploadFile($params['insurance_file']);
                $file = $params['insurance_file'];
                $type = get_class($insurance);
                $this->storeFile($path, $file, $insurance->id, $type);
            }

            return $insurance;
        } catch (\Throwable $th) {
            throw $th;
        }
    }



    public function updateDoctor(array $params)
    {
        try {
            $user = request()->user();
            $id = $user->id;

            $user = User::findOrFail($id);

            $user->fill([
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'],
                'country_code' => $params['country_code'],
                'phone' => $params['phone'],
                'about' => $params['about'],
                'experience' => $params['experience'],
                'role' => 'doctor',
                'professional' => 'Consultant',
                'languages' => $params['languages']
            ]);

            $user->save();

            // Handle licenses (deleting previous licenses and inserting new ones)
            // License::where('user_id', $id)->delete();
            $license = License::updateOrCreate(
                ['user_id' => $id],
                [
                    'authroity' => $params['license']['authroity'] ?? null,
                    'number' => $params['license']['number'] ?? null,
                    'expiry' => $params['license']['expiry'] ?? null,
                    'specialty' => $params['license']['specialty'] ?? null,
                ]
            );

            // Handle education (deleting previous education entries and inserting new ones)
            Education::where('user_id', $id)->delete();
            foreach ($params['education'] as $education) {
                Education::create([
                    'user_id' => $id,
                    'institution_name' => $education['institution_name'],
                    'degree_title' => $education['degree_title'],
                ]);
            }

            // Handle session types (deleting previous session types and inserting new ones)
            SessionType::where('user_id', $id)->delete();
            foreach ($params['session_type'] as $session) {
                SessionType::create([
                    'user_id' => $id,
                    'session_type' => $session['type'],
                    'price' => $session['price'],
                ]);
            }

            if (isset($params['file'])) {
                Media::where('fileable_type', 'App\Models\User')->where('fileable_id', $id)->delete();
                $path = $this->uploadFile($params['file']);
                $file = $params['file'];
                $type = get_class($user);
                $path = $this->storeFile($path, $file, $id, $type);
            }
            if (isset($params['license']['license_file'])) {
                Media::where('fileable_type', 'App\Models\License')->where('fileable_id', $license->id)->delete();
                $path = $this->uploadFile($params['license']['license_file']);
                $file = $params['license']['license_file'];
                $type = get_class($license);
                $path = $this->storeFile($path, $file, $license->id, $type);
            }
            $result = $this->model->where('id', $id)->with('file:id,path,fileable_id,fileable_type', 'education', 'license.file', 'sessionType')->first();

            return $result;
        } catch (\Throwable $th) {
            // Log the exception or rethrow as necessary
            throw $th;
        }
    }

    public function updateNurse(array $params)
    {
        try {

            $user = request()->user();
            $id = $user->id;

            $user = User::findOrFail($id);

            $user->fill([
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'],
                'country_code' => $params['country_code'],
                'phone' => $params['phone'],
                'about' => $params['about'],
                'experience' => $params['experience'],
                'role' => 'nurse',
                'professional' => 'Nurse',
                'languages' => $params['languages']
            ]);

            $user->save();

            // Handle licenses (deleting previous licenses and inserting new ones)
            // License::where('user_id', $id)->delete();
            $license = License::updateOrCreate(
                ['user_id' => $id],
                [
                    'authroity' => $params['license']['authroity'] ?? null,
                    'number' => $params['license']['number'] ?? null,
                    'expiry' => $params['license']['expiry'] ?? null,
                ]
            );

            // Handle education (deleting previous education entries and inserting new ones)
            Education::where('user_id', $id)->delete();
            foreach ($params['education'] as $education) {
                Education::create([
                    'user_id' => $id,
                    'institution_name' => $education['institution_name'],
                    'degree_title' => $education['degree_title'],
                ]);
            }

            if (isset($params['file'])) {
                Media::where('fileable_type', 'App\Models\User')->where('fileable_id', $id)->delete();
                $path = $this->uploadFile($params['file']);
                $file = $params['file'];
                $type = get_class($user);
                $path = $this->storeFile($path, $file, $id, $type);
            }
            if (isset($params['license']['license_file'])) {
                Media::where('fileable_type', 'App\Models\License')->where('fileable_id', $license->id)->delete();
                $path = $this->uploadFile($params['license']['license_file']);
                $file = $params['license']['license_file'];
                $type = get_class($license);
                $path = $this->storeFile($path, $file, $license->id, $type);
            }

            $result = $this->model->where('id', $id)->with('file:id,path,fileable_id,fileable_type', 'education', 'license.file')->first();

            return $result;
        } catch (\Throwable $th) {
            // Log the exception or rethrow as necessary
            throw $th;
        }
    }

    public function updatePhysician(array $params)
    {
        try {

            $user = request()->user();
            $id = $user->id;

            $user = User::findOrFail($id);

            $user->fill([
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'],
                'country_code' => $params['country_code'],
                'phone' => $params['phone'],
                'about' => $params['about'],
                'experience' => $params['experience'],
                'role' => 'physician',
                'professional' => 'Physician',
                'languages' => $params['languages']
            ]);

            $user->save();

            // Handle licenses (deleting previous licenses and inserting new ones)
            // License::where('user_id', $id)->delete();
            $license = License::updateOrCreate(
                ['user_id' => $id],
                [
                    'authroity' => $params['license']['authroity'] ?? null,
                    'number' => $params['license']['number'] ?? null,
                    'expiry' => $params['license']['expiry'] ?? null,
                ]
            );

            // Handle education (deleting previous education entries and inserting new ones)
            Education::where('user_id', $id)->delete();
            foreach ($params['education'] as $education) {
                Education::create([
                    'user_id' => $id,
                    'institution_name' => $education['institution_name'],
                    'degree_title' => $education['degree_title'],
                ]);
            }

            if (isset($params['file'])) {
                Media::where('fileable_type', 'App\Models\User')->where('fileable_id', $id)->delete();
                $path = $this->uploadFile($params['file']);
                $file = $params['file'];
                $type = get_class($user);
                $path = $this->storeFile($path, $file, $id, $type);
            }
            if (isset($params['license']['license_file'])) {
                Media::where('fileable_type', 'App\Models\License')->where('fileable_id', $license->id)->delete();
                $path = $this->uploadFile($params['license']['license_file']);
                $file = $params['license']['license_file'];
                $type = get_class($license);
                $path = $this->storeFile($path, $file, $license->id, $type);
            }

            $result = $this->model->where('id', $id)->with('file:id,path,fileable_id,fileable_type', 'education', 'license.file')->first();

            return $result;
        } catch (\Throwable $th) {
            // Log the exception or rethrow as necessary
            throw $th;
        }
    }





    public function profileUpdate(array $params)
    {
        try {
            $user = request()->user();
            $id = $user->id;

            // Common user data
            $userData = [
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'],
                'country_code' => $params['country_code'],
                'phone' => $params['phone'],
                'office_country_code' => $params['office_country_code'],
                'office_phone' => $params['office_phone'],
                'billing_address' => $params['billing_address'],
                'zip_code' => $params['zip_code'],
                'state' => $params['state'],
            ];

            // Additional fields based on role
            if ($user->role === 'buyer') {
                $userData['city'] = $params['city'];
            } else {
                $userData['country'] = $params['country'];
            }

            $result = $this->model->where('id', $id)->update($userData);

            // Handle role-specific details
            if ($user->role === 'buyer') {
                Address::where('user_id', $id)->delete();
                foreach ($params['delivery_address'] as $address) {
                    Address::updateOrCreate([
                        'user_id' => $id,
                        'address' => $address['address'],
                        'city' => $address['city'],
                        'zip_code' => $address['zip_code'],
                        'state' => $address['state'],
                    ]);
                }

                foreach ($params['favourite_suppliers'] as $favourite) {
                    Favourite::updateOrCreate(
                        ['user_id' => $id, 'supplier_id' => $favourite['supplier_id']], // Find record by user_id
                        [
                            'user_id' => $id,
                            'supplier_id' => $favourite['supplier_id'],
                            'terms' => $favourite['terms'],
                            'rating' => $favourite['rating'],
                        ]
                    );
                }

                // Update or create buyer details
                BuyerDetails::updateOrCreate(
                    ['user_id' => $id], // Find record by user_id
                    [
                        'company_name' => $params['company_name'],
                        'title' => $params['title'],
                        'estimated_volumn_per_month' => $params['estimated_volumn_per_month'],
                        // 'terms' => $params['terms'],
                    ]
                );
            } else {
                SupplierDetails::updateOrCreate(
                    ['user_id' => $id], // Find record by user_id
                    [
                        'business_entity_name' => $params['business_entity_name'],
                        'title' => $params['title'],
                        'federal_tax_eid' => $params['federal_tax_eid'],
                        'terms' => $params['terms'],
                        'wholesale_fuel_available' => $params['wholesale_fuel_available'],
                        'hq_address' => $params['hq_address'],
                        'branded_fuel_supply' => $params['branded_fuel_supply'],
                        'existing_delivery_companies' => $params['existing_delivery_companies'],
                    ]
                );

                StoreLocation::where('supplier_id', $id)->delete();
                foreach ($params['store_location'] as $address) {
                    StoreLocation::create(
                        [
                            'supplier_id' => $id,
                            'lat' => $address['lat'],
                            'lng' => $address['lng'],
                            // 'address' => $this->getAddress($address['lat'], $address['lng']),
                            'address' => 'Test',

                        ]
                    );
                }
            }
            if ($params['verification_document']) {
                $this->updateVerificationDocument($params, $user, $id);
            }

            return true;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function companyProfileUpdate(array $params)
    {
        try {
            $user = request()->user();
            $id = $user->id;

            // Common user data
            $userData = [
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'],
                'country_code' => $params['country_code'],
                'phone' => $params['phone'],
                'office_country_code' => $params['office_country_code'],
                'office_phone' => $params['office_phone'],
                'billing_address' => $params['street_address'],
                'zip_code' => $params['zip_code'],
                'city' => $params['city'],
                'state' => $params['state'],
            ];

            $result = $this->model->where('id', $id)->update($userData);

            CompanyDetails::updateOrCreate(
                ['company_id' => $id], // Find record by user_id
                [
                    'company_name' => $params['company_name'],
                    'title' => $params['title'],
                    'federal_tax_eid' => $params['federal_tax_eid'],
                    'terms' => $params['terms'],
                    'terminals' => $params['terminals'],
                    'building_no' => $params['building_no'],
                ]
            );

            CompanyDeliveryDetails::where('company_id', $id)->delete();
            foreach ($params['states'] as $stateId => $address) {
                foreach ($address['county'] as $countyId) {
                    CompanyDeliveryDetails::create([
                        'company_id' => $id, // Assuming $result->id contains the supplier ID
                        'state_id' => $stateId,
                        'county_id' => $countyId,
                    ]);
                }
            }

            return true;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function supplierProfileUpdate(array $params)
    {
        try {
            $user = request()->user();
            $id = $user->id;

            // Common user data
            $userData = [
                'first_name' => $params['first_name'],
                'last_name' => $params['last_name'],
                'country_code' => $params['country_code'],
                'phone' => $params['phone'],
                'office_country_code' => $params['office_country_code'],
                'office_phone' => $params['office_phone'],
                'billing_address' => $params['billing_address'],
                'zip_code' => $params['zip_code'],
            ];

            $result = $this->model->where('id', $id)->update($userData);

            SupplierDetails::updateOrCreate(
                ['user_id' => $id], // Find record by user_id
                [
                    'business_entity_name' => $params['business_entity_name'],
                    'title' => $params['title'],
                    'federal_tax_eid' => $params['federal_tax_eid'],
                    'terms' => $params['terms'],
                    'wholesale_fuel_available' => $params['wholesale_fuel_available'],
                    'hq_address' => $params['hq_address'],
                    'branded_fuel_supply' => $params['branded_fuel_supply'],
                    'existing_delivery_companies' => $params['existing_delivery_companies'],
                ]
            );

            SupplierDeliveryAddress::where('supplier_id', $id)->delete();
            // Assuming $params['state'] contains the structured data array
            foreach ($params['state'] as $stateId => $address) {
                // Iterate over each county for the current state
                foreach ($address['county'] as $countyId) {
                    SupplierDeliveryAddress::create([
                        'supplier_id' => $id, // Assuming $result->id contains the supplier ID
                        'state_id' => $stateId,
                        'county_id' => $countyId,
                    ]);
                }
            }

            // StoreLocation::where('supplier_id', $id)->delete();
            // foreach ($params['store_location'] as $address) {
            //     StoreLocation::create(
            //         [
            //             'supplier_id' => $id,
            //             'lat' => $address['lat'],
            //             'lng' => $address['lng'],
            //             // 'address' => $this->getAddress($address['lat'], $address['lng']),
            //             'address' => 'Test',

            //         ]
            //     );
            // }

            return true;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateVerificationDocument($params, $user, $id)
    {
        // Check if 'verification_document' exists in $params
        if (isset($params['verification_document'])) {
            // Check if 'verification_document' is a URL
            if (filter_var($params['verification_document'], FILTER_VALIDATE_URL)) {
                // It's a URL, do not store it
                // You may handle this case differently if needed
                return;
            } else {
                // It's a file, proceed with upload and store
                $path = $this->uploadFile($params['verification_document']);
                $file = $params['verification_document'];
                $type = get_class($user);
                $path = $this->storeFile($path, $file, $id, $type);
            }
        }
    }




    public function updatePsychologistProfile(array $params)
    {

        try {
            $user = request()->user();
            $type = get_class($user);
            $id = $user->id;

            $result = $this->model->fill(
                [
                    'first_name' => $params['first_name'],
                    'last_name' => $params['last_name'],
                    'gender' => $params['gender'],
                    'phone' => $params['phone'],
                    'country_code' => $params['country_code'],
                    'about' => $params['about'],
                    'medical_id' => $params['medical_id'],
                ]
            )->save();

            if ($params['image']) {
                $path = $this->uploadFile($params['image']);
                $file = $params['image'];
                $path = $this->storeFile($path, $file, $id, $type);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    protected function uploadFile($file)
    {
        return Storage::putFile('public/media', $file);
    }

    protected function storeFile($path, $file, $data, $type)
    {
        return Media::create([
            'path' => basename($path),
            'field_name' => 'image',
            'name' => $file->getClientOriginalName(),
            'fileable_type' => $type,
            'fileable_id' => $data,
        ]);
    }

    public function familyMember(array $params)
    {
        try {
            $user = request()->user();
            $id = $user->id;

            $familyMember = new FamilyMember(); // ✅ Create an instance first
            $familyMember->fill([
                'name' => $params['name'],
                'gender' => $params['gender'],
                'emirates_id' => $params['emirates_id'],
                'user_id' => $id
            ]);
            $familyMember->save(); // ✅ Save the instance

            return $familyMember; // ✅ Return the created record if needed
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function familyMemberEdit(array $params, $id)
    {
        try {
            $user = request()->user();

            $familyMember = FamilyMember::where('id', $id)
                ->where('user_id', $user->id) // ✅ Ensure it belongs to the logged-in user
                ->update([
                    'name'       => $params['name'],
                    'gender'     => $params['gender'],
                    'emirates_id' => $params['emirates_id'],
                ]);

            $familyMember = FamilyMember::find($id);

            return $familyMember; // returns the number of updated rows
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function familyMemberRemove($id)
    {
        try {
            $user = request()->user();

            // Find the family member that belongs to the user
            $familyMember = FamilyMember::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$familyMember) {
                throw new \Exception('Family member not found!');
            }

            return $familyMember->delete(); // ✅ Remove the record

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getfamilyMembers()
    {
        try {
            $user = request()->user();

            // Find the family member that belongs to the user
            $familyMember = FamilyMember::where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->get();

            if (!$familyMember) {
                throw new \Exception('Family member not found!');
            }

            return $familyMember;
        } catch (\Throwable $th) {
            throw $th;
        }
    }






    public function deleteAccount()
    {

        DB::beginTransaction();
        try {

            $tokens = $this->model->tokens()->delete();
            $this->model->delete();
            DB::commit();
            return true;
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }


    public function changePassword($currentPassword, $password)
    {
        DB::beginTransaction();
        try {

            $user = $this->model;
            if (!Hash::check($currentPassword, $user->password)) throw new \Exception('invalid current password.');
            if (Hash::check($password, $user->password)) throw new \Exception('Current and new password cannot not be same.');
            $user->password = $password;
            $user->save();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updateCommision($id, $rate)
    {
        try {
            Commissions::where('vendor_id', $id)->update(['rate' => $rate]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function updateBookingCharges($rate)
    {
        try {
            $user = request()->user();
            $id = $user->id;
            Commissions::create([
                'vendor_id' => $id,
                'rate_type' => 'booking-charges',
                'user_type' => 'psychologist',
                'rate' => $rate,
                'effective_date' => now(),
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function getBookingCharges($data)
    {
        try {
            $user = request()->user();
            $id = $user->id;
            return  Commissions::where('vendor_id',  $id)
                ->where('rate_type',  'booking-charges')
                ->where('user_type',  'psychologist')
                ->when(!empty($data->from) && !empty($data->to), function ($q) use ($data) {
                    $q->whereBetween('effective_date', [$data->from, $data->to]);
                })
                ->latest()
                ->paginate($data->per_page);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    public function updateStripeId($id, $stripId)
    {
        try {
            Vendor::where('id',  $id)->update(['stripe_id' => $stripId]);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function getVendor($id)
    {
        try {
            return  Vendor::where('id',  $id)->first();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    private function getAddress($lat, $lng)
    {
        $client = new Client();
        $response = $client->get('https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => [
                'latlng' => $lat . ',' . $lng,
                'key' => 'AIzaSyAHPUufTlBkF5NfBT3uhS9K4BbW2N-mkb4',
            ]
        ]);

        $geocodeData = json_decode($response->getBody(), true);

        $address = null;

        if ($geocodeData['results']) {
            $address = $geocodeData['results'][0]['formatted_address'];
        }

        return $address;
    }
}
