<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Product;
use App\Services\MediaStorageService;
use App\Services\SlugService;

class CategoryController extends Controller
{
    public function getAll()
    {
        $categories = Category::where('status', 'active')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function getTree()
    {
        $all = Category::orderBy('sort_order', 'asc')->orderBy('name', 'asc')->get();
        $byParent = [];
        foreach ($all as $c) {
            $parentId = $c->parent_id ?: 0;
            $byParent[$parentId][] = $c;
        }

        $buildTree = function ($parentId) use (&$buildTree, &$byParent) {
            $branch = [];
            if (!empty($byParent[$parentId])) {
                foreach ($byParent[$parentId] as $c) {
                    $item = $c->toArray();
                    $children = $buildTree($c->id);
                    $item['children'] = $children;
                    $branch[] = $item;
                }
            }
            return $branch;
        };

        $tree = $buildTree(0);

        return response()->json(['success' => true, 'data' => $tree]);
    }

    public function listAdmin(Request $request)
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('slug', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('parentId')) {
            $pid = $request->input('parentId');
            if ($pid === 'root' || $pid === 'null' || $pid === '0') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $pid);
            }
        }

        if ($request->has('featured')) {
            $query->where('featured', filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN));
        }

        $sort = $request->input('sort', 'sort_order');
        if ($sort === 'name') {
            $query->orderBy('name', 'asc');
        } elseif ($sort === 'created_at') {
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('sort_order', 'asc')->orderBy('created_at', 'desc');
        }

        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'pagination' => [
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'total' => $paginator->total(),
                'totalPages' => $paginator->lastPage(),
            ],
        ]);
    }

    public function getById($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $category]);
    }

    public function getBySlug($slug)
    {
        $category = Category::where('slug', $slug)->first();
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $category]);
    }

    public function getUsage($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        $productsCount = Product::where('category_id', $id)
            ->orWhere('sub_category_id', $id)
            ->orWhere('child_category_id', $id)
            ->count();

        $subCategoriesCount = Category::where('parent_id', $id)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $productsCount,
                'subCategories' => $subCategoriesCount,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $name = trim($validated['name']);
        $slug = $request->input('slug') ? Str::slug($request->input('slug')) : SlugService::toAsciiSlug($name);

        if (Category::where('slug', $slug)->exists()) {
            return response()->json(['success' => false, 'message' => 'Slug already exists'], 409);
        }

        $imageUrl = $request->input('image');
        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), 'categories');
            $imageUrl = $upload['url'];
        }

        $category = Category::create([
            'name' => $name,
            'slug' => $slug,
            'parent_id' => $request->filled('parentId') && $request->input('parentId') !== 'none' ? (int) $request->input('parentId') : null,
            'image' => $imageUrl,
            'icon' => $request->input('icon'),
            'banner' => $request->input('banner'),
            'thumbnail' => $request->input('thumbnail'),
            'description' => $request->input('description'),
            'featured' => filter_var($request->input('featured', false), FILTER_VALIDATE_BOOLEAN),
            'sort_order' => (int) $request->input('sortOrder', 0),
            'homepage_visibility' => filter_var($request->input('homepageVisibility', true), FILTER_VALIDATE_BOOLEAN),
            'seo_title' => $request->input('seoTitle'),
            'seo_description' => $request->input('seoDescription'),
            'seo_keywords' => $request->input('seoKeywords'),
            'status' => $request->input('status', 'active'),
        ]);

        return response()->json(['success' => true, 'data' => $category], 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        $updateData = [];
        if ($request->filled('name')) {
            $updateData['name'] = trim($request->input('name'));
            if (!$request->filled('slug')) {
                $updateData['slug'] = SlugService::toAsciiSlug($updateData['name']);
            }
        }
        if ($request->filled('slug')) {
            $updateData['slug'] = Str::slug($request->input('slug'));
        }

        if (isset($updateData['slug']) && Category::where('slug', $updateData['slug'])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Slug already exists'], 409);
        }

        if ($request->has('parentId')) {
            $pid = $request->input('parentId');
            $updateData['parent_id'] = ($pid === null || $pid === '' || $pid === 'none') ? null : (int) $pid;
        }

        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), 'categories');
            $updateData['image'] = $upload['url'];
        } elseif ($request->has('image')) {
            $updateData['image'] = $request->input('image');
        }

        foreach (['icon', 'banner', 'thumbnail', 'description', 'seoTitle', 'seoDescription', 'seoKeywords', 'status'] as $field) {
            $snake = Str::snake($field);
            if ($request->has($field)) {
                $updateData[$snake] = $request->input($field);
            }
        }

        if ($request->has('featured')) {
            $updateData['featured'] = filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('homepageVisibility')) {
            $updateData['homepage_visibility'] = filter_var($request->input('homepageVisibility'), FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('sortOrder')) {
            $updateData['sort_order'] = (int) $request->input('sortOrder');
        }

        $category->update($updateData);

        return response()->json(['success' => true, 'data' => $category]);
    }

    public function remove($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        $subCategories = Category::where('parent_id', $id)->count();
        if ($subCategories > 0) {
            return response()->json([
                'success' => false,
                'message' => "This category has {$subCategories} sub-categories. Move or delete them first.",
                'usageCount' => 0,
                'subCategories' => $subCategories,
                'code' => 'has_children',
            ], 409);
        }

        $products = Product::where('category_id', $id)
            ->orWhere('sub_category_id', $id)
            ->orWhere('child_category_id', $id)
            ->count();

        if ($products > 0) {
            return response()->json([
                'success' => false,
                'message' => "This category is currently used by {$products} product" . ($products > 1 ? "s" : "") . ".",
                'usageCount' => $products,
                'subCategories' => 0,
                'code' => 'in_use',
            ], 409);
        }

        $category->delete();
        return response()->json(['success' => true, 'message' => 'Category deleted']);
    }

    public function moveProducts(Request $request, $id)
    {
        $targetId = $request->input('targetId') ? (int) $request->input('targetId') : null;

        $updated = Product::where('category_id', $id)->update(['category_id' => $targetId]);

        return response()->json([
            'success' => true,
            'data' => [
                'sourceCategoryId' => (int) $id,
                'targetCategoryId' => $targetId,
                'productsMoved' => $updated,
            ],
        ]);
    }
}
