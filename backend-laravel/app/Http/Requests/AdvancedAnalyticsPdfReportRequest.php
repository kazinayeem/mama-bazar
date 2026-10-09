<?php

namespace App\Http\Requests;

use App\Services\AdvancedAnalyticsReportService;
use App\Support\FinancialDataAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdvancedAnalyticsPdfReportRequest extends FormRequest
{
    /**
     * Route middleware already restricts this endpoint to analytics/report users.
     * The profitability report additionally needs the profit export right.
     */
    public function authorize(): bool
    {
        if ($this->input('report_type') !== 'profitability') {
            return true;
        }

        return FinancialDataAccess::forRequest($this)->forExport()->canViewProfitMargin;
    }

    protected function failedAuthorization(): void
    {
        throw new AuthorizationException('You do not have permission to export profit reports.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', Rule::in(array_keys(AdvancedAnalyticsReportService::REPORT_TITLES))],
            'orientation' => ['required', 'string', Rule::in(['landscape', 'portrait'])],
            'preset' => ['nullable', 'string', Rule::in(['today', 'yesterday', '7d', '30d', 'this_month', 'last_month', 'this_quarter', 'this_year', 'month_year', 'custom'])],
            'start_date' => ['nullable', 'required_if:preset,custom', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'required_if:preset,custom', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2020,2035'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'stock_status' => ['nullable', 'string', Rule::in(['all', 'in_stock', 'low_stock', 'out_of_stock', 'overstock'])],
            'product_status' => ['nullable', 'string', Rule::in(['all', 'published', 'draft', 'archived'])],
            'sort_by' => ['nullable', 'string', Rule::in(['revenue_desc', 'units_desc', 'units_asc', 'stock_desc', 'stock_asc', 'retail_val_desc', 'price_desc', 'price_asc', 'title_asc'])],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'report_type.required' => 'Choose a report type.',
            'report_type.in' => 'The selected report type is not supported.',
            'orientation.required' => 'Choose a page orientation.',
            'orientation.in' => 'Page orientation must be landscape or portrait.',
            'end_date.after_or_equal' => 'The report end date must be on or after the start date.',
        ];
    }
}
