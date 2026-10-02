<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAdminPermission;
use App\Models\ActivityLog;
use App\Models\CustomerNote;
use App\Models\EmailLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\ActivityLoggerService;
use App\Services\CustomerReportService;
use App\Services\EmailDispatcherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCustomerWebController extends Controller
{
    /**
     * Check if current user has permission to view customers.
     */
    protected function authorizeView(): void
    {
        if (! EnsureAdminPermission::allows(auth()->user(), ['customers.view'])) {
            abort(403, 'Unauthorized access to customer directory.');
        }
    }

    /**
     * Check if current user has permission to manage/update customers.
     */
    protected function authorizeManage(): void
    {
        if (! EnsureAdminPermission::allows(auth()->user(), ['customers.update', 'customers.manage'])) {
            abort(403, 'Unauthorized action on customer record.');
        }
    }

    /**
     * Base query for genuine customers (excluding administrative staff roles).
     */
    protected function customerBaseQuery()
    {
        return User::where(function ($q) {
            $q->whereIn('role', ['user', 'customer'])
                ->orWhere(function ($sub) {
                    $sub->whereNotIn('role', ['admin', 'super_admin', 'manager', 'editor', 'staff'])
                        ->whereNull('custom_role');
                });
        });
    }

    /**
     * 1. Upgraded Customer List with Advanced Filters, Sorting, and Aggregates.
     */
    public function index(Request $request)
    {
        $this->authorizeView();

        $query = $this->customerBaseQuery()
            ->withCount('orders')
            ->withCount(['orders as valid_orders_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled', 'returned']);
            }])
            ->withSum(['orders as total_spent' => function ($q) {
                $q->whereNotIn('status', ['cancelled', 'returned']);
            }], 'total_price')
            ->withMax('orders as last_order_at', 'created_at');

        // Search filter
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('id', $s);
            });
        }

        // Account status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Registration date range filter
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from').' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to').' 23:59:59');
        }

        // Order filters
        if ($request->input('order_filter') === 'no_orders') {
            $query->whereDoesntHave('orders');
        } elseif ($request->input('order_filter') === 'with_orders') {
            $query->whereHas('orders');
        } elseif ($request->input('order_filter') === 'recent_orders') {
            $query->whereHas('orders', function ($q) {
                $q->where('created_at', '>=', now()->subDays(30));
            });
        }

        if ($request->filled('min_orders')) {
            $query->has('orders', '>=', (int) $request->input('min_orders'));
        }
        if ($request->filled('max_orders')) {
            $query->has('orders', '<=', (int) $request->input('max_orders'));
        }

        // Spending range filter
        if ($request->filled('min_spend')) {
            $query->having('total_spent', '>=', (float) $request->input('min_spend'));
        }
        if ($request->filled('max_spend')) {
            $query->having('total_spent', '<=', (float) $request->input('max_spend'));
        }

        // Sorting
        $sort = $request->input('sort', 'created_at_desc');
        switch ($sort) {
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'orders_desc':
                $query->orderByDesc('orders_count');
                break;
            case 'orders_asc':
                $query->orderBy('orders_count', 'asc');
                break;
            case 'spent_desc':
                $query->orderByDesc('total_spent');
                break;
            case 'spent_asc':
                $query->orderBy('total_spent', 'asc');
                break;
            case 'last_order_desc':
                $query->orderByDesc('last_order_at');
                break;
            default:
                $query->orderByDesc('created_at');
                break;
        }

        $customers = $query->paginate(20)->withQueryString();

        // Calculate summary KPIs for the customer base
        $totalCustomersCount = $this->customerBaseQuery()->count();
        $activeCustomersCount = $this->customerBaseQuery()->where('status', 'active')->count();

        return view('admin.customers.index', compact(
            'customers',
            'totalCustomersCount',
            'activeCustomersCount'
        ));
    }

    /**
     * Export customer list as CSV or PDF.
     */
    public function exportList(Request $request)
    {
        $this->authorizeView();

        $query = $this->customerBaseQuery()
            ->withCount('orders')
            ->withCount(['orders as valid_orders_count' => function ($q) {
                $q->whereNotIn('status', ['cancelled', 'returned']);
            }])
            ->withSum(['orders as total_spent' => function ($q) {
                $q->whereNotIn('status', ['cancelled', 'returned']);
            }], 'total_price')
            ->withMax('orders as last_order_at', 'created_at');

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('id', $s);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from').' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to').' 23:59:59');
        }

        $customers = $query->orderByDesc('created_at')->limit(1000)->get();

        $format = $request->input('format', 'csv');
        if ($format === 'pdf') {
            return CustomerReportService::exportListPdf($customers, $request->only(['status', 'search', 'date_from', 'date_to']));
        }

        return CustomerReportService::exportListCsv($customers);
    }

    /**
     * 2. Customer 360° Comprehensive Profile & Dossier.
     */
    public function show(Request $request, $id)
    {
        $this->authorizeView();

        $customer = $this->customerBaseQuery()
            ->with([
                'addresses',
                'customerNotes.admin',
            ])
            ->findOrFail($id);

        $activeTab = $request->input('tab', 'overview');

        // All customer orders for accurate statistics
        $allOrders = Order::where('user_id', $customer->id)
            ->with(['items.product', 'statusHistory'])
            ->orderByDesc('created_at')
            ->get();

        $validOrders = $allOrders->whereNotIn('status', ['cancelled', 'returned']);
        $totalOrders = $allOrders->count();
        $validOrdersCount = $validOrders->count();
        $totalSpent = (float) $validOrders->sum('total_price');
        $aov = $validOrdersCount > 0 ? ($totalSpent / $validOrdersCount) : 0.0;
        $pendingOrdersCount = $allOrders->whereIn('status', ['pending', 'processing'])->count();
        $completedOrdersCount = $allOrders->whereIn('status', ['delivered', 'completed'])->count();
        $cancelledOrdersCount = $allOrders->whereIn('status', ['cancelled', 'returned'])->count();
        $lastOrder = $allOrders->first();
        $firstOrder = $allOrders->last();

        $metrics = [
            'totalOrders' => $totalOrders,
            'validOrdersCount' => $validOrdersCount,
            'totalSpent' => $totalSpent,
            'aov' => $aov,
            'pendingOrders' => $pendingOrdersCount,
            'completedOrders' => $completedOrdersCount,
            'cancelledOrders' => $cancelledOrdersCount,
            'lastOrderDate' => $lastOrder?->created_at,
            'firstOrderDate' => $firstOrder?->created_at,
        ];

        // 3. Paginated & Filterable Orders Tab
        $ordersQuery = Order::with('items.product')->where('user_id', $customer->id);
        if ($request->filled('order_search')) {
            $os = trim($request->input('order_search'));
            $ordersQuery->where(function ($q) use ($os) {
                $q->where('order_id', 'like', "%{$os}%")
                    ->orWhere('invoice_number', 'like', "%{$os}%");
            });
        }
        if ($request->filled('order_status')) {
            $ordersQuery->where('status', $request->input('order_status'));
        }
        if ($request->filled('order_payment_status')) {
            $ordersQuery->where('payment_status', $request->input('order_payment_status'));
        }
        if ($request->filled('order_date_from')) {
            $ordersQuery->where('created_at', '>=', $request->input('order_date_from').' 00:00:00');
        }
        if ($request->filled('order_date_to')) {
            $ordersQuery->where('created_at', '<=', $request->input('order_date_to').' 23:59:59');
        }
        $paginatedOrders = $ordersQuery->orderByDesc('created_at')->paginate(10, ['*'], 'orders_page')->withQueryString();

        // 4. Payments Tab (Derived cleanly from stored order payment attributes)
        $paymentsQuery = Order::where('user_id', $customer->id)
            ->where(function ($q) {
                $q->whereNotNull('payment_method')
                    ->orWhereNotNull('transaction_id')
                    ->orWhere('amount_sent', '>', 0)
                    ->orWhereNotNull('payment_status');
            });
        if ($request->filled('pay_status')) {
            $paymentsQuery->where('payment_status', $request->input('pay_status'));
        }
        if ($request->filled('pay_search')) {
            $ps = trim($request->input('pay_search'));
            $paymentsQuery->where(function ($q) use ($ps) {
                $q->where('transaction_id', 'like', "%{$ps}%")
                    ->orWhere('order_id', 'like', "%{$ps}%")
                    ->orWhere('sender_number', 'like', "%{$ps}%");
            });
        }
        if ($request->filled('pay_date_from')) {
            $paymentsQuery->where('created_at', '>=', $request->input('pay_date_from').' 00:00:00');
        }
        if ($request->filled('pay_date_to')) {
            $paymentsQuery->where('created_at', '<=', $request->input('pay_date_to').' 23:59:59');
        }
        $payments = $paymentsQuery->orderByDesc('created_at')->paginate(10, ['*'], 'payments_page')->withQueryString();

        // 5. Addresses & Historical Order Shipping Addresses
        $savedAddresses = $customer->addresses()->orderByDesc('is_default')->orderByDesc('created_at')->get();
        $historicalOrderAddresses = $allOrders
            ->filter(fn ($o) => ! empty($o->address))
            ->unique(fn ($o) => strtolower(trim($o->address.' '.$o->district.' '.$o->division)))
            ->take(5);

        // 6. Activity Timeline Tab
        $orderIds = $allOrders->pluck('id')->all();
        $activityLogs = ActivityLog::where(function ($q) use ($customer, $orderIds) {
            $q->where(function ($sub) use ($customer) {
                $sub->where('actor_type', 'customer')
                    ->where('actor_id', $customer->id);
            })->orWhere(function ($sub) use ($customer) {
                $sub->where('subject_type', 'User')
                    ->where('subject_id', (string) $customer->id);
            })->orWhere(function ($sub) use ($customer) {
                $sub->where('actor_id', $customer->id);
            });

            if (! empty($orderIds)) {
                $q->orWhere(function ($sub) use ($orderIds) {
                    $sub->where('module', 'orders')
                        ->whereIn('subject_id', array_map('strval', $orderIds));
                });
            }
        })
            ->orderByDesc('occurred_at')
            ->paginate(15, ['*'], 'activity_page')
            ->withQueryString();

        // 7. Customer Analytics
        $analytics = $this->computeCustomerAnalytics($customer, $allOrders, $validOrders);

        // 8. Communication History Tab
        $emailLogs = EmailLog::where(function ($q) use ($customer) {
            $q->where('user_id', $customer->id);
            if (! empty($customer->email)) {
                $q->orWhere('recipient_email', $customer->email);
            }
        })
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'emails_page')
            ->withQueryString();

        // 9. Internal Notes & Administrative Actions
        $notes = $customer->customerNotes()->with('admin')->orderByDesc('created_at')->get();
        $adminActions = ActivityLog::where('subject_type', 'User')
            ->where('subject_id', (string) $customer->id)
            ->where('actor_type', 'admin')
            ->orderByDesc('occurred_at')
            ->limit(10)
            ->get();

        return view('admin.customers.show', compact(
            'customer',
            'activeTab',
            'metrics',
            'paginatedOrders',
            'payments',
            'savedAddresses',
            'historicalOrderAddresses',
            'activityLogs',
            'analytics',
            'emailLogs',
            'notes',
            'adminActions'
        ));
    }

    /**
     * Compute analytics: Monthly trends, top products, top categories, frequency.
     */
    protected function computeCustomerAnalytics(User $customer, $allOrders, $validOrders): array
    {
        // 12 Months Trend
        $monthlyLabels = [];
        $monthlyOrdersData = [];
        $monthlySpendData = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $monthlyLabels[] = $month->format('M Y');

            $monthOrders = $allOrders->filter(function ($o) use ($key) {
                return $o->created_at && $o->created_at->format('Y-m') === $key;
            });
            $monthlyOrdersData[] = $monthOrders->count();

            $monthSpend = $monthOrders->whereNotIn('status', ['cancelled', 'returned'])->sum('total_price');
            $monthlySpendData[] = (float) $monthSpend;
        }

        // Most frequently purchased products
        $validOrderIds = $validOrders->pluck('id')->all();
        $topProducts = collect();
        $topCategories = collect();

        if (! empty($validOrderIds)) {
            $topProducts = OrderItem::whereIn('order_id', $validOrderIds)
                ->select(
                    'product_id',
                    'product_title',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('SUM(quantity * price) as total_spent')
                )
                ->with('product.category')
                ->groupBy('product_id', 'product_title')
                ->orderByDesc('total_quantity')
                ->limit(6)
                ->get();

            // Extract Category aggregations
            $categoryCounts = [];
            foreach ($topProducts as $item) {
                $catName = $item->product?->category?->name ?? 'General Goods';
                $categoryCounts[$catName] = ($categoryCounts[$catName] ?? 0) + (int) $item->total_quantity;
            }
            arsort($categoryCounts);
            $topCategories = collect($categoryCounts);
        }

        // Purchase Frequency
        $orderFrequencyText = 'Single order to date';
        if ($validOrders->count() >= 2) {
            $firstDate = $validOrders->last()->created_at;
            $lastDate = $validOrders->first()->created_at;
            if ($firstDate && $lastDate) {
                $days = max(1, $firstDate->diffInDays($lastDate));
                $avgDays = round($days / ($validOrders->count() - 1), 1);
                $orderFrequencyText = "Approx. every {$avgDays} days";
            }
        } elseif ($validOrders->count() === 0) {
            $orderFrequencyText = 'No completed purchases';
        }

        return [
            'monthlyLabels' => $monthlyLabels,
            'monthlyOrdersData' => $monthlyOrdersData,
            'monthlySpendData' => $monthlySpendData,
            'topProducts' => $topProducts,
            'topCategories' => $topCategories,
            'orderFrequency' => $orderFrequencyText,
            'customerLifetimeValue' => (float) $validOrders->sum('total_price'),
        ];
    }

    /**
     * Update Customer profile info (Name, Phone, Email, Shipping Area, Address).
     */
    public function update(Request $request, $id)
    {
        $this->authorizeManage();

        $customer = $this->customerBaseQuery()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone,'.$customer->id,
            'email' => 'nullable|email|max:255|unique:users,email,'.$customer->id,
            'shipping_area' => 'nullable|string|max:100',
            'shipping_address' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
        ]);

        $oldValues = $customer->only(['name', 'phone', 'email', 'shipping_area', 'shipping_address', 'status']);
        $customer->update($validated);

        ActivityLoggerService::log([
            'event' => 'customer.updated',
            'module' => 'customers',
            'description' => "Customer #{$customer->id} ({$customer->name}) profile details updated by ".auth()->user()->name,
            'subjectType' => 'User',
            'subjectId' => (string) $customer->id,
            'actor' => auth()->user(),
            'oldValues' => $oldValues,
            'newValues' => $validated,
            'status' => 'success',
        ]);

        return back()->with('success', 'Customer profile updated successfully.');
    }

    /**
     * Toggle Customer status (Active / Inactive).
     */
    public function toggleStatus($id)
    {
        $this->authorizeManage();

        $customer = $this->customerBaseQuery()->findOrFail($id);
        $newStatus = ($customer->status === 'active') ? 'inactive' : 'active';
        $customer->status = $newStatus;
        $customer->save();

        ActivityLoggerService::log([
            'event' => $newStatus === 'active' ? 'customer.activated' : 'customer.deactivated',
            'module' => 'customers',
            'description' => "Customer account #{$customer->id} ({$customer->name}) was {$newStatus} by ".auth()->user()->name,
            'subjectType' => 'User',
            'subjectId' => (string) $customer->id,
            'actor' => auth()->user(),
            'status' => 'success',
        ]);

        return back()->with('success', "Customer status successfully changed to {$newStatus}.");
    }

    /**
     * Store internal note for the customer.
     */
    public function storeNote(Request $request, $id)
    {
        $this->authorizeManage();

        $customer = $this->customerBaseQuery()->findOrFail($id);

        $validated = $request->validate([
            'note' => 'required|string|min:2|max:2000',
        ]);

        $note = CustomerNote::create([
            'customer_id' => $customer->id,
            'admin_id' => auth()->id(),
            'note' => $validated['note'],
        ]);

        ActivityLoggerService::log([
            'event' => 'customer.note_added',
            'module' => 'customers',
            'description' => 'Admin '.auth()->user()->name." added an internal note to customer #{$customer->id}",
            'subjectType' => 'User',
            'subjectId' => (string) $customer->id,
            'actor' => auth()->user(),
            'status' => 'success',
        ]);

        return back()->with('success', 'Internal note added successfully.')->with('tab', 'notes');
    }

    /**
     * Delete internal note.
     */
    public function deleteNote($id, $noteId)
    {
        $this->authorizeManage();

        $note = CustomerNote::where('customer_id', $id)->findOrFail($noteId);
        $note->delete();

        ActivityLoggerService::log([
            'event' => 'customer.note_deleted',
            'module' => 'customers',
            'description' => 'Admin '.auth()->user()->name." deleted note #{$noteId} from customer #{$id}",
            'subjectType' => 'User',
            'subjectId' => (string) $id,
            'actor' => auth()->user(),
            'status' => 'success',
        ]);

        return back()->with('success', 'Internal note removed.')->with('tab', 'notes');
    }

    /**
     * Add new address to Customer Address Book.
     */
    public function storeAddress(Request $request, $id)
    {
        $this->authorizeManage();

        $customer = $this->customerBaseQuery()->findOrFail($id);

        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'alternative_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'division' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'upazila' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:150',
            'shipping_area' => 'required|string|max:100',
            'address' => 'required|string|max:1000',
            'apartment' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            UserAddress::where('user_id', $customer->id)->update(['is_default' => false]);
        }

        $validated['user_id'] = $customer->id;
        $validated['is_default'] = $isDefault;

        UserAddress::create($validated);

        ActivityLoggerService::log([
            'event' => 'customer.address_added',
            'module' => 'customers',
            'description' => "New address added to customer #{$customer->id} by ".auth()->user()->name,
            'subjectType' => 'User',
            'subjectId' => (string) $customer->id,
            'actor' => auth()->user(),
            'status' => 'success',
        ]);

        return back()->with('success', 'Address added to customer address book.')->with('tab', 'addresses');
    }

    /**
     * Update an address in Customer Address Book.
     */
    public function updateAddress(Request $request, $id, $addressId)
    {
        $this->authorizeManage();

        $address = UserAddress::where('user_id', $id)->findOrFail($addressId);

        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'alternative_phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'division' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'upazila' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:150',
            'shipping_area' => 'required|string|max:100',
            'address' => 'required|string|max:1000',
            'apartment' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = $request->boolean('is_default');
        if ($isDefault) {
            UserAddress::where('user_id', $id)->where('id', '!=', $addressId)->update(['is_default' => false]);
        }
        $validated['is_default'] = $isDefault;

        $address->update($validated);

        ActivityLoggerService::log([
            'event' => 'customer.address_updated',
            'module' => 'customers',
            'description' => "Address #{$addressId} updated for customer #{$id} by ".auth()->user()->name,
            'subjectType' => 'User',
            'subjectId' => (string) $id,
            'actor' => auth()->user(),
            'status' => 'success',
        ]);

        return back()->with('success', 'Customer address updated successfully.')->with('tab', 'addresses');
    }

    /**
     * Delete an address from Customer Address Book.
     */
    public function deleteAddress($id, $addressId)
    {
        $this->authorizeManage();

        $address = UserAddress::where('user_id', $id)->findOrFail($addressId);
        $address->delete();

        ActivityLoggerService::log([
            'event' => 'customer.address_deleted',
            'module' => 'customers',
            'description' => "Address #{$addressId} removed from customer #{$id} by ".auth()->user()->name,
            'subjectType' => 'User',
            'subjectId' => (string) $id,
            'actor' => auth()->user(),
            'status' => 'success',
        ]);

        return back()->with('success', 'Address deleted successfully.')->with('tab', 'addresses');
    }

    /**
     * Set default address for customer.
     */
    public function setDefaultAddress($id, $addressId)
    {
        $this->authorizeManage();

        UserAddress::where('user_id', $id)->update(['is_default' => false]);
        UserAddress::where('user_id', $id)->where('id', $addressId)->update(['is_default' => true]);

        return back()->with('success', 'Default address updated.')->with('tab', 'addresses');
    }

    /**
     * Dispatch an individual direct email to the customer.
     */
    public function sendEmail(Request $request, $id)
    {
        $this->authorizeManage();

        $customer = $this->customerBaseQuery()->findOrFail($id);

        if (empty($customer->email)) {
            return back()->with('error', 'Cannot send email: this customer does not have an email address configured.')->with('tab', 'communication');
        }

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'email_type' => 'required|in:transactional,notification,order,campaign',
            'message' => 'required|string|min:5|max:10000',
        ]);

        $htmlMessage = '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 20px;">'
            .'<div style="background-color: #f8fafc; border-left: 4px solid #0f4d2c; padding: 14px 18px; margin-bottom: 20px;">'
            .'<h2 style="margin: 0; color: #0f4d2c; font-size: 18px;">'.e($validated['subject']).'</h2>'
            .'<p style="margin: 4px 0 0 0; font-size: 13px; color: #64748b;">Dear '.e($customer->name).',</p>'
            .'</div>'
            .'<div style="font-size: 14px; color: #334155; line-height: 1.7;">'
            .nl2br(e($validated['message']))
            .'</div>'
            .'<hr style="margin: 30px 0 15px 0; border: none; border-top: 1px solid #e2e8f0;">'
            .'<div style="font-size: 12px; color: #94a3b8; text-align: center;">'
            .'Mama Bazar Customer Care & Support'
            .'</div>'
            .'</div>';

        $result = EmailDispatcherService::send(
            to: $customer->email,
            recipientName: $customer->name,
            subject: $validated['subject'],
            htmlContent: $htmlMessage,
            plainContent: $validated['message'],
            emailType: $validated['email_type'],
            options: [
                'user_id' => $customer->id,
                'from_name' => config('mail.from.name', 'Mama Bazar'),
            ]
        );

        if (! empty($result['success'])) {
            ActivityLoggerService::log([
                'event' => 'customer.email_dispatched',
                'module' => 'customers',
                'description' => 'Admin '.auth()->user()->name." sent email '{$validated['subject']}' to {$customer->email}",
                'subjectType' => 'User',
                'subjectId' => (string) $customer->id,
                'actor' => auth()->user(),
                'status' => 'success',
            ]);

            return back()->with('success', "Email successfully dispatched to {$customer->email}.")->with('tab', 'communication');
        }

        return back()->with('error', 'Failed to dispatch email: '.($result['error'] ?? 'Unknown SMTP error'))->with('tab', 'communication')->withInput();
    }

    /**
     * Export individual Customer 360 profile as PDF or CSV.
     */
    public function export(Request $request, $id)
    {
        $this->authorizeView();

        $customer = $this->customerBaseQuery()
            ->with(['addresses', 'customerNotes.admin'])
            ->findOrFail($id);

        $orders = Order::where('user_id', $customer->id)->with('items')->orderByDesc('created_at')->get();
        $validOrders = $orders->whereNotIn('status', ['cancelled', 'returned']);
        $totalOrders = $orders->count();
        $validOrdersCount = $validOrders->count();
        $totalSpent = (float) $validOrders->sum('total_price');
        $aov = $validOrdersCount > 0 ? ($totalSpent / $validOrdersCount) : 0.0;

        $metrics = [
            'totalOrders' => $totalOrders,
            'totalSpent' => $totalSpent,
            'aov' => $aov,
            'pendingOrders' => $orders->whereIn('status', ['pending', 'processing'])->count(),
            'completedOrders' => $orders->whereIn('status', ['delivered', 'completed'])->count(),
            'cancelledOrders' => $orders->whereIn('status', ['cancelled', 'returned'])->count(),
        ];

        $format = $request->input('format', 'pdf');
        if ($format === 'csv') {
            return CustomerReportService::exportCustomerCsv($customer, $metrics, $orders);
        }

        $payments = Order::where('user_id', $customer->id)
            ->where(function ($q) {
                $q->whereNotNull('payment_method')->orWhereNotNull('transaction_id')->orWhere('amount_sent', '>', 0);
            })
            ->orderByDesc('created_at')
            ->get();

        return CustomerReportService::exportCustomerPdf(
            $customer,
            $metrics,
            $orders,
            $payments,
            $customer->addresses,
            $customer->customerNotes
        );
    }
}
