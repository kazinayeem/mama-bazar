<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\SeoController;
use App\Models\Category;
use App\Services\MediaStorageService;
use App\Services\SlugService;
use Illuminate\Http\Request;

class AdminCategoryWebController extends Controller
{
    public function index(Request $request)
    {
        $q = Category::with('parent')->orderBy('sort_order', 'asc')->orderBy('name', 'asc');

        if ($search = trim((string) $request->get('q', ''))) {
            $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        if ($status = $request->get('status')) {
            if (in_array($status, ['active', 'inactive'], true)) {
                $q->where('status', $status);
            }
        }

        $categories = $q->paginate(20)->withQueryString();

        $parents = Category::whereNull('parent_id')->orderBy('name')->get();

        return view('admin.categories.index', compact('categories', 'parents'));
    }

    protected function categoryRules(?int $ignoreId = null): array
    {
        return [
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            'parent_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|in:active,inactive',
            'sort_order' => 'nullable|integer|min:0|max:100000',
            'featured' => 'nullable|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:1000',
            'seo_keywords' => 'nullable|string|max:500',
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->categoryRules());

        $data = [
            'name' => trim($validated['name']),
            'slug' => $request->filled('slug')
                ? trim(strtolower($request->input('slug')))
                : SlugService::toAsciiSlug($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'sort_order' => $validated['sort_order'] ?? 0,
            'featured' => $request->boolean('featured', false),
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'seo_keywords' => $validated['seo_keywords'] ?? null,
        ];

        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), 'categories');
            $data['image'] = $upload['url'];
        }

        Category::create($data);
        SeoController::clearCache();

        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate($this->categoryRules((int) $id));

        if (! empty($validated['parent_id']) && (int) $validated['parent_id'] === (int) $category->id) {
            return back()->withErrors(['parent_id' => 'A category cannot be its own parent.'])->withInput();
        }

        $data = [
            'name' => trim($validated['name']),
            'parent_id' => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? $category->status,
            'sort_order' => $validated['sort_order'] ?? 0,
            'featured' => $request->boolean('featured', (bool) $category->featured),
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
            'seo_keywords' => $validated['seo_keywords'] ?? null,
        ];
        if ($request->filled('slug')) {
            $data['slug'] = trim(strtolower($request->input('slug')));
        }

        if ($request->hasFile('image')) {
            if ($category->image) {
                MediaStorageService::deleteFile($category->image);
            }
            $upload = MediaStorageService::uploadFile($request->file('image'), 'categories');
            $data['image'] = $upload['url'];
        }

        $category->update($data);
        SeoController::clearCache();

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($category->image) {
            MediaStorageService::deleteFile($category->image);
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}
