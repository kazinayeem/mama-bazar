<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Catalog Audit Report — Mama Bazar</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 40px; color: #1e293b; line-height: 1.5; }
        .header { border-bottom: 2px solid #059669; padding-bottom: 20px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end; }
        .title { font-size: 24px; font-weight: 800; color: #0f172a; margin: 0; }
        .subtitle { font-size: 13px; color: #64748b; margin-top: 4px; }
        .score-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 20px; text-align: center; }
        .score-val { font-size: 28px; font-weight: 900; color: #166534; }
        .metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 30px; }
        .metric-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
        .metric-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; }
        .metric-val { font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; margin-top: 20px; }
        th { background: #f1f5f9; text-align: left; padding: 8px 10px; font-weight: 700; border-bottom: 1px solid #cbd5e1; }
        td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 700; }
        .badge-warn { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        @media print {
            .no-print { display: none; }
            body { margin: 20px; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="background: #059669; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;">
            Print / Save to PDF
        </button>
    </div>

    <div class="header">
        <div>
            <h1 class="title">Mama Bazar — Technical SEO Audit Report</h1>
            <p class="subtitle">Generated on {{ now()->format('F j, Y, g:i a') }} | Active Catalog Audit</p>
        </div>
        <div class="score-box">
            <div class="metric-label">Health Score</div>
            <div class="score-val">{{ $audit['health_score'] }}%</div>
        </div>
    </div>

    <div class="metrics-grid">
        <div class="metric-card">
            <div class="metric-label">Total Active Products</div>
            <div class="metric-val">{{ number_format($audit['products_total']) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Missing SEO Titles</div>
            <div class="metric-val">{{ number_format($audit['products_missing_title']) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Missing Descriptions</div>
            <div class="metric-val">{{ number_format($audit['products_missing_description']) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Products Without Images</div>
            <div class="metric-val">{{ number_format($audit['products_missing_images']) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Total Categories</div>
            <div class="metric-val">{{ number_format($audit['categories_total']) }}</div>
        </div>
        <div class="metric-card">
            <div class="metric-label">Categories Missing Description</div>
            <div class="metric-val">{{ number_format($audit['categories_missing_description']) }}</div>
        </div>
    </div>

    <h2 style="font-size: 14px; font-weight: 700; margin-top: 30px; margin-bottom: 8px;">Top Identified Products Requiring Metadata Attention</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Product Title</th>
                <th>SKU</th>
                <th>Category</th>
                <th>Missing Metadata Attributes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($issues as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td><strong>{{ $p->title }}</strong></td>
                    <td>{{ $p->sku ?? 'N/A' }}</td>
                    <td>{{ $p->category?->name ?? 'Uncategorized' }}</td>
                    <td>
                        @if(empty($p->seo_title))
                            <span class="badge badge-warn">Missing SEO Title</span>
                        @endif
                        @if(empty($p->seo_description))
                            <span class="badge badge-warn">Missing SEO Description</span>
                        @endif
                        @if(empty($p->images) || $p->images === '[]')
                            <span class="badge badge-danger">No Product Image</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">No critical SEO gaps detected!</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 40px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px;">
        Mama Bazar Production SEO Engine • Dynamic Catalog Audit
    </div>
</body>
</html>
