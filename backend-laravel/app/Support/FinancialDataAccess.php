<?php

namespace App\Support;

use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsureAdminPermission;
use App\Services\RbacService;
use Illuminate\Http\Request;

/**
 * Resolved financial-data visibility for one actor. Cost fields are omitted
 * everywhere unless the matching flag is true, so the default is deny.
 */
final class FinancialDataAccess
{
    public const VIEW_COST_PRICE = 'products.view_cost_price';

    public const EDIT_COST_PRICE = 'products.edit_cost_price';

    public const VIEW_PROFIT_MARGIN = 'analytics.view_profit_margin';

    public const VIEW_COST_VALUATION = 'inventory.view_cost_valuation';

    public const VIEW_SUPPLIER_COST = 'suppliers.view_purchase_cost';

    public const VIEW_COST_HISTORY = 'purchases.view_cost_history';

    public const EXPORT_COST_REPORTS = 'analytics.export_cost_reports';

    public const ALL_CODES = [
        self::VIEW_COST_PRICE,
        self::EDIT_COST_PRICE,
        self::VIEW_PROFIT_MARGIN,
        self::VIEW_COST_VALUATION,
        self::VIEW_SUPPLIER_COST,
        self::VIEW_COST_HISTORY,
        self::EXPORT_COST_REPORTS,
    ];

    /**
     * Request keys (web snake_case and API camelCase) that write product cost data.
     *
     * @var list<string>
     */
    public const PRODUCT_COST_INPUT_KEYS = ['cost_price', 'costPrice', 'profit_margin', 'profitMargin'];

    public function __construct(
        public readonly bool $canViewCostPrice = false,
        public readonly bool $canEditCostPrice = false,
        public readonly bool $canViewProfitMargin = false,
        public readonly bool $canViewCostValuation = false,
        public readonly bool $canViewSupplierCost = false,
        public readonly bool $canViewCostHistory = false,
        public readonly bool $canExportCostReports = false,
    ) {}

    public static function none(): self
    {
        return new self;
    }

    public static function all(): self
    {
        return new self(true, true, true, true, true, true, true);
    }

    public static function forUser(?object $user): self
    {
        if (! EnsureAdminAccess::isAdminLike($user)) {
            return self::none();
        }

        return self::fromChecker(fn (string $code): bool => EnsureAdminPermission::allows($user, [$code]));
    }

    /**
     * Build from the `auth_user` array that JwtAuthMiddleware attaches to API requests.
     *
     * @param  array<string, mixed>  $authUser
     */
    public static function forAuthUser(array $authUser): self
    {
        return self::fromChecker(fn (string $code): bool => RbacService::hasPermission($authUser, $code));
    }

    /**
     * Resolve for the current request: session user first, then the JWT `auth_user`.
     * Anonymous requests always get no financial access.
     */
    public static function forRequest(Request $request): self
    {
        if ($request->user()) {
            return self::forUser($request->user());
        }

        $authUser = $request->attributes->get('auth_user');

        return is_array($authUser) ? self::forAuthUser($authUser) : self::none();
    }

    /**
     * Narrow visibility to what may be written into a downloadable report (CSV/PDF).
     */
    public function forExport(): self
    {
        $export = $this->canExportCostReports;

        return new self(
            $this->canViewCostPrice && $export,
            false,
            $this->canViewProfitMargin && $export,
            $this->canViewCostValuation && $export,
            $this->canViewSupplierCost && $export,
            $this->canViewCostHistory && $export,
            $export,
        );
    }

    /**
     * Per-product profit reveals unit cost, so it needs both cost and margin visibility.
     */
    public function canViewProductProfit(): bool
    {
        return $this->canViewCostPrice && $this->canViewProfitMargin;
    }

    /**
     * Per-product cost valuation divided by stock is the unit cost.
     */
    public function canViewProductCostValuation(): bool
    {
        return $this->canViewCostPrice && $this->canViewCostValuation;
    }

    public function hasAnyAccess(): bool
    {
        return $this->canViewCostPrice || $this->canEditCostPrice || $this->canViewProfitMargin
            || $this->canViewCostValuation || $this->canViewSupplierCost || $this->canViewCostHistory
            || $this->canExportCostReports;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public static function containsProductCostInput(array $input): bool
    {
        foreach (self::PRODUCT_COST_INPUT_KEYS as $key) {
            if (array_key_exists($key, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  callable(string): bool  $can
     */
    private static function fromChecker(callable $can): self
    {
        return new self(
            $can(self::VIEW_COST_PRICE),
            $can(self::EDIT_COST_PRICE),
            $can(self::VIEW_PROFIT_MARGIN),
            $can(self::VIEW_COST_VALUATION),
            $can(self::VIEW_SUPPLIER_COST),
            $can(self::VIEW_COST_HISTORY),
            $can(self::EXPORT_COST_REPORTS),
        );
    }
}
