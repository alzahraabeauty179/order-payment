<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Exception;
use Throwable;
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
    public function makeOrder(array $data): Order|array
    {
        DB::beginTransaction();
        try {
            $products = collect($data['products']);
            $productIds = $products->pluck('id')->unique();
            $productModels = Product::whereIn('id', $productIds)
                ->get(['id', 'name', 'price'])
                ->keyBy('id');

            $orderAmountsResult = $this->calculateAmounts($productModels);

            $order = Order::create([
                'user_id' => auth()->user()->id,
                'total_items' => $products->sum('quantity'),

                #TODO: Add tax and discount calculations values
                'subtotal_amount' => $orderAmountsResult['subtotal_amount'],
                'total_amount' => $orderAmountsResult['total_amount'],
                'currency' => $data['currency'],
                'expires_at' => now()->addMinutes(60)
            ]);

            $this->createOrderItems($products, $order, $productModels);

            $this->decreaseStock($products);

            DB::commit();
            return $order;

        } catch (Throwable $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Validate the stock for the given products.
     * 
     * @param   Collection $products
     * @return  void
     * @throws  Exception
     */
    private function validateStock(Collection $products): void
    {
        foreach ($products as $item) {

            $product = Product::lockForUpdate()->find($item['id']);

            if (!$product) {
                throw new Exception("Product not found.");
            }

            if ($product->quantity < $item['quantity']) {
                throw new Exception("Insufficient stock for {$product->name}");
            }
        }
    }

    /**
     * Calculate the cost for the given products.
     * 
     * @param   Collection $products
     * @return  float|array
     */
    public function calculateAmounts(Collection $products): array
    {
        $this->validateStock($products);

        $subtotal = 0;
        foreach ($products as $item) {
            $product = Product::find($item['id']);
            $subtotal += $product->price * $item['quantity'];
        }

        #TODO: Add tax and discount calculations to the result
        return [
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal,
        ];
    }

    /**
     * Create order items for the given products and order.
     * 
     * @param   Collection $products
     * @param   Order $order
     * @param   Collection $productModels
     * @return  void
     */
    public function createOrderItems (Collection $products, Order $order, Collection $productModels): void
    {
        $orderProducts = $products->map(function ($item) use ($productModels) {
            $product = $productModels[$item['id']];

            return [
                'product_id' => $product->id,
                'product_name_snapshot' => $product->name,
                'unit_price_snapshot' => $product->price,
                'quantity' => $item['quantity'],
                'total_price_snapshot' => $product->price * $item['quantity'],
            ];
        });

        $order->items()->createMany($orderProducts->toArray());
    }
    
    /**
     * Decrease the stock for the given products.
     * 
     * @param   Collection $products
     * @return  void
     */
    private function decreaseStock(Collection $products): void
    {
        foreach ($products as $item) {
            $product = Product::lockForUpdate()->find($item['id']);

            $product->decrement('quantity', $item['quantity']);
        }
    }

}