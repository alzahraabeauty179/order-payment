<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Exception;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Controller instance.
     *
     * @param OrderService $orderService
     */
    public function __construct(protected OrderService $orderService) {}

    /**
     * Store a new order.
     * 
     * @param OrderStoreRequest $request
     * @return JsonResponse
     */
    public function store (OrderStoreRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder($request->validated());

            return response()->json([
                'message' => 'Order created successfully.',
                'order' => new OrderResource($order),
            ])->setStatusCode(201);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Order creation failed.',
                'error' => $ex->getMessage(),
            ])->setStatusCode(400);
        }
    }
}
