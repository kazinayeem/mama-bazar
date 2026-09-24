<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\CheckoutNotice;
use App\Models\Collection;
use App\Models\Color;
use App\Models\ExpenseCategory;
use App\Models\Size;
use App\Models\Supplier;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCatalogWebController extends Controller
{
    protected function catalogConfig(string $resource): array
    {
        $configs = [
            'brands' => [
                'model' => Brand::class,
                'title' => 'Brands',
                'description' => 'Manage product brands and logos.',
                'route' => 'admin.brands',
                'empty' => 'No brands yet.',
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'website', 'label' => 'Website', 'type' => 'text'],
                    ['name' => 'country_of_origin', 'label' => 'Country', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
                    ['name' => 'logo', 'label' => 'Logo URL', 'type' => 'text', 'full' => true],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number'],
                    ['name' => 'featured', 'label' => 'Featured', 'type' => 'checkbox'],
                    ['name' => 'homepage_visibility', 'label' => 'Show on Homepage', 'type' => 'checkbox'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                ],
                'columns' => ['logo', 'name', 'slug', 'products_count', 'featured', 'status', 'created_at'],
                'withCount' => ['products'],
            ],
            'collections' => [
                'model' => Collection::class,
                'title' => 'Collections',
                'description' => 'Curated product collections.',
                'route' => 'admin.collections',
                'empty' => 'No collections yet.',
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'image', 'label' => 'Image URL', 'type' => 'text'],
                    ['name' => 'banner', 'label' => 'Banner URL', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number'],
                    ['name' => 'featured', 'label' => 'Featured', 'type' => 'checkbox'],
                    ['name' => 'homepage_visibility', 'label' => 'Show on Homepage', 'type' => 'checkbox'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                ],
                'columns' => ['name', 'slug', 'status', 'featured'],
            ],
            'colors' => [
                'model' => Color::class,
                'title' => 'Colors',
                'description' => 'Catalog color swatches.',
                'route' => 'admin.colors',
                'empty' => 'No colors yet.',
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'display_name', 'label' => 'Display Name', 'type' => 'text'],
                    ['name' => 'hex', 'label' => 'Hex Color', 'type' => 'hex'],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                ],
                'columns' => ['name', 'hex', 'status'],
            ],
            'sizes' => [
                'model' => Size::class,
                'title' => 'Sizes',
                'description' => 'Catalog size options.',
                'route' => 'admin.sizes',
                'empty' => 'No sizes yet.',
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'type', 'label' => 'Type', 'type' => 'text'],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                ],
                'columns' => ['name', 'type', 'status'],
            ],
            'vendors' => [
                'model' => Vendor::class,
                'title' => 'Vendors',
                'description' => 'Vendor directory.',
                'route' => 'admin.vendors',
                'empty' => 'No vendors yet.',
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'contact', 'label' => 'Contact Person', 'type' => 'text'],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'text'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'full' => true],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'full' => true],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                ],
                'columns' => ['name', 'phone', 'email', 'status'],
            ],
            'suppliers' => [
                'model' => Supplier::class,
                'title' => 'Suppliers',
                'description' => 'Supplier registry.',
                'route' => 'admin.suppliers',
                'empty' => 'No suppliers yet.',
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'contact', 'label' => 'Contact Person', 'type' => 'text'],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'text'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'full' => true],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'full' => true],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                ],
                'columns' => ['name', 'phone', 'email', 'status'],
            ],
            'checkout-notices' => [
                'model' => CheckoutNotice::class,
                'title' => 'Checkout Notices',
                'description' => 'Notices shown on the checkout page.',
                'route' => 'admin.checkout-notices',
                'empty' => 'No checkout notices yet.',
                'fields' => [
                    ['name' => 'text', 'label' => 'Notice Text', 'type' => 'textarea', 'required' => true, 'full' => true],
                    ['name' => 'icon', 'label' => 'Icon', 'type' => 'text'],
                    ['name' => 'background_color', 'label' => 'Background', 'type' => 'hex'],
                    ['name' => 'text_color', 'label' => 'Text Color', 'type' => 'hex'],
                    ['name' => 'priority', 'label' => 'Priority', 'type' => 'number'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                ],
                'columns' => ['text', 'priority', 'status'],
            ],
            'expense-categories' => [
                'model' => ExpenseCategory::class,
                'title' => 'Expense Categories',
                'description' => 'Categories for operational expenses.',
                'route' => 'admin.expense-categories',
                'empty' => 'No expense categories yet.',
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'full' => true],
                    ['name' => 'sort_order', 'label' => 'Sort Order', 'type' => 'number'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                ],
                'columns' => ['name', 'status', 'sort_order'],
            ],
        ];

        abort_unless(isset($configs[$resource]), 404);

        return $configs[$resource];
    }

    public function index(string $resource)
    {
        $config = $this->catalogConfig($resource);
        $model = $config['model'];
        $q = $model::query();

        if ($search = request('q')) {
            $q->where(function ($query) use ($search, $config) {
                foreach ($config['columns'] as $col) {
                    if (in_array($col, ['name', 'slug', 'text', 'phone', 'email', 'type'], true)) {
                        $query->orWhere($col, 'like', "%{$search}%");
                    }
                }
            });
        }

        if ($status = request('status')) {
            $q->where('status', $status);
        }

        if (! empty($config['withCount'])) {
            $q->withCount($config['withCount']);
        }

        $items = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.catalog.index', [
            'config' => $config,
            'resource' => $resource,
            'items' => $items,
            'headerTitle' => $config['title'],
        ]);
    }

    public function store(Request $request, string $resource)
    {
        $config = $this->catalogConfig($resource);
        $model = $config['model'];
        $data = $this->validatedPayload($request, $config);

        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $model::create($data);

        return back()->with('success', $config['title'].' item created.');
    }

    public function update(Request $request, string $resource, int $id)
    {
        $config = $this->catalogConfig($resource);
        $item = $config['model']::findOrFail($id);
        $data = $this->validatedPayload($request, $config);

        if (empty($data['slug']) && ! empty($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $item->update($data);

        return back()->with('success', $config['title'].' item updated.');
    }

    public function destroy(string $resource, int $id)
    {
        $config = $this->catalogConfig($resource);
        $item = $config['model']::findOrFail($id);
        $item->delete();

        return back()->with('success', $config['title'].' item deleted.');
    }

    protected function validatedPayload(Request $request, array $config): array
    {
        $rules = [];
        foreach ($config['fields'] as $field) {
            $rule = [];
            $rule[] = ! empty($field['required']) ? 'required' : 'nullable';
            if ($field['type'] === 'number') {
                $rule[] = 'numeric';
            }
            $rules[$field['name']] = implode('|', $rule);
        }

        $data = $request->validate($rules);

        foreach ($config['fields'] as $field) {
            if ($field['type'] === 'checkbox') {
                $data[$field['name']] = $request->boolean($field['name']);
            }
        }

        return $data;
    }
}
