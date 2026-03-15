<?php

namespace App\Http\Controllers\User\Address;

use App\Models\Address;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Address\AddressRequest;
use App\Repositories\Address\AddressRepository;

class AddressController extends Controller
{
    private AddressRepository $address;

    public function __construct(AddressRepository $addressRepo, Address $address)
    {
        $this->address = $addressRepo;
        $this->address->setModel(Address::make());
    }

    public function index()
    {
        try {
            $data = $this->address->get();
            $data = api_successWithData('address data', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(AddressRequest $request, $id): JsonResponse
    {
        try {
            $this->address->updateAddress($request->validated(), $id);
            $data = api_success('Successfully updated.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function create(AddressRequest $request): JsonResponse
    {
        try {
            $params = $request->validated();
            $this->address->create($params);
            $data = api_success('added successfully.');
            return response()->json($data, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    public function status($id): JsonResponse
    {
        try {
            $data = $this->address->status($id);
            $data = api_successWithData('status has been updated', $data);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function destory($id): JsonResponse
    {
        try {
            $this->address->delete($id);

            $data = api_success('address removed');
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }
}
