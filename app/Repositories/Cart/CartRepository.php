<?php

namespace App\Repositories\Cart;

use App\Models\Service;
use App\Models\BundleService;
use App\Models\ServiceBundle;
use App\Core\Abstracts\Filters;
use Illuminate\Support\Facades\Log;
use App\Core\Abstracts\Repository\BaseRepository;
use App\Repositories\Cart\CartRepositoryContract;
use Illuminate\Support\Facades\DB;

class CartRepository extends BaseRepository implements CartRepositoryContract
{


    public function updateOrCreate(array $conditionalParams, array $newParams)
    {
        DB::beginTransaction();
        try {
            $this->model->updateOrCreate($conditionalParams, $newParams);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    // public function checkout($params)
    // {
    //     if ($params->isEmpty()) {
    //         return [
    //             'message' => 'No services selected for checkout.',
    //         ];
    //     }

    //     $firstCart = $params->first();
    //     $serviceId = $firstCart->service_id;

    //     $serviceData = Service::findOrFail($serviceId);
    //     $serviceType = $serviceData->type;

    //     // Use match expression for cleaner type mapping (PHP 8+)
    //     $type = $serviceType === 'lab' ? 'lab_custom' : 'iv_drip_custom';
    //     $bundleName = $serviceType === 'lab' ? 'Customized Bundle' : 'IV Drip Customized Bundle';
    //     $description = $serviceType === 'lab'
    //         ? 'This is a customized Bundle'
    //         : 'This is an IV drip customized Bundle';

    //     // Calculate total price
    //     $totalPrice = $params->sum('charges') ?? 0;

    //     DB::beginTransaction();
    //     try {
    //         // Create the bundle
    //         $bundle = ServiceBundle::create([
    //             'bundle_name' => $bundleName,
    //             'description' => $description,
    //             'price' => $totalPrice,
    //             'type' => $type,
    //             'status' => 1,
    //         ]);

    //         if (!$bundle) {
    //             throw new \Exception('Customized bundle creation failed.');
    //         }

    //         // Prepare service list for insertion
    //         $servicesData = $params->map(function ($service) use ($bundle) {
    //             return [
    //                 'bundle_id' => $bundle->id,
    //                 'service_id' => $service->service_id,
    //             ];
    //         })->toArray();

    //         // Bulk insert into BundleService
    //         BundleService::insert($servicesData);

    //         DB::commit();

    //         return [
    //             'service_id'   => $bundle->id,
    //             'type'         => $bundle->type,
    //             'total_price'  => $totalPrice,
    //         ];
    //     } catch (\Throwable $th) {
    //         DB::rollBack();

    //         // Use a custom exception or log for better tracking
    //         throw new \Exception("Checkout failed: " . $th->getMessage(), 500, $th);
    //     }
    // }

    public function checkout($params)
    {
        if ($params->isEmpty()) {
            return [
                'message' => 'No services selected for checkout.',
            ];
        }

        // Separate cart items
        $bundleIds  = $params->where('type', 'bundle')->pluck('service_id');
        $serviceIds = $params->where('type', 'service')->pluck('service_id');

        // Collect service types (from both direct services + bundles)
        $serviceTypes = collect();

        if ($serviceIds->isNotEmpty()) {
            $serviceTypes = $serviceTypes->merge(
                Service::whereIn('id', $serviceIds)->pluck('type')
            );
        }

        if ($bundleIds->isNotEmpty()) {
            $bundleServiceIds = BundleService::whereIn('bundle_id', $bundleIds)->pluck('service_id');
            $serviceTypes = $serviceTypes->merge(
                Service::whereIn('id', $bundleServiceIds)->pluck('type')
            );
        }

        $uniqueTypes = $serviceTypes->unique();

        // Decide bundle type & name
        $type = 'custom';
        $bundleName = 'Custom Bundle';
        $description = 'This is a custom bundle.';

        if ($bundleIds->isEmpty() && $uniqueTypes->count() === 1) {
            if ($uniqueTypes->first() === 'lab') {
                $type = 'lab_custom';
                $bundleName = 'Customized Lab Bundle';
            } elseif ($uniqueTypes->first() === 'iv_drip') {
                $type = 'iv_drip_custom';
                $bundleName = 'Customized IV Drip Bundle';
            }
        }

        // Calculate total
        $totalPrice = $params->sum('charges') ?? 0;

        DB::beginTransaction();
        try {
            $bundle = ServiceBundle::create([
                'bundle_name'  => $bundleName,
                'description'  => $description,
                'price'        => $totalPrice,
                'type'         => $type,
                'status'       => 1,
            ]);

            if (!$bundle) {
                throw new \Exception('Customized bundle creation failed.');
            }

            // Flatten all services (direct + from bundles)
            $allServiceIds = $serviceIds->toArray();

            if ($bundleIds->isNotEmpty()) {
                $bundleServiceIds = BundleService::whereIn('bundle_id', $bundleIds)->pluck('service_id');
                $allServiceIds = array_merge($allServiceIds, $bundleServiceIds->toArray());
            }

            // Initialize $allServices variable
            $allServices = collect();

            // Handle case when only normal services are selected
            if ($serviceIds->isNotEmpty()) {
                $directServices = Service::whereIn('id', $serviceIds)
                    ->get(['id', 'type']);
                $allServices = $allServices->merge($directServices);
            }

            // Handle case when bundle services are selected
            if ($bundleIds->isNotEmpty()) {
                $bundleServices = ServiceBundle::whereIn('id', $bundleIds)
                    ->get(['id', 'type']);
                $allServices = $allServices->merge($bundleServices);
            }

            // Remove duplicates
            $allServices = $allServices->unique('id');

            $servicesData = $allServices->map(function ($service) use ($bundle) {
                return [
                    'bundle_id'  => $bundle->id,
                    'service_id' => $service->id,
                    'type'       => $service->type,
                ];
            })->toArray();

            BundleService::insert($servicesData);

            DB::commit();

            return [
                'service_id'  => $bundle->id,
                'type'        => $bundle->type,
                'total_price' => $totalPrice,
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            throw new \Exception("Checkout failed: " . $th->getMessage(), 500, $th);
        }
    }






    public function delete(int $id, Filters|null $filter = null)
    {
        try {
            $model = $this->model->filter($filter)->firstOrFail();
            $model->delete();
            return true;
        } catch (\Throwable $th) {
            throw new \Exception('item doesn\'t exists', previous: $th);
        }
    }

    public function deleteAll(Filters|null $filter = null)
    {
        try {
            $this->model->filter($filter)->delete();
            return true;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
