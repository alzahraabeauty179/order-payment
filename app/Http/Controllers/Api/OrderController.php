<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\GetOrdersRequest;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
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
    public function __construct(protected OrderService $orderService)
    {
    }

    /**
     * Display a list of user's orders.
     * 
     * @param GetOrdersRequest $request
     * @return JsonResponse
     */
    public function index(GetOrdersRequest $request): JsonResponse
    {
        try {
            $orders = $this->orderService->getOrders($request->validated());

            return response()->json([
                'orders' => OrderResource::collection($orders),
            ])->setStatusCode(200);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Failed to retrieve orders.',
                'error' => $ex->getMessage(),
            ])->setStatusCode(400);
        }
    }

    /**
     * Store a new order.
     * 
     * @param OrderStoreRequest $request
     * @return JsonResponse
     */
    public function makeOrder(OrderStoreRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->makeOrder($request->validated());

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

    /**
     * Update an existing order.
     * 
     * @param OrderUpdateRequest $request
     * @param Order $order
     * @return JsonResponse
     */
    public function update (OrderUpdateRequest $request, Order $order):JsonResponse
    {
        try {
            $order = $this->orderService->update($request->validated(), $order);

            return response()->json([
                'message' => 'Order updated successfully.',
                'order' => new OrderResource($order),
            ])->setStatusCode(200);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Order update failed.',
                'error' => $ex->getMessage(),
            ])->setStatusCode(400);
        }
    }

    /**
     * Delete an existing order.
     *
     * @param Order $order
     * @return JsonResponse
     */
    public function destroy(Order $order): JsonResponse
    {
        try {
            $this->orderService->delete($order);

            return response()->json([
                'message' => 'Order deleted successfully.',
            ])->setStatusCode(200);
        } catch (Exception $ex) {
            return response()->json([
                'message' => 'Order deletion failed.',
                'error' => $ex->getMessage(),
            ])->setStatusCode(400);
        }
    }
}
