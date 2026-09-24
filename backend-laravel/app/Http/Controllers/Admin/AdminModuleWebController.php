<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\MarketingIntegration;
use App\Models\Category;
use App\Models\Order;
use App\Models\PolicyPage;
use App\Models\Product;
use App\Models\User;
use App\Services\HomepageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
        $product = Product::findOrFail($id);
        $delta = (int) $request->input('delta', 0);
        $product->stock = max(0, (int) $product->stock + $delta);
        if ($product->stock <= 0) {
            $product->stock_status = 'out_of_stock';
        } elseif ($product->stock_status === 'out_of_stock') {
            $product->stock_status = 'in_stock';
        }
        $product->save();

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

        $monthly = Expense::selectRaw("strftime('%Y-%m', expense_date) as month, SUM(amount) as total, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $byCategory = Expense::query()
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->selectRaw('COALESCE(expense_categories.name, "Uncategorized") as name, SUM(expenses.amount) as total')
            ->groupBy('name')
            ->orderByDesc('total')
            ->get();

        $byMember = Expense::selectRaw('COALESCE(member_name, "Unassigned") as name, SUM(amount) as total')
            ->groupBy('name')
            ->orderByDesc('total')
            ->get();

        $revenue = Order::whereNotIn('status', ['cancelled', 'refunded'])->sum('total_price');
        $expenseTotal = Expense::where('status', '!=', 'rejected')->sum('amount');

        $profitMonthly = Order::selectRaw("strftime('%Y-%m', created_at) as month, SUM(total_price) as revenue")
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
            ->selectRaw("date(created_at) as day, SUM(total_price) as total")
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
        if (!is_array($decoded)) {
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

    public function members()
    {
        $members = User::whereIn('role', ['admin', 'manager', 'editor', 'staff', 'super_admin'])
            ->orWhereNotNull('custom_role')
            ->orderByDesc('id')
            ->get();

        $auditLogs = AdminAuditLog::orderByDesc('id')->limit(50)->get();

        return view('admin.members.index', [
            'members' => $members,
            'auditLogs' => $auditLogs,
            'headerTitle' => 'Team Members',
        ]);
    }

    public function storeMember(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'nullable|email|max:255',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,manager,editor,staff',
            'status' => 'nullable|in:active,inactive',
        ]);

        User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
        ]);

        return back()->with('success', 'Team member created.');
    }

    public function updateMember(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,'.$id,
            'email' => 'nullable|email|max:255',
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,manager,editor,staff',
            'status' => 'nullable|in:active,inactive',
        ]);

        $user->name = $data['name'];
        $user->phone = $data['phone'];
        $user->email = $data['email'] ?? null;
        $user->role = $data['role'];
        $user->status = $data['status'] ?? $user->status;
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('success', 'Team member updated.');
    }

    public function destroyMember(int $id)
    {
        $user = User::findOrFail($id);
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();

        return back()->with('success', 'Team member removed.');
    }
}
