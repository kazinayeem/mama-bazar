<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCostHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductCostHistoryService
{
    /**
     * Product columns whose changes are recorded.
     *
     * @var list<string>
     */
    public const TRACKED_FIELDS = ['cost_price', 'profit_margin'];

    /**
     * Record buying-price / margin changes from a Product model event.
     * Values are kept out of the general activity log, which has broader access.
     */
    public static function recordModelChanges(Product $product, bool $wasCreated): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        foreach (self::TRACKED_FIELDS as $field) {
            if ($wasCreated) {
                $oldValue = null;
                $newValue = (float) ($product->getAttribute($field) ?? 0);
                if ($newValue == 0.0) {
                    continue;
                }
            } else {
                if (! $product->wasChanged($field)) {
                    continue;
                }
                $oldValue = (float) ($product->getOriginal($field) ?? 0);
                $newValue = (float) ($product->getAttribute($field) ?? 0);
                if ($oldValue == $newValue) {
                    continue;
                }
            }

            self::write($product->id, $field, $oldValue, $newValue);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, ProductCostHistory>
     */
    public static function forProduct(int $productId, int $limit = 50)
    {
        return ProductCostHistory::where('product_id', $productId)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    private static function write(int $productId, string $field, ?float $oldValue, float $newValue): void
    {
        try {
            [$userId, $userName, $source] = self::resolveActor();

            ProductCostHistory::create([
                'product_id' => $productId,
                'user_id' => $userId,
                'user_name' => $userName,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'source' => $source,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Product cost history could not be recorded', [
                'product_id' => $productId,
                'field' => $field,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array{0: int|null, 1: string|null, 2: string}
     */
    private static function resolveActor(): array
    {
        $user = Auth::user();
        if ($user) {
            return [(int) $user->id, $user->name, request()->is('api/*') ? 'api' : 'admin'];
        }

        $authUser = request()->attributes->get('auth_user');
        if (is_array($authUser) && isset($authUser['id'])) {
            $name = User::whereKey((int) $authUser['id'])->value('name');

            return [(int) $authUser['id'], $name, 'api'];
        }

        return [null, 'System', 'system'];
    }
}
