{{-- Shared Bornosoft software-attribution footer for generated PDFs.
      Included (not duplicated) by every PDF/print document template.
      dompdf turns the anchor into a clickable link annotation automatically.
      Static in-flow block (never position:fixed) so it cannot overlap tables
      and appears once at the end of the document with safe spacing. --}}
@php($pdfBrand = \App\Support\PdfBranding::attribution())
<div style="margin-top: 18px; border-top: 1px solid #e2e8f0; padding-top: 8px; text-align: center; font-size: 9px; color: #94a3b8; line-height: 1.6;">
    <span>{{ $pdfBrand['tagline'] }} &middot; {{ $pdfBrand['subline'] }}</span><br>
    <a href="{{ $pdfBrand['url'] }}" style="color: #64748b; text-decoration: none;">{{ $pdfBrand['domain'] }}</a>
</div>
