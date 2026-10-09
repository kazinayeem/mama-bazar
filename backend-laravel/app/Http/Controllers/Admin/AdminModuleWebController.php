<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MarketingIntegration;
use App\Models\Order;
use App\Models\PolicyPage;
use App\Models\Product;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\AuditService;
use App\Services\HomepageService;
use App\Services\MemberInvitationService;
use App\Services\RbacService;
use App\Support\AdminNav;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminModuleWebController extends Controller
{
    /* ── Inventory ───────────────────────────────────────────── */

    public function inventory(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $q = Product::query()->orderBy('title');

        if ($filter === 'low') {
            $q->where('track_inventory', true)->where('stock', '>', 0)->where('stock', '<=', 10);
        } elseif ($filter === 'out') {
            $q->where(function ($query) {
                $query->where('stock', '<=', 0)->orWhere('stock_status', 'out_of_stock');
            });
        }

        $products = $q->paginate(25)->withQueryString();

        $stats = [
            'total' => Product::count(),
            'in_stock' => Product::where('stock', '>', 10)->count(),
            'low' => Product::where('track_inventory', true)->where('stock', '>', 0)->where('stock', '<=', 10)->count(),
            'out' => Product::where('stock', '<=', 0)->count(),
        ];

        return view('admin.inventory.index', [
            'products' => $products,
            'stats' => $stats,
            'filter' => $filter,
            'headerTitle' => 'Inventory',
        ]);
    }

    public function adjustStock(Request $request, int $id)
    {
        $validated = $request->validate([
            'delta' => 'required|integer|min:-10000|max:10000',
            'reason' => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($id);
        $delta = (int) $validated['delta'];
        $product->stock = max(0, (int) $product->stock + $delta);
        if ($product->stock <= 0) {
            $product->stock_status = $product->backorder ? 'on_backorder' : 'out_of_stock';
        } elseif ($product->stock_status === 'out_of_stock') {
            $product->stock_status = 'in_stock';
        }
        $product->save();

        try {
            $actor = Auth::user();
            AdminAuditLog::create([
                'actor_id' => $actor?->id,
                'actor_name' => $actor?->name,
                'actor_email' => $actor?->email,
                'action' => 'inventory.adjust',
                'target_type' => Product::class,
                'target_id' => $product->id,
                'details' => trim(($validated['reason'] ?? '') !== '' ? "delta={$delta}; ".$validated['reason'] : "delta={$delta}"),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'status' => 'success',
            ]);
        } catch (\Throwable $e) {
            // Audit logging must never break stock adjustments.
        }

        return back()->with('success', "Stock updated for {$product->title}.");
    }

    /* ── Expenses ────────────────────────────────────────────── */

    public function expenses(Request $request)
    {
        $q = Expense::with(['category', 'member'])->orderByDesc('expense_date');

        if ($search = $request->get('q')) {
            $q->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('vendor', 'like', "%{$search}%");
            });
        }
        if ($status = $request->get('status')) {
            $q->where('status', $status);
        }
        if ($categoryId = $request->get('category_id')) {
            $q->where('category_id', $categoryId);
        }

        $expenses = $q->paginate(20)->withQueryString();
        $categories = ExpenseCategory::orderBy('name')->get();
        $members = User::whereIn('role', ['admin', 'manager', 'editor', 'staff', 'super_admin'])->orderBy('name')->get();

        $stats = [
            'total' => Expense::sum('amount'),
            'approved' => Expense::where('status', 'approved')->sum('amount'),
            'pending' => Expense::where('status', 'pending')->sum('amount'),
            'count' => Expense::count(),
        ];

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'members' => $members,
            'stats' => $stats,
            'headerTitle' => 'Expenses',
        ]);
    }

    public function storeExpense(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category_id' => 'nullable|integer',
            'member_id' => 'nullable|integer',
            'payment_method' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (! empty($data['member_id'])) {
            $member = User::find($data['member_id']);
            $data['member_name'] = $member?->name;
        }
        $data['created_by_id'] = Auth::id();
        $data['status'] = $data['status'] ?? 'pending';

        Expense::create($data);

        return back()->with('success', 'Expense created.');
    }

    public function updateExpense(Request $request, int $id)
    {
        $expense = Expense::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'category_id' => 'nullable|integer',
            'member_id' => 'nullable|integer',
            'payment_method' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'vendor' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (! empty($data['member_id'])) {
            $member = User::find($data['member_id']);
            $data['member_name'] = $member?->name;
        }

        $expense->update($data);

        return back()->with('success', 'Expense updated.');
    }

    public function destroyExpense(int $id)
    {
        Expense::findOrFail($id)->delete();

        return back()->with('success', 'Expense deleted.');
    }

    public function expenseReports(Request $request)
    {
        $tab = $request->get('tab', 'overview');

        $driver = DB::connection()->getDriverName();
        $expenseMonthExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', expense_date)",
            'pgsql' => "to_char(expense_date, 'YYYY-MM')",
            default => "DATE_FORMAT(expense_date, '%Y-%m')",
        };

        $orderMonthExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $monthly = Expense::selectRaw("{$expenseMonthExpr} as month, SUM(amount) as total, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $byCategory = Expense::query()
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw("COALESCE(expense_categories.name, 'Uncategorized') as name, SUM(expenses.amount) as total")
            ->groupBy('name')
            ->orderByDesc('total')
            ->get();

        $byMember = Expense::selectRaw("COALESCE(member_name, 'Unassigned') as name, SUM(amount) as total")
            ->groupBy('name')
            ->orderByDesc('total')
            ->get();

        $revenue = Order::whereNotIn('status', ['cancelled', 'refunded'])->sum('total_price');
        $expenseTotal = Expense::where('status', '!=', 'rejected')->sum('amount');

        $profitMonthly = Order::selectRaw("{$orderMonthExpr} as month, SUM(total_price) as revenue")
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $expenseMonthly = $monthly->keyBy('month');
        $profitRows = collect($profitMonthly->keys()->merge($expenseMonthly->keys())->unique()->sort())->map(function ($month) use ($profitMonthly, $expenseMonthly) {
            $rev = (float) ($profitMonthly[$month]->revenue ?? 0);
            $exp = (float) ($expenseMonthly[$month]->total ?? 0);

            return [
                'month' => $month,
                'revenue' => $rev,
                'expenses' => $exp,
                'profit' => $rev - $exp,
            ];
        })->values();

        return view('admin.expenses.reports', [
            'tab' => $tab,
            'monthly' => $monthly,
            'byCategory' => $byCategory,
            'byMember' => $byMember,
            'revenue' => $revenue,
            'expenseTotal' => $expenseTotal,
            'profit' => $revenue - $expenseTotal,
            'profitRows' => $profitRows,
            'headerTitle' => $tab === 'profit' ? 'Profit Overview' : 'Expense Reports',
        ]);
    }

    /* ── Analytics ───────────────────────────────────────────── */

    public function analytics(Request $request)
    {
        $days = (int) $request->get('range', 30);
        $since = now()->subDays($days);

        $orders = Order::where('created_at', '>=', $since);
        $revenue = (clone $orders)->whereNotIn('status', ['cancelled', 'refunded'])->sum('total_price');
        $orderCount = (clone $orders)->count();
        $customers = User::where('role', 'user')->where('created_at', '>=', $since)->count();
        $avgOrder = $orderCount > 0 ? $revenue / $orderCount : 0;

        $statusBreakdown = Order::where('created_at', '>=', $since)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        $paymentBreakdown = Order::where('created_at', '>=', $since)
            ->selectRaw('COALESCE(payment_method, "unknown") as method, COUNT(*) as count')
            ->groupBy('method')
            ->get();

        $revenueTrend = Order::where('created_at', '>=', $since)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->selectRaw('date(created_at) as day, SUM(total_price) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $topProducts = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.created_at', '>=', $since)
            ->whereNotIn('orders.status', ['cancelled', 'refunded'])
            ->selectRaw('COALESCE(products.title, "Unknown") as title, SUM(order_items.quantity) as qty, SUM(order_items.price * order_items.quantity) as revenue')
            ->groupBy('products.title')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        // Guest vs registered
        $guestOrders = Order::where('created_at', '>=', $since)->whereNull('user_id')->count();
        $registeredOrders = Order::where('created_at', '>=', $since)->whereNotNull('user_id')->count();

        // Paid vs created vs cancelled/refunded
        $paidOrders = Order::where('created_at', '>=', $since)->whereIn('payment_status', ['success', 'verified'])->count();
        $cancelledOrders = Order::where('created_at', '>=', $since)->whereIn('status', ['cancelled', 'refunded', 'returned'])->count();

        // Device / browser / source / campaign breakdowns (privacy-conscious aggregates only)
        $deviceBreakdown = Order::where('created_at', '>=', $since)
            ->selectRaw('COALESCE(device_type, "Unknown") as name, COUNT(*) as count')
            ->groupBy('name')->orderByDesc('count')->get();
        $browserBreakdown = Order::where('created_at', '>=', $since)
            ->selectRaw('COALESCE(browser, "Unknown") as name, COUNT(*) as count')
            ->groupBy('name')->orderByDesc('count')->limit(8)->get();
        $sourceBreakdown = Order::where('created_at', '>=', $since)
            ->selectRaw('COALESCE(NULLIF(utm_source, ""), order_source, "Direct") as name, COUNT(*) as count')
            ->groupBy('name')->orderByDesc('count')->limit(10)->get();
        $campaignBreakdown = Order::where('created_at', '>=', $since)
            ->whereNotNull('utm_campaign')
            ->selectRaw('utm_campaign as name, COUNT(*) as count, SUM(total_price) as revenue')
            ->groupBy('name')->orderByDesc('count')->limit(10)->get();
        $shippingBreakdown = Order::where('created_at', '>=', $since)
            ->selectRaw('COALESCE(shipping_method_name, "Standard") as name, COUNT(*) as count')
            ->groupBy('name')->orderByDesc('count')->get();

        // Attributed vs unattributed conversions
        $attributed = Order::where('created_at', '>=', $since)
            ->where(function ($q) {
                $q->whereNotNull('utm_source')->orWhereNotNull('utm_campaign');
            })->count();
        $unattributed = max(0, $orderCount - $attributed);

        return view('admin.analytics.index', [
            'range' => $days,
            'revenue' => $revenue,
            'orderCount' => $orderCount,
            'customers' => $customers,
            'avgOrder' => $avgOrder,
            'statusBreakdown' => $statusBreakdown,
            'paymentBreakdown' => $paymentBreakdown,
            'revenueTrend' => $revenueTrend,
            'topProducts' => $topProducts,
            'guestOrders' => $guestOrders,
            'registeredOrders' => $registeredOrders,
            'paidOrders' => $paidOrders,
            'cancelledOrders' => $cancelledOrders,
            'deviceBreakdown' => $deviceBreakdown,
            'browserBreakdown' => $browserBreakdown,
            'sourceBreakdown' => $sourceBreakdown,
            'campaignBreakdown' => $campaignBreakdown,
            'shippingBreakdown' => $shippingBreakdown,
            'attributed' => $attributed,
            'unattributed' => $unattributed,
            'headerTitle' => 'Analytics',
        ]);
    }

    /* ── Marketing ───────────────────────────────────────────── */

    public function marketing()
    {
        $integrations = MarketingIntegration::orderByDesc('id')->get();

        return view('admin.marketing.index', [
            'integrations' => $integrations,
            'headerTitle' => 'Marketing',
        ]);
    }

    public function storeMarketing(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'pixel_id' => 'nullable|string|max:255',
            'script_code' => 'nullable|string',
            'access_token' => 'nullable|string',
            'test_event_code' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
        ]);
        $data['status'] = $data['status'] ?? 'active';
        MarketingIntegration::create($data);

        return back()->with('success', 'Marketing integration saved.');
    }

    public function updateMarketing(Request $request, int $id)
    {
        $integration = MarketingIntegration::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'pixel_id' => 'nullable|string|max:255',
            'script_code' => 'nullable|string',
            'access_token' => 'nullable|string',
            'test_event_code' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:50',
        ]);
        $data['status'] = $data['status'] ?? 'active';
        $integration->update($data);

        return back()->with('success', 'Marketing integration updated.');
    }

    public function destroyMarketing(int $id)
    {
        MarketingIntegration::findOrFail($id)->delete();

        return back()->with('success', 'Integration deleted.');
    }

    /* ── Homepage Builder ────────────────────────────────────── */

    public function homepage()
    {
        $config = HomepageService::getConfig();
        $subscribers = HomepageService::getSubscribers();
        $categories = Category::where('status', 'active')->orderBy('name')->get(['id', 'name', 'slug']);

        return view('admin.homepage.index', [
            'config' => $config,
            'subscribers' => $subscribers,
            'categories' => $categories,
            'headerTitle' => 'Homepage Builder',
        ]);
    }

    public function saveHomepage(Request $request)
    {
        $request->validate([
            'config_json' => 'required|string',
        ]);

        $decoded = json_decode($request->input('config_json'), true);
        if (! is_array($decoded)) {
            return back()->with('error', 'Invalid homepage configuration.');
        }

        HomepageService::saveConfig($decoded);

        return back()->with('success', 'Homepage published — the storefront has been updated.');
    }

    public function resetHomepage(Request $request)
    {
        $config = HomepageService::resetConfig();

        if ($request->expectsJson()) {
            return response()->json($config);
        }

        return back()->with('success', 'Default layout restored — press Publish Changes to apply.');
    }

    /* ── Policies ────────────────────────────────────────────── */

    public function policies()
    {
        $policies = PolicyPage::orderBy('title')->get();

        return view('admin.policies.index', [
            'policies' => $policies,
            'headerTitle' => 'Policies & Messages',
        ]);
    }

    public function storePolicy(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|string|max:100|unique:policy_pages,slug',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'status' => 'nullable|string|max:50',
        ]);
        $data['status'] = $data['status'] ?? 'published';
        $data['last_updated'] = time();
        $data['updated_by'] = Auth::id();
        PolicyPage::create($data);

        return back()->with('success', 'Policy page created.');
    }

    public function updatePolicy(Request $request, int $id)
    {
        $policy = PolicyPage::findOrFail($id);
        $data = $request->validate([
            'slug' => 'required|string|max:100|unique:policy_pages,slug,'.$id,
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'status' => 'nullable|string|max:50',
        ]);
        $data['last_updated'] = time();
        $data['updated_by'] = Auth::id();
        $policy->update($data);

        return back()->with('success', 'Policy page updated.');
    }

    public function destroyPolicy(int $id)
    {
        PolicyPage::findOrFail($id)->delete();

        return back()->with('success', 'Policy page deleted.');
    }

    /* ── Team Members ────────────────────────────────────────── */

    /* ── Team Members ────────────────────────────────────────── */

    public function members(Request $request)
    {
        $search = $request->query('search');
        $roleFilter = $request->query('role');
        $statusFilter = $request->query('status');

        $query = User::where(function ($q) {
            $q->whereIn('role', ['admin', 'manager', 'editor', 'staff', 'super_admin'])
                ->orWhereNotNull('custom_role');
        });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $members = $query->withCount([
            'loginHistories as total_successful_logins' => function ($q) {
                $q->where('status', 'success');
            },
        ])->orderByDesc('id')->paginate(15)->withQueryString();

        $auditLogs = AdminAuditLog::where(function ($q) {
            $q->where('target_type', 'member')
                ->orWhere('action', 'like', 'member.%')
                ->orWhere('action', 'like', 'login.%');
        })->orWhere(function ($q) {
            $q->whereNull('target_type');
        })->orderByDesc('id')->limit(50)->get();

        return view('admin.members.index', [
            'members' => $members,
            'auditLogs' => $auditLogs,
            'headerTitle' => 'Team Members',
            'search' => $search,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
            'permissionMatrix' => RbacService::getPermissionMatrix(),
            'rolePresets' => RbacService::getRolePresets(),
            'sidebarSections' => AdminNav::rawSections(),
        ]);
    }

    public function showMember(Request $request, int $id)
    {
        $member = User::where(function ($q) {
            $q->whereIn('role', ['admin', 'manager', 'editor', 'staff', 'super_admin'])
                ->orWhereNotNull('custom_role');
        })->findOrFail($id);

        $statusFilter = $request->query('status');
        $historyQuery = $member->loginHistories()->orderByDesc('login_at');

        if ($statusFilter && in_array($statusFilter, ['success', 'failure'], true)) {
            $historyQuery->where('status', $statusFilter);
        }

        $loginHistories = $historyQuery->paginate(15)->withQueryString();

        $totalLogins = $member->loginHistories()->count();
        $successfulLogins = $member->loginHistories()->where('status', 'success')->count();
        $failedLogins = $member->loginHistories()->where('status', 'failure')->count();
        $lastLogin = $member->loginHistories()->where('status', 'success')->latest('login_at')->first();

        $auditLogs = AdminAuditLog::where(function ($q) use ($member) {
            $q->where('target_id', (string) $member->id)
                ->orWhere('actor_id', $member->id);
        })->orderByDesc('id')->limit(30)->get();

        return view('admin.members.show', [
            'member' => $member,
            'loginHistories' => $loginHistories,
            'totalLogins' => $totalLogins,
            'successfulLogins' => $successfulLogins,
            'failedLogins' => $failedLogins,
            'lastLogin' => $lastLogin,
            'auditLogs' => $auditLogs,
            'statusFilter' => $statusFilter,
            'headerTitle' => "Team Member: {$member->name}",
        ]);
    }

    public function storeMember(Request $request)
    {
        $currentUser = Auth::user();
        $isSuperAdmin = RbacService::isSuperAdmin($currentUser);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|in:admin,manager,editor,staff',
            'status' => 'nullable|in:active,inactive',
            'permission_mode' => 'nullable|in:role,custom',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'sidebar_access' => 'nullable|array',
            'sidebar_access.*' => 'string',
        ]);

        // Privilege escalation guard: Only Super Admin can assign the admin role
        if ($data['role'] === 'admin' && ! $isSuperAdmin) {
            return back()->with('error', 'Only Super Administrators can create Admin accounts.')->withInput();
        }

        $permissionMode = $data['permission_mode'] ?? 'role';
        $permissions = null;
        $sidebarAccess = null;
        $customRole = null;

        if ($permissionMode === 'custom') {
            $permissions = array_values(array_unique(array_filter((array) ($request->input('permissions') ?? []))));
            $sidebarAccess = array_values(array_unique(array_filter((array) ($request->input('sidebar_access') ?? []))));
            $customRole = 'CUSTOM';
        }

        $initialPassword = Str::random(32);

        $member = DB::transaction(function () use ($data, $initialPassword, $permissionMode, $permissions, $sidebarAccess, $customRole) {
            $user = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password' => Hash::make($initialPassword),
                'role' => $data['role'],
                'custom_role' => $customRole,
                'permission_mode' => $permissionMode,
                'permissions_json' => $permissions,
                'sidebar_access_json' => $sidebarAccess,
                'status' => $data['status'] ?? 'active',
                'must_change_password' => true,
            ]);

            if ($permissionMode === 'custom' && ! empty($permissions)) {
                foreach ($permissions as $permCode) {
                    UserPermission::create([
                        'user_id' => $user->id,
                        'permission_code' => $permCode,
                        'granted' => true,
                    ]);
                }
            }

            return $user;
        });

        RbacService::invalidateUserPermissionCache($member->id);

        $inviteResult = MemberInvitationService::createInvitation($member, $currentUser);

        AuditService::log([
            'actorId' => $currentUser?->id,
            'actorName' => $currentUser?->name ?? 'System',
            'actorEmail' => $currentUser?->email,
            'action' => 'member.created',
            'targetType' => 'member',
            'targetId' => (string) $member->id,
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'status' => 'success',
            'details' => [
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->role,
                'permission_mode' => $permissionMode,
                'permissions_count' => count($permissions ?? []),
                'sidebar_access_count' => count($sidebarAccess ?? []),
                'invitation_sent' => $inviteResult['success'],
            ],
        ]);

        if ($inviteResult['success']) {
            return back()->with('success', "Team member created and invitation queued to {$member->email}.");
        }

        return back()->with('warning', "Team member created, but the invitation email could not be dispatched: {$inviteResult['message']}. You can resend the invitation from the member table.");
    }

    public function resendInvitation(Request $request, int $id)
    {
        $member = User::findOrFail($id);

        if (empty($member->email)) {
            return back()->with('error', 'Cannot send invitation: member has no email address.');
        }

        $inviteResult = MemberInvitationService::createInvitation($member, Auth::user());

        if ($inviteResult['success']) {
            return back()->with('success', "Invitation email queued to {$member->email}.");
        }

        return back()->with('error', "Failed to dispatch invitation email: {$inviteResult['message']}");
    }

    public function updateMember(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();
        $isSuperAdmin = RbacService::isSuperAdmin($currentUser);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,'.$id,
            'email' => 'required|email|max:255|unique:users,email,'.$id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,manager,editor,staff',
            'status' => 'nullable|in:active,inactive',
            'permission_mode' => 'nullable|in:role,custom',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'sidebar_access' => 'nullable|array',
            'sidebar_access.*' => 'string',
        ]);

        // Prevent normal admin from escalating themselves or changing their own role/permissions
        if ($user->id === $currentUser?->id) {
            if ($data['role'] !== $user->role && ! $isSuperAdmin) {
                return back()->with('error', 'You cannot alter your own role.');
            }
        }

        // Only super admin can grant admin role
        if ($data['role'] === 'admin' && $user->role !== 'admin' && ! $isSuperAdmin) {
            return back()->with('error', 'Only Super Administrators can elevate members to the Admin role.');
        }

        $isTargetSuperAdmin = RbacService::isSuperAdmin($user);
        $newStatus = $data['status'] ?? $user->status;
        $newRole = $data['role'];
        $newPermissionMode = $data['permission_mode'] ?? ($user->permission_mode ?? 'role');

        // Protect final active Super Administrator: Cannot demote or deactivate the last Super Admin
        if ($isTargetSuperAdmin) {
            $isBeingDeactivated = ($newStatus === 'inactive' && $user->status === 'active');
            $isBeingDemoted = ($newRole !== 'admin');
            $isBeingRestricted = ($newPermissionMode === 'custom');

            if ($isBeingDeactivated || $isBeingDemoted || $isBeingRestricted) {
                if (RbacService::countActiveSuperAdmins() <= 1) {
                    return back()->with('error', 'Cannot demote, deactivate, or restrict permissions of the final active Super Administrator.');
                }
            }
        }

        $oldRole = $user->role;
        $oldCustomRole = $user->custom_role;
        $oldStatus = $user->status;
        $oldPermMode = $user->permission_mode ?? 'role';
        $oldPerms = is_array($user->permissions_json) ? $user->permissions_json : (json_decode($user->permissions_json ?? '', true) ?: []);
        $oldSidebar = is_array($user->sidebar_access_json) ? $user->sidebar_access_json : (json_decode($user->sidebar_access_json ?? '', true) ?: []);

        $newPerms = [];
        $newSidebar = [];
        $customRole = $user->custom_role;

        if ($newPermissionMode === 'custom') {
            $newPerms = array_values(array_unique(array_filter((array) ($request->input('permissions') ?? []))));
            $newSidebar = array_values(array_unique(array_filter((array) ($request->input('sidebar_access') ?? []))));
            $customRole = 'CUSTOM';
        } else {
            $customRole = null;
        }

        $passwordUpdated = false;

        DB::transaction(function () use ($user, $data, $newRole, $newStatus, $newPermissionMode, $customRole, $newPerms, $newSidebar, &$passwordUpdated) {
            $user->name = $data['name'];
            $user->phone = $data['phone'];
            $user->email = $data['email'];
            $user->role = $newRole;
            $user->status = $newStatus;
            $user->permission_mode = $newPermissionMode;
            $user->custom_role = $customRole;
            $user->permissions_json = $newPermissionMode === 'custom' ? $newPerms : null;
            $user->sidebar_access_json = $newPermissionMode === 'custom' ? $newSidebar : null;

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
                $user->must_change_password = false;
                $passwordUpdated = true;
            }

            $user->save();

            // Sync user_permissions table
            UserPermission::where('user_id', $user->id)->delete();
            if ($newPermissionMode === 'custom' && ! empty($newPerms)) {
                foreach ($newPerms as $permCode) {
                    UserPermission::create([
                        'user_id' => $user->id,
                        'permission_code' => $permCode,
                        'granted' => true,
                    ]);
                }
            }
        });

        RbacService::invalidateUserPermissionCache($user->id);

        if ($oldRole !== $user->role) {
            AuditService::log([
                'actorId' => $currentUser?->id,
                'actorName' => $currentUser?->name ?? 'System',
                'actorEmail' => $currentUser?->email,
                'action' => 'member.role_changed',
                'targetType' => 'member',
                'targetId' => (string) $user->id,
                'ipAddress' => $request->ip(),
                'userAgent' => $request->userAgent(),
                'status' => 'success',
                'details' => ['old_role' => $oldRole, 'new_role' => $user->role],
            ]);
        }

        if ($oldPermMode !== $newPermissionMode || $oldPerms != $newPerms || $oldSidebar != $newSidebar) {
            $addedPerms = array_values(array_diff($newPerms, $oldPerms));
            $removedPerms = array_values(array_diff($oldPerms, $newPerms));

            AuditService::log([
                'actorId' => $currentUser?->id,
                'actorName' => $currentUser?->name ?? 'System',
                'actorEmail' => $currentUser?->email,
                'action' => 'member.permissions_updated',
                'targetType' => 'member',
                'targetId' => (string) $user->id,
                'ipAddress' => $request->ip(),
                'userAgent' => $request->userAgent(),
                'status' => 'success',
                'details' => [
                    'previous_mode' => $oldPermMode,
                    'new_mode' => $newPermissionMode,
                    'added_permissions' => $addedPerms,
                    'removed_permissions' => $removedPerms,
                    'sidebar_access' => $newSidebar,
                ],
            ]);
        }

        if ($oldStatus !== $user->status) {
            $action = $user->status === 'active' ? 'member.activated' : 'member.deactivated';
            AuditService::log([
                'actorId' => $currentUser?->id,
                'actorName' => $currentUser?->name ?? 'System',
                'actorEmail' => $currentUser?->email,
                'action' => $action,
                'targetType' => 'member',
                'targetId' => (string) $user->id,
                'ipAddress' => $request->ip(),
                'userAgent' => $request->userAgent(),
                'status' => 'success',
                'details' => ['status' => $user->status],
            ]);
        }

        if ($passwordUpdated) {
            AuditService::log([
                'actorId' => $currentUser?->id,
                'actorName' => $currentUser?->name ?? 'System',
                'actorEmail' => $currentUser?->email,
                'action' => 'member.password_changed',
                'targetType' => 'member',
                'targetId' => (string) $user->id,
                'ipAddress' => $request->ip(),
                'userAgent' => $request->userAgent(),
                'status' => 'success',
                'details' => ['reason' => 'Direct admin password update'],
            ]);
        }

        AuditService::log([
            'actorId' => $currentUser?->id,
            'actorName' => $currentUser?->name ?? 'System',
            'actorEmail' => $currentUser?->email,
            'action' => 'member.updated',
            'targetType' => 'member',
            'targetId' => (string) $user->id,
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'status' => 'success',
            'details' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]);

        return back()->with('success', 'Team member updated successfully.');
    }

    public function destroyMember(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();
        $isSuperAdmin = RbacService::isSuperAdmin($currentUser);

        if ($user->id === $currentUser?->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Protect final active Super Admin
        if (RbacService::isSuperAdmin($user)) {
            if (RbacService::countActiveSuperAdmins() <= 1) {
                return back()->with('error', 'Cannot delete the final active Super Administrator.');
            }
            if (! $isSuperAdmin) {
                return back()->with('error', 'You are not authorized to delete administrator accounts.');
            }
        } elseif (! in_array($currentUser?->role, ['super_admin', 'admin'], true) && ! $isSuperAdmin) {
            return back()->with('error', 'You are not authorized to remove team members.');
        }

        AuditService::log([
            'actorId' => $currentUser?->id,
            'actorName' => $currentUser?->name ?? 'System',
            'actorEmail' => $currentUser?->email,
            'action' => 'member.removed',
            'targetType' => 'member',
            'targetId' => (string) $user->id,
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'status' => 'success',
            'details' => ['name' => $user->name, 'email' => $user->email, 'role' => $user->role],
        ]);

        UserPermission::where('user_id', $user->id)->delete();
        RbacService::invalidateUserPermissionCache($user->id);

        $user->delete();

        return redirect()->route('admin.members.index')->with('success', 'Team member removed.');
    }
}
