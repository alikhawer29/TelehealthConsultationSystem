<?php

namespace App\Http\Controllers\User\Cart;

use App\Models\Cart;
use App\Models\Shop;
use App\Models\Product;
use App\Models\Service;
use App\Models\Commissions;
use Illuminate\Http\Request;
use App\Models\BundleService;
use App\Models\ServiceBundle;
use Illuminate\Http\Response;
use App\Models\ProductVariant;
use App\Filters\User\CartFilters;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Http\Resources\CartItemResource;
use App\Repositories\Cart\CartRepository;
use App\Repositories\Shop\ShopRepository;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Repositories\Product\ProductRepository;
use App\Repositories\Service\ServiceRepository;
use App\Repositories\Commissions\CommissionRepository;

class CartController extends Controller
{
    protected $cart;
    protected $product;
    protected $shop;
    protected $commission;

    protected $service;

    public function __construct(
        CartRepository $repo,
        Cart $cart,
        ProductRepository $productRep,
        Product $product,
        CommissionRepository $commissionRep,
        Commissions $commission,

        ServiceRepository $serviceRepo,
        Service $service

    ) {
        $this->cart = $repo;
        $this->cart->setModel($cart);
        $this->product = $productRep;
        $this->product->setModel($product);
        $this->commission = $commissionRep;
        $this->commission->setModel($commission);

        $this->service = $serviceRepo;
        $this->service->setModel($service);
    }

    public function index(Request $request, CartFilters $filter): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $filter->extendRequest(['personal' => $userId]);

            // Fetch all cart items with related service data
            $cartItems = $this->cart->findAll(
                filter: $filter,
                relations: [
                    'service' => fn($q) => $q->select('id', 'name', 'price', 'type')
                        ->with(['file']),
                    'bundleService' => fn($q) => $q->select('id', 'bundle_name as name', 'price', 'type')
                        ->with(['file']),
                ]
            );

            // Calculate total cart value
            $totalAmount = $cartItems->sum('charges');

            // Format response
            $data = [
                'items' => CartResource::collection($cartItems),
                'summary' => [
                    'total_items' => $cartItems->count(),
                    'total_price' => $totalAmount,
                ],
            ];

            return response()->json(api_successWithData('My Cart', $data));
        } catch (\Throwable $e) {
            return response()->json(api_error('Failed to fetch cart: ' . $e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function count(Request $request, CartFilters $filter): JsonResponse
    {
        try {

            $filter->extendRequest([
                'personal' => $request->user()->id,
            ]);
            $total_cart = 0;
            $total_cart = $this->cart->getTotal($filter);
            $data = api_successWithData('user cart', compact('total_cart'));

            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }


    public function update(AddToCartRequest $request, CartFilters $filter): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $serviceId = (int) $request->input('service_id');
            $type = $request->input('service_type');
            if ($type === 'service') {
                $service = $this->service->findById($serviceId);
            } elseif ($type === 'bundle') {
                $service = ServiceBundle::find($serviceId);
            }

            $serviceType = $service->type;
            if (!$service) {
                return response()->json(api_error('Service not found.'), Response::HTTP_NOT_FOUND);
            }

            // Check if the same service already exists in the cart
            $serviceExists = Cart::where('user_id', $userId)
                ->where('service_id', $serviceId)
                ->exists();

            if ($serviceExists) {
                return response()->json(api_error("This service is already in the cart."), Response::HTTP_CONFLICT);
            }

            // Get the first cart item to check its service type
            // $existingCartItem = Cart::where('user_id', $userId)->first();
            // if ($existingCartItem) {
            //     $existingService = $this->service->findById($existingCartItem->service_id);
            //     if ($existingService && $existingService->type !== $serviceType) {
            //         return response()->json(api_error("You can only add services of the same type in the cart."), Response::HTTP_FORBIDDEN);
            //     }
            // }

            // Update or create cart entry
            $this->cart->updateOrCreate(
                ['service_id' => $serviceId, 'user_id' => $userId, 'type' => $type],
                ['charges' => $service->price]
            );

            return response()->json(api_success('Cart updated successfully.'));
        } catch (\Throwable $e) {
            return response()->json(api_error('Failed to update cart: ' . $e->getMessage()), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destory($id, CartFilters $filter): JsonResponse
    {
        try {
            $filter->extendRequest([
                'personal' => request()->user()->id,
            ]);
            $carItems = $this->cart->delete($id, $filter);

            $data = api_success('cart item removed');
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function flushCart(CartFilters $filter)
    {
        try {
            $filter->extendRequest([
                'personal' => request()->user()->id,
            ]);
            $carItems = $this->cart->deleteAll($filter);

            $data = api_success('cart item removed');
            return response()->json($data);
        } catch (\Exception $e) {
            $data = api_error($e->getMessage());
            return response()->json($data, Response::HTTP_CONFLICT);
        }
    }

    public function checkout(CartFilters $filter)
    {
        try {
            $filter->extendRequest([
                'personal' => request()->user()->id,
            ]);

            $cartItems = $this->cart->findAll($filter);
            $result = $this->cart->checkout($cartItems);
            // if ($result) {
            //     $this->cart->deleteAll($filter);
            // }


            return response()->json(api_successWithData('Custom Service bundle created successfully.', $result));
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }



    public function details($id)
    {
        try {
            $bundle = ServiceBundle::with([
                'cartServices.service', // For regular services
            ])->where('id', $id)->first();

            // Transform the response
            $transformedBundle = [
                'id' => $id,
                'service_id' => $bundle->id,
                'charges' => $bundle->price,
                'type' => $bundle->type,
                'cart_services' => $bundle->cartServices->map(function ($cartService) {
                    if ($cartService->type === 'lab_bundle') {
                        $bundle = ServiceBundle::find($cartService->service_id);
                        return [
                            'id' => $cartService->id,
                            'type' => $cartService->type,
                            'name' => $bundle?->bundle_name, // Service details
                            'price' => $bundle?->price, // Service details
                            'type' => $bundle?->type, // Service details
                        ];
                    } else {
                        return [
                            'id' => $cartService->id,
                            'type' => $cartService->type,
                            'name' => $cartService?->service?->name, // Service details
                            'price' => $cartService->service->price, // Service details
                            'type' => $cartService->service->type,
                        ];
                    }
                })
            ];

            return response()->json(api_successWithData('Cart History', $transformedBundle));
        } catch (\Exception $e) {
            return response()->json(api_error($e->getMessage()), Response::HTTP_CONFLICT);
        }
    }
}
