<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\SeoController;
use App\Models\Category;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminSeoController extends Controller
{
    /**
     * SEO Dashboard & Audit Overview.
     */
    public function index(Request $request)
    {
        $audit = SeoService::auditCatalog();

        // Issues query builder
        $tab = $request->query('tab', 'products');
        $filter = $request->query('filter', 'all');
        $search = trim((string) $request->query('q', ''));

        $productIssuesQuery = Product::where('status', 'active');

        if ($search !== '') {
            $productIssuesQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($filter === 'missing_title') {
            $productIssuesQuery->where(fn ($q) => $q->whereNull('seo_title')->orWhere('seo_title', ''));
        } elseif ($filter === 'missing_description') {
            $productIssuesQuery->where(fn ($q) => $q->whereNull('seo_description')->orWhere('seo_description', ''));
        } elseif ($filter === 'missing_image') {
            $productIssuesQuery->where(fn ($q) => $q->whereNull('images')->orWhere('images', '[]')->orWhere('images', ''));
        } elseif ($filter === 'duplicate_title') {
            $productIssuesQuery->whereIn('title', $audit['duplicate_product_titles']);
        } elseif ($filter === 'all_issues') {
            $productIssuesQuery->where(function ($q) use ($audit) {
                $q->whereNull('seo_title')
                    ->orWhere('seo_title', '')
                    ->orWhereNull('seo_description')
                    ->orWhere('seo_description', '')
                    ->orWhereNull('images')
                    ->orWhere('images', '[]')
                    ->orWhere('images', '')
                    ->orWhereIn('title', $audit['duplicate_product_titles']);
            });
        }

        $products = $productIssuesQuery->with(['category', 'brandRel'])
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Categories list
        $categoryIssuesQuery = Category::where('status', 'active');
        if ($search !== '') {
            $categoryIssuesQuery->where('name', 'like', "%{$search}%");
        }
        $categories = $categoryIssuesQuery->orderBy('sort_order')->paginate(15, ['*'], 'cat_page')->withQueryString();

        // Homepage & Shop Custom SEO meta
        $homeSeo = SeoMeta::where('route_name', 'home')->first();
        $shopSeo = SeoMeta::where('route_name', 'shop')->first();

        return view('admin.seo.index', compact(
            'audit', 'products', 'categories', 'tab', 'filter', 'search', 'homeSeo', 'shopSeo'
        ));
    }

    /**
     * Update route-level SEO metadata (Home / Shop).
     */
    public function updateRouteSeo(Request $request)
    {
        $validated = $request->validate([
            'route_name' => 'required|string|in:home,shop',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:1000',
            'seo_keywords' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:100',
        ]);

        SeoMeta::updateOrCreate(
            ['route_name' => $validated['route_name']],
            [
                'seo_title' => $validated['seo_title'],
                'seo_description' => $validated['seo_description'],
                'seo_keywords' => $validated['seo_keywords'],
                'canonical_url' => $validated['canonical_url'],
                'robots' => $validated['robots'] ?: 'index, follow',
            ]
        );

        SeoController::clearCache();

        return back()->with('success', 'SEO configuration saved successfully.');
    }

    /**
     * Controlled Bulk SEO Metadata Auto-Draft Generator.
     */
    public function generateDrafts(Request $request)
    {
        $type = $request->input('type', 'missing_only');
        $apply = $request->boolean('apply', false);

        $query = Product::where('status', 'active');
        if ($type === 'missing_only') {
            $query->where(function ($q) {
                $q->whereNull('seo_title')
                    ->orWhere('seo_title', '')
                    ->orWhereNull('seo_description')
                    ->orWhere('seo_description', '');
            });
        }

        $targetProducts = $query->with(['category', 'brandRel'])->take(50)->get();
        $generatedCount = 0;

        foreach ($targetProducts as $product) {
            $brand = $product->brandRel?->name ?? $product->brand;
            $cat = $product->category?->name;

            // Generate unique title
            $title = $product->title;
            if ($brand && ! Str::contains(strtolower($title), strtolower($brand))) {
                $title .= " - {$brand}";
            }
            $title .= ' | Mama Bazar';

            // Generate rich description
            $priceText = $product->sale_price ? '৳'.number_format($product->sale_price) : '৳'.number_format($product->price);
            $catText = $cat ? " in {$cat}" : '';
            $desc = "Buy {$product->title}{$catText} at Mama Bazar for {$priceText}. 100% authentic product with fast doorstep delivery across Bangladesh.";

            if ($apply) {
                if (empty($product->seo_title)) {
                    $product->seo_title = Str::limit($title, 120);
                }
                if (empty($product->seo_description)) {
                    $product->seo_description = Str::limit($desc, 160);
                }
                $product->save();
                $generatedCount++;
            }
        }

        SeoController::clearCache();

        if ($apply) {
            return back()->with('success', "Successfully generated SEO metadata drafts for {$generatedCount} products.");
        }

        return back()->with('info', "Found {$targetProducts->count()} products eligible for automatic metadata generation.");
    }

    /**
     * Clear Sitemap Caches.
     */
    public function refreshSitemap()
    {
        SeoController::clearCache();

        return back()->with('success', 'Sitemap and SEO caches cleared. New sitemaps will be regenerated on next request.');
    }

    /**
     * Export Audit Report as CSV.
     */
    public function exportCsv()
    {
        $products = Product::where('status', 'active')
            ->with(['category', 'brandRel'])
            ->get();

        $filename = 'mama_bazar_seo_audit_'.now()->format('Y_m_d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Product ID',
                'Title',
                'SKU',
                'Category',
                'Brand',
                'Has Main Image',
                'SEO Title Status',
                'Current SEO Title',
                'SEO Description Status',
                'Current SEO Description',
                'Canonical URL',
                'Audit Issues',
            ]);

            foreach ($products as $p) {
                $hasImage = ! empty($p->images) && $p->images !== '[]';
                $hasTitle = ! empty($p->seo_title);
                $hasDesc = ! empty($p->seo_description);

                $issues = [];
                if (! $hasTitle) {
                    $issues[] = 'Missing SEO Title';
                }
                if (! $hasDesc) {
                    $issues[] = 'Missing SEO Description';
                }
                if (! $hasImage) {
                    $issues[] = 'Missing Product Image';
                }

                fputcsv($file, [
                    $p->id,
                    $p->title,
                    $p->sku ?? 'N/A',
                    $p->category?->name ?? 'Uncategorized',
                    $p->brandRel?->name ?? $p->brand ?? 'None',
                    $hasImage ? 'Yes' : 'NO',
                    $hasTitle ? 'Custom' : 'Fallback Required',
                    $p->seo_title ?? '',
                    $hasDesc ? 'Custom' : 'Fallback Required',
                    $p->seo_description ?? '',
                    $p->canonical_url ?? route('products.show', ['slug' => $p->slug]),
                    empty($issues) ? 'Optimal' : implode('; ', $issues),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Printable / PDF-ready Audit Report.
     */
    public function exportPdf()
    {
        $audit = SeoService::auditCatalog();
        $issues = Product::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('seo_title')
                    ->orWhere('seo_title', '')
                    ->orWhereNull('seo_description')
                    ->orWhere('seo_description', '')
                    ->orWhereNull('images')
                    ->orWhere('images', '[]')
                    ->orWhere('images', '');
            })
            ->with(['category', 'brandRel'])
            ->limit(100)
            ->get();

        return view('admin.seo.pdf-report', compact('audit', 'issues'));
    }
}
