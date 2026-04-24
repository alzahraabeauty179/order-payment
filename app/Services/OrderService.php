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
     * Handle the retrieval of user's orders with optional filters.
     * 
     * @param   array $filters
     * @return  Collection
     */
    public function getOrders(array $filters): Collection
    {
        $query = Order::query()->where('user_id', auth()->id());

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with('items')->orderBy('id', 'desc')->get();
    }

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
                ->lockForUpdate()
                ->get(['id', 'name', 'quantity', 'price'])
                ->keyBy('id');

            $orderAmountsResult = $this->calculateAmounts($productModels, $products);

            $order = Order::create([
                'user_id' => auth()->id(),
                'total_items' => $products->sum('quantity'),

                #TODO: Add tax and discount calculations values
                'subtotal_amount' => $orderAmountsResult['subtotal_amount'],
                'total_amount' => $orderAmountsResult['total_amount'],
                'currency' => $data['currency'],
                'expires_at' => now()->addMinutes(60)
            ]);

            $this->createOrderItems($products, $order, $productModels);

            $this->decreaseStock($productModels, $products);

            DB::commit();
            return $order;

        } catch (Throwable $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    /**
     * Calculate the cost for the given products.
     * 
     * @param   Collection $productsModel
     * @param   Collection $products
     * @return  float|array
     */
    public function calculateAmounts(Collection $productsModel, Collection $products): array
    {
        $this->validateStock($productsModel, $products);

        $subtotal = 0;
        foreach ($products as $item) {
            $product = $productsModel->get($item['id']);

            $subtotal += $product->price * $item['quantity'];
        }

        #TODO: Add tax and discount calculations to the result
        return [
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal,
        ];
    }

    /**
     * Validate the stock for the given products.
     * 
     * @param   Collection $productsModel
     * @param   Collection $products
     * @return  void
     * @throws  Exception
     */
    private function validateStock(Collection $productsModel, Collection $products): void
    {
        foreach ($products as $item) {

            $product = $productsModel->get($item['id']);

            if (!$product) {
                throw new Exception("Product not found.");
            }

            if ($product->quantity < $item['quantity']) {
                throw new Exception("Insufficient stock for {$product->name}");
            }
        }
    }

    /**
     * Create order items for the given products and order.
     * 
     * @param   Collection $products
     * @param   Order $order
     * @param   Collection $productModels
     * @return  void
     */
    public function createOrderItems(Collection $products, Order $order, Collection $productModels): void
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
     * @param   Collection $productsModel
     * @param   Collection $products
     * @return  void
     */
    private function decreaseStock(Collection $productsModel, Collection $products): void
    {
        foreach ($products as $item) {
            $product = $productsModel->get($item['id']);

            $product->decrement('quantity', $item['quantity']);
        }
    }

    /**
     * Update an existing order with partial data while ensuring data integrity.
     *
     * This method updates basic order attributes (such as status and currency) and,
     * when provided, fully synchronizes the order items. It recalculates order totals,
     * replaces existing items, and adjusts product stock levels based on the difference
     * between previous and new quantities.
     *
     * The entire operation is executed within a database transaction to guarantee atomicity.
     * If any error occurs (e.g., insufficient stock), all changes are rolled back to maintain
     * data consistency.
     *
     * @param array $data
     * @param Order $order
     *
     * @return Order
     */
    public function update(array $data, Order $order): Order
    {
        DB::beginTransaction();
        try {
            if (array_key_exists('status', $data)) {
                $order->status = $data['status'];
            }

            if (array_key_exists('currency', $data)) {
                $order->currency = $data['currency'];
            }

            if (array_key_exists('products', $data)) {

                $newProducts = collect($data['products']);
                $newProductIds = $newProducts->pluck('id')->unique();
                $oldProductIds = $order->items()->pluck('product_id');

                $allProductIds = $oldProductIds
                    ->merge($newProductIds)
                    ->unique();

                $productModels = Product::whereIn('id', $allProductIds)
                    ->lockForUpdate()
                    ->get(['id', 'name', 'quantity', 'price'])
                    ->keyBy('id');

                $oldItems = $order->items()
                    ->get()
                    ->keyBy('product_id');

                $this->syncStock($oldItems, $newProducts, $productModels);

                $orderAmounts = $this->calculateAmounts($productModels, $newProducts);

                $order->subtotal_amount = $orderAmounts['subtotal_amount'];
                $order->total_amount = $orderAmounts['total_amount'];
                $order->total_items = $newProducts->sum('quantity');

                $order->items()->delete();
                $this->createOrderItems($newProducts, $order, $productModels);
            }

            $order->save();
            DB::commit();

            return $order;

        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Synchronize product stock levels based on differences between old and new order items.
     *
     * This method calculates the quantity difference (delta) for each product by comparing
     * existing order items with the incoming data, then updates stock accordingly:
     *
     * - Increasing quantity → decreases stock.
     * - Decreasing quantity → increases stock.
     * - Removing a product → restores its previous quantity to stock.
     * - Adding a new product → reduces stock.
     *
     * The method ensures stock never goes below zero. If insufficient stock is detected,
     * an exception is thrown, causing the surrounding transaction to roll back.
     *
     * Note: This method assumes products are already locked using `lockForUpdate()` to
     * prevent race conditions in concurrent environments.
     *
     * @param Collection $oldItems
     * @param Collection $newProducts
     * @param Collection $productModels
     *
     * @return void
     */
    private function syncStock(Collection $oldItems, Collection $newProducts, Collection $productModels): void
    {
        $newItems = $newProducts->keyBy('id');

        foreach ($newItems as $productId => $item) {

            $newQty = $item['quantity'];
            $oldQty = $oldItems[$productId]->quantity ?? 0;

            $diff = $newQty - $oldQty;

            if ($diff > 0) {
                $product = $productModels->get($productId);
                if (!$product) {
                    throw new Exception("Product {$productId} not found");
                }

                \Log::debug("Checking stock for product ID {$productId}: current stock = {$product->quantity}, required additional = {$diff}");

                if ($product->quantity < $diff) {
                    throw new Exception(
                        "Insufficient stock for product {$product->name}. Available: {$product->quantity}"
                    );
                }

                $product->decrement('quantity', $diff);

            } elseif ($diff < 0) {
                $productModels->get($productId)->increment('quantity', abs($diff));
            }
        }

        // Handle removed products (those that existed before but are not in the new list)
        foreach ($oldItems as $productId => $oldItem) {
            if (!isset($newItems[$productId])) {
                $productModels->get($productId)->increment('quantity', $oldItem->quantity);
            }
        }
    }

}