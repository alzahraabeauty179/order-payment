<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderService
{

    /**
     * Create a new order.
     * 
     * @param   array $data
     * @return  Order|array
     */
    public function createOrder(array $data): Order|array
    {
        try {
            DB::beginTransaction();

            $products = collect($data['products']);
            $orderAmountsResultResult = $this->getOrderAmounts($products);

            if ($orderAmountsResultResult['status'] === 'error') {
                return $orderAmountsResultResult;
            }

            $order = Order::create([
                'user_id' => auth()->user()->id,
                'total_items' => $products->sum('quantity'),

                #TODO: Add tax and discount calculations values

                'subtotal_amount' => $orderAmountsResultResult['subtotal_amount'],
                'total_amount' => $orderAmountsResultResult['total_amount'],
                'currency' => $data['currency'],
                'expires_at' => now()->addMinutes(60)
            ]);

            
        } catch (Exception $ex) {
            DB::rollbackTransaction();
            throw $ex;
        }
    }

    /**
     * Calculate the cost for the given products.
     * 
     * @param   Collection $products
     * @return  float|array
     */
    public function getOrderAmounts(Collection $products): array
    {
        $productIds = $products->pluck('id')->unique();

        $productsModel = Product::whereIn('id', $productIds)
            ->get(['id', 'price'])
            ->keyBy('id');

        $subtotalAmount = $products->sum(function ($item) use ($productsModel) {
            $product = $productsModel->get($item['id']);

            if (!$product) {
                throw new Exception("Product with ID {$item['id']} not found.");
            } elseif ($product->quantity < $item['quantity']) {
                return [
                    'status' => 'error',
                    'message' => "Insufficient stock for product ID {$item['id']}.",
                    'available_stock' => $product->quantity,
                ];
            }

            return $product->price * $item['quantity'];
        });

        #TODO: Add tax and discount calculations to the result
        return [
            'status' => 'passed',
            'subtotal_amount' => $subtotalAmount,
            'total_amount' => $subtotalAmount,
        ];
    }

}