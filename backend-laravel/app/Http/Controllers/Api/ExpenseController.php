<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_LIMIT = 20;

    private function toNumber($v): float
    {
        return is_numeric($v) ? (float) $v : 0.0;
    }

    private function normalizeDateInput(string $input): string
    {
        $trimmed = str_replace(' ', 'T', trim($input));
        return strlen($trimmed) <= 10 ? "{$trimmed} 00:00:00" : str_replace('T', ' ', $trimmed);
    }

    private function buildWhere(Request $request, $query)
    {
        $query->where('expenses.status', '!=', 'rejected');

        if ($request->filled('status')) {
            $query->where('expenses.status', $request->query('status'));
        }
        if ($request->filled('memberId')) {
            $query->where('expenses.member_id', (int) $request->query('memberId'));
        }
        if ($request->filled('categoryId')) {
            $query->where('expenses.category_id', (int) $request->query('categoryId'));
        }
        if ($request->filled('paymentMethod')) {
            $query->where('expenses.payment_method', $request->query('paymentMethod'));
        }
        if ($request->filled('dateFrom')) {
            $query->where('expenses.expense_date', '>=', $this->normalizeDateInput($request->query('dateFrom')));
        }
        if ($request->filled('dateTo')) {
            $query->where('expenses.expense_date', '<=', $request->query('dateTo') . ' 23:59:59');
        }
        if ($request->filled('amountMin')) {
            $query->where('expenses.amount', '>=', (float) $request->query('amountMin'));
        }
        if ($request->filled('amountMax')) {
            $query->where('expenses.amount', '<=', (float) $request->query('amountMax'));
        }
        if ($request->filled('search')) {
            $s = '%' . strtolower($request->query('search')) . '%';
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(expenses.title) LIKE ?', [$s])
                    ->orWhereRaw('LOWER(expenses.description) LIKE ?', [$s])
                    ->orWhereRaw('LOWER(expenses.member_name) LIKE ?', [$s])
                    ->orWhereRaw('LOWER(expenses.reference_number) LIKE ?', [$s])
                    ->orWhereRaw('LOWER(expenses.vendor) LIKE ?', [$s]);
            });
        }
    }

    private function selectColumns()
    {
        return [
            'expenses.id',
            'expenses.title',
            'expenses.description',
            'expenses.category_id as categoryId',
            'expense_categories.name as categoryName',
            'expenses.amount',
            'expenses.payment_method as paymentMethod',
            'expenses.vendor',
            'expenses.member_id as memberId',
            'expenses.member_name as memberName',
            'expenses.expense_date as expenseDate',
            'expenses.reference_number as referenceNumber',
            'expenses.attachment_url as attachmentUrl',
            'expenses.notes',
            'expenses.status',
            'expenses.created_by_id as createdById',
            'users.name as createdByName',
            'expenses.created_at as createdAt',
            'expenses.updated_at as updatedAt',
        ];
    }

    public function list(Request $request): JsonResponse
    {
        $page = max(1, (int) ($request->query('page') ?: self::DEFAULT_PAGE));
        $limit = max(1, (int) ($request->query('limit') ?: self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $query = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->leftJoin('users', 'expenses.created_by_id', '=', 'users.id');

        $this->buildWhere($request, $query);

        $total = $query->count();

        $data = $query->select($this->selectColumns())
            ->orderBy('expenses.expense_date', 'desc')
            ->orderBy('expenses.id', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    public function getById(int $id): JsonResponse
    {
        $row = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->leftJoin('users', 'expenses.created_by_id', '=', 'users.id')
            ->where('expenses.id', $id)
            ->select($this->selectColumns())
            ->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Expense not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $row]);
    }

    private function resolveMemberName(?int $memberId, ?string $fallback): ?string
    {
        if ($fallback) return $fallback;
        if (!$memberId) return null;
        $user = User::find($memberId);
        return $user ? $user->name : null;
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'categoryId' => 'nullable|integer',
            'amount' => 'required|numeric',
            'paymentMethod' => 'nullable|string|max:50',
            'vendor' => 'nullable|string|max:100',
            'memberId' => 'nullable|integer',
            'memberName' => 'nullable|string|max:100',
            'expenseDate' => 'required|string',
            'referenceNumber' => 'nullable|string|max:100',
            'attachmentUrl' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $actor = $request->user();
        $memberId = isset($validated['memberId']) ? (int) $validated['memberId'] : null;
        $memberName = $this->resolveMemberName($memberId, $validated['memberName'] ?? null);

        $expense = Expense::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category_id' => $validated['categoryId'] ?? null,
            'amount' => $validated['amount'],
            'payment_method' => $validated['paymentMethod'] ?? 'cash',
            'vendor' => $validated['vendor'] ?? null,
            'member_id' => $memberId,
            'member_name' => $memberName,
            'expense_date' => $this->normalizeDateInput($validated['expenseDate']),
            'reference_number' => $validated['referenceNumber'] ?? null,
            'attachment_url' => $validated['attachmentUrl'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'approved',
            'created_by_id' => $actor?->id,
        ]);

        $created = $this->getById($expense->id)->getData()->data;
        return response()->json(['success' => true, 'data' => $created], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $expense = Expense::find($id);
        if (!$expense) {
            return response()->json(['success' => false, 'message' => 'Expense not found'], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:200',
            'description' => 'nullable|string',
            'categoryId' => 'nullable|integer',
            'amount' => 'sometimes|numeric',
            'paymentMethod' => 'nullable|string|max:50',
            'vendor' => 'nullable|string|max:100',
            'memberId' => 'nullable|integer',
            'memberName' => 'nullable|string|max:100',
            'expenseDate' => 'sometimes|string',
            'referenceNumber' => 'nullable|string|max:100',
            'attachmentUrl' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,approved,rejected',
        ]);

        $updateData = [];
        if ($request->has('title')) $updateData['title'] = $validated['title'];
        if ($request->has('description')) $updateData['description'] = $validated['description'];
        if ($request->has('categoryId')) $updateData['category_id'] = $validated['categoryId'];
        if ($request->has('amount')) $updateData['amount'] = $validated['amount'];
        if ($request->has('paymentMethod')) $updateData['payment_method'] = $validated['paymentMethod'];
        if ($request->has('vendor')) $updateData['vendor'] = $validated['vendor'];
        if ($request->has('memberId')) {
            $memberId = (int) $validated['memberId'] ?: null;
            $updateData['member_id'] = $memberId;
            $updateData['member_name'] = $this->resolveMemberName($memberId, $validated['memberName'] ?? null);
        }
        if ($request->has('expenseDate')) {
            $updateData['expense_date'] = $this->normalizeDateInput($validated['expenseDate']);
        }
        if ($request->has('referenceNumber')) $updateData['reference_number'] = $validated['referenceNumber'];
        if ($request->has('attachmentUrl')) $updateData['attachment_url'] = $validated['attachmentUrl'];
        if ($request->has('notes')) $updateData['notes'] = $validated['notes'];
        if ($request->has('status')) $updateData['status'] = $validated['status'];

        $expense->update($updateData);

        $fresh = $this->getById($id)->getData()->data;
        return response()->json(['success' => true, 'data' => $fresh]);
    }

    public function remove(int $id): JsonResponse
    {
        $expense = Expense::find($id);
        if (!$expense) {
            return response()->json(['success' => false, 'message' => 'Expense not found'], 404);
        }

        $expense->delete();
        return response()->json(['success' => true]);
    }

    // ==================== CATEGORIES ====================

    public function categories(): JsonResponse
    {
        $cats = ExpenseCategory::orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get();
        return response()->json(['success' => true, 'data' => $cats]);
    }

    public function createCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'sortOrder' => 'nullable|integer',
            'status' => 'nullable|in:active,inactive',
        ]);

        $cat = ExpenseCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sortOrder'] ?? 0,
            'status' => $validated['status'] ?? 'active',
        ]);

        return response()->json(['success' => true, 'data' => $cat], 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $cat = ExpenseCategory::find($id);
        if (!$cat) {
            return response()->json(['success' => false, 'message' => 'Expense category not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'sortOrder' => 'sometimes|integer',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $validated['name'];
        if ($request->has('description')) $updateData['description'] = $validated['description'];
        if ($request->has('sortOrder')) $updateData['sort_order'] = $validated['sortOrder'];
        if ($request->has('status')) $updateData['status'] = $validated['status'];

        $cat->update($updateData);

        return response()->json(['success' => true, 'data' => $cat->fresh()]);
    }

    public function removeCategory(int $id): JsonResponse
    {
        $cat = ExpenseCategory::find($id);
        if (!$cat) {
            return response()->json(['success' => false, 'message' => 'Expense category not found'], 404);
        }

        $count = Expense::where('category_id', $id)->count();
        if ($count > 0) {
            return response()->json(['success' => false, 'usageCount' => $count, 'message' => 'Category is used by expenses']);
        }

        $cat->delete();
        return response()->json(['success' => true, 'usageCount' => 0]);
    }

    // ==================== MEMBERS ====================

    public function members(): JsonResponse
    {
        $members = User::whereIn('role', ['admin', 'manager'])
            ->where('status', 'active')
            ->select(['id', 'name', 'phone', 'role'])
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $members]);
    }

    // ==================== REPORTS ====================

    private function applyReportWhere(Request $request, $query)
    {
        $query->where('expenses.status', '!=', 'rejected');

        if ($request->filled('status')) {
            $query->where('expenses.status', $request->query('status'));
        }
        if ($request->filled('memberId')) {
            $query->where('expenses.member_id', (int) $request->query('memberId'));
        }
        if ($request->filled('categoryId')) {
            $query->where('expenses.category_id', (int) $request->query('categoryId'));
        }
        if ($request->filled('dateFrom')) {
            $query->where('expenses.expense_date', '>=', $this->normalizeDateInput($request->query('dateFrom')));
        }
        if ($request->filled('dateTo')) {
            $query->where('expenses.expense_date', '<=', $request->query('dateTo') . ' 23:59:59');
        }
    }

    public function summary(Request $request): JsonResponse
    {
        $now = Carbon::now();
        $todayStart = Carbon::today()->format('Y-m-d 00:00:00');
        $weekStart = Carbon::now()->startOfWeek()->format('Y-m-d 00:00:00');
        $monthStart = Carbon::now()->startOfMonth()->format('Y-m-d 00:00:00');

        $base = DB::table('expenses');
        $this->applyReportWhere($request, $base);

        $totalRow = (clone $base)->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')->first();
        $monthRow = (clone $base)->where('expense_date', '>=', $monthStart)->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')->first();
        $weekRow = (clone $base)->where('expense_date', '>=', $weekStart)->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')->first();
        $todayRow = (clone $base)->where('expense_date', '>=', $todayStart)->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as count')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $this->toNumber($totalRow->total),
                'totalCount' => (int) $totalRow->count,
                'thisMonth' => $this->toNumber($monthRow->total),
                'thisMonthCount' => (int) $monthRow->count,
                'thisWeek' => $this->toNumber($weekRow->total),
                'thisWeekCount' => (int) $weekRow->count,
                'today' => $this->toNumber($todayRow->total),
                'todayCount' => (int) $todayRow->count,
            ],
        ]);
    }

    public function byMember(Request $request): JsonResponse
    {
        $query = DB::table('expenses');
        $this->applyReportWhere($request, $query);

        $rows = $query->selectRaw('member_id as memberId, member_name as memberName, COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
            ->groupBy('member_id', 'member_name')
            ->orderByRaw('SUM(amount) DESC')
            ->get()
            ->map(function ($r) {
                return [
                    'memberId' => $r->memberId,
                    'memberName' => $r->memberName ?: 'Unassigned',
                    'total' => $this->toNumber($r->total),
                    'count' => (int) $r->count,
                ];
            });

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function byCategory(Request $request): JsonResponse
    {
        $query = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id');
        $this->applyReportWhere($request, $query);

        $rows = $query->selectRaw('expenses.category_id as categoryId, expense_categories.name as categoryName, COALESCE(SUM(expenses.amount), 0) as total, COUNT(*) as count')
            ->groupBy('expenses.category_id', 'expense_categories.name')
            ->orderByRaw('SUM(expenses.amount) DESC')
            ->get()
            ->map(function ($r) {
                return [
                    'categoryId' => $r->categoryId,
                    'categoryName' => $r->categoryName ?: 'Uncategorized',
                    'total' => $this->toNumber($r->total),
                    'count' => (int) $r->count,
                ];
            });

        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function monthlyReport(Request $request): JsonResponse
    {
        $now = Carbon::now();
        $year = (int) ($request->query('year') ?: $now->year);
        $month = (int) ($request->query('month') ?: $now->month);
        $page = max(1, (int) ($request->query('page') ?: self::DEFAULT_PAGE));
        $limit = max(1, (int) ($request->query('limit') ?: self::DEFAULT_LIMIT));

        $from = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d 00:00:00');
        $to = Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('Y-m-d 23:59:59');

        $query = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->leftJoin('users', 'expenses.created_by_id', '=', 'users.id')
            ->where('expenses.status', '!=', 'rejected')
            ->where('expenses.expense_date', '>=', $from)
            ->where('expenses.expense_date', '<=', $to);

        if ($request->filled('memberId')) {
            $query->where('expenses.member_id', (int) $request->query('memberId'));
        }
        if ($request->filled('categoryId')) {
            $query->where('expenses.category_id', (int) $request->query('categoryId'));
        }

        $agg = (clone $query)->selectRaw('COALESCE(SUM(expenses.amount), 0) as total, COUNT(*) as count, COALESCE(AVG(expenses.amount), 0) as average, COALESCE(MAX(expenses.amount), 0) as highest, COALESCE(MIN(expenses.amount), 0) as lowest')->first();
        $totalCount = (int) ($agg->count ?? 0);

        $expenses = (clone $query)->select($this->selectColumns())
            ->orderBy('expenses.expense_date', 'desc')
            ->orderBy('expenses.id', 'desc')
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();

        // sub-reports
        $memberReq = Request::create('', 'GET', ['dateFrom' => substr($from, 0, 10), 'dateTo' => substr($to, 0, 10), 'memberId' => $request->query('memberId')]);
        $memberRows = $this->byMember($memberReq)->getData()->data;
        $categoryRows = $this->byCategory($memberReq)->getData()->data;

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'total' => $this->toNumber($agg->total),
                'count' => $totalCount,
                'average' => round($this->toNumber($agg->average), 2),
                'highest' => $this->toNumber($agg->highest),
                'lowest' => $this->toNumber($agg->lowest),
                'byMember' => $memberRows,
                'byCategory' => $categoryRows,
                'expenses' => $expenses,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $totalCount,
                    'totalPages' => (int) ceil($totalCount / $limit),
                ],
            ],
        ]);
    }

    public function monthlyTrend(Request $request): JsonResponse
    {
        $year = (int) ($request->query('year') ?: Carbon::now()->year);
        $from = "{$year}-01-01 00:00:00";
        $to = "{$year}-12-31 23:59:59";

        $rows = DB::table('expenses')
            ->where('status', '!=', 'rejected')
            ->where('expense_date', '>=', $from)
            ->where('expense_date', '<=', $to)
            ->selectRaw('MONTH(expense_date) as month, COALESCE(SUM(amount), 0) as total, COUNT(*) as count')
            ->groupBy(DB::raw('MONTH(expense_date)'))
            ->orderBy(DB::raw('MONTH(expense_date)'))
            ->get();

        $byMonth = [];
        foreach ($rows as $r) {
            $byMonth[(int) $r->month] = [
                'total' => $this->toNumber($r->total),
                'count' => (int) $r->count,
            ];
        }

        $monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $row = $byMonth[$m] ?? null;
            $data[] = [
                'month' => $m,
                'label' => $monthNames[$m - 1],
                'total' => $row ? $row['total'] : 0,
                'count' => $row ? $row['count'] : 0,
            ];
        }

        return response()->json([
            'success' => true,
            'year' => $year,
            'data' => $data,
        ]);
    }

    public function rangeReport(Request $request): JsonResponse
    {
        $query = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->leftJoin('users', 'expenses.created_by_id', '=', 'users.id');
        $this->applyReportWhere($request, $query);

        $agg = (clone $query)->selectRaw('COALESCE(SUM(expenses.amount), 0) as total, COUNT(*) as count')->first();
        $expenses = (clone $query)->select($this->selectColumns())
            ->orderBy('expenses.expense_date', 'desc')
            ->orderBy('expenses.id', 'desc')
            ->get();

        $memberRows = $this->byMember($request)->getData()->data;
        $categoryRows = $this->byCategory($request)->getData()->data;

        return response()->json([
            'success' => true,
            'data' => [
                'dateFrom' => $request->query('dateFrom'),
                'dateTo' => $request->query('dateTo'),
                'total' => $this->toNumber($agg->total),
                'count' => (int) ($agg->count ?? 0),
                'byMember' => $memberRows,
                'byCategory' => $categoryRows,
                'expenses' => $expenses,
            ],
        ]);
    }

    public function profitOverview(Request $request): JsonResponse
    {
        $now = Carbon::now();
        $year = (int) ($request->query('year') ?: $now->year);
        $month = (int) ($request->query('month') ?: $now->month);

        $fromDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $toDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $revenueRow = DB::table('orders')
            ->where('status', 'delivered')
            ->where('created_at', '>=', $fromDate)
            ->where('created_at', '<=', $toDate)
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue')
            ->first();

        $costRow = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', 'delivered')
            ->where('orders.created_at', '>=', $fromDate)
            ->where('orders.created_at', '<=', $toDate)
            ->selectRaw('COALESCE(SUM(order_items.quantity * COALESCE(products.cost_price, 0)), 0) as cost')
            ->first();

        $expenseRow = DB::table('expenses')
            ->where('status', '!=', 'rejected')
            ->where('expense_date', '>=', $fromDate->format('Y-m-d 00:00:00'))
            ->where('expense_date', '<=', $toDate->format('Y-m-d 23:59:59'))
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->first();

        $revenue = $this->toNumber($revenueRow->revenue);
        $productCost = $this->toNumber($costRow->cost);
        $operatingExpenses = $this->toNumber($expenseRow->total);
        $netProfit = $revenue - $productCost - $operatingExpenses;

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'revenue' => $revenue,
                'productCost' => $productCost,
                'operatingExpenses' => $operatingExpenses,
                'netProfit' => round($netProfit, 2),
                'hasRevenueData' => $revenue > 0,
            ],
        ]);
    }

    public function exportCsv(Request $request): JsonResponse
    {
        $query = DB::table('expenses')
            ->leftJoin('expense_categories', 'expenses.category_id', '=', 'expense_categories.id')
            ->leftJoin('users', 'expenses.created_by_id', '=', 'users.id');
        $this->buildWhere($request, $query);

        $rows = $query->select($this->selectColumns())
            ->orderBy('expenses.expense_date', 'desc')
            ->orderBy('expenses.id', 'desc')
            ->get();

        $esc = function ($v) {
            $s = ($v === null) ? '' : (string) $v;
            return preg_match('/[",\n]/', $s) ? '"' . str_replace('"', '""', $s) . '"' : $s;
        };

        $header = ['Date', 'Expense ID', 'Member', 'Category', 'Title', 'Description', 'Amount', 'Payment Method', 'Reference', 'Status'];
        $lines = [];

        foreach ($rows as $r) {
            $dateStr = $r->expenseDate ? substr((string) $r->expenseDate, 0, 10) : '';
            $idStr = 'EXP-' . str_pad($r->id, 5, '0', STR_PAD_LEFT);
            $line = [
                $dateStr,
                $idStr,
                $r->memberName ?? '',
                $r->categoryName ?? '',
                $r->title,
                $r->description ?? '',
                $r->amount,
                $r->paymentMethod,
                $r->referenceNumber ?? '',
                $r->status,
            ];
            $lines[] = implode(',', array_map($esc, $line));
        }

        $csv = implode(',', array_map($esc, $header)) . "\n" . implode("\n", $lines);

        return response()->json([
            'success' => true,
            'data' => [
                'csv' => "\xEF\xBB\xBF" . $csv,
                'count' => count($rows),
            ],
        ]);
    }
}
