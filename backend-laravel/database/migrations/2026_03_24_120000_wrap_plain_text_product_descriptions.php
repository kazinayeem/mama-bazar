<?php

use App\Services\HtmlSanitizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Wrap legacy plain-text product descriptions in safe <p>…</p> HTML
 * without treating them as HTML (escape + nl2br). Already-HTML rows are left alone
 * (they will be re-sanitized on next save / via formatProduct).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->select(['id', 'description'])
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $desc = $row->description;
                    if ($desc === null || trim($desc) === '') {
                        continue;
                    }

                    // Skip content that already contains rich-text HTML tags
                    if (\App\Services\HtmlSanitizer::looksLikeHtml($desc)) {
                        // Still purify existing HTML to strip XSS leftovers
                        $cleaned = HtmlSanitizer::clean($desc);
                        if ($cleaned !== $desc) {
                            DB::table('products')->where('id', $row->id)->update([
                                'description' => $cleaned,
                            ]);
                        }
                        continue;
                    }

                    $wrapped = HtmlSanitizer::clean($desc);
                    DB::table('products')->where('id', $row->id)->update([
                        'description' => $wrapped,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Irreversible data migration — no-op
    }
};
