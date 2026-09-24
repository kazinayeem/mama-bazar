<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Color;
use App\Models\Size;
use App\Models\Collection;
use App\Models\Vendor;
use App\Models\Supplier;
use App\Models\Brand;
use App\Models\Product;
use App\Services\MediaStorageService;
use App\Services\SlugService;

class CatalogController extends Controller
{
    private function getModelClass(string $type): string
    {
        return match ($type) {
            'colors' => Color::class,
            'sizes' => Size::class,
            'collections' => Collection::class,
            'vendors' => Vendor::class,
            'suppliers' => Supplier::class,
            'brands' => Brand::class,
            default => abort(404),
        };
    }

    private function getForeignKey(string $type): string
    {
        return match ($type) {
            'collections' => 'collection_id',
            'vendors' => 'vendor_id',
            'suppliers' => 'supplier_id',
            'brands' => 'brand_id',
            default => '',
        };
    }

    public function list(Request $request, string $type)
    {
        $model = $this->getModelClass($type);
        $query = $model::where('status', 'active');
        if (in_array($type, ['colors', 'sizes', 'collections', 'brands'])) {
            $query->orderBy('sort_order', 'asc');
        }
        $data = $query->orderBy('name', 'asc')->get();
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function listAdmin(Request $request, string $type)
    {
        $model = $this->getModelClass($type);
        $query = $model::query();

        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where('name', 'like', "%{$s}%");
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('featured') && in_array($type, ['collections', 'brands'])) {
            $query->where('featured', filter_var($request->input('featured'), FILTER_VALIDATE_BOOLEAN));
        }

        $page = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 20);

        if (in_array($type, ['colors', 'sizes', 'collections', 'brands'])) {
            $query->orderBy('sort_order', 'asc');
        }
        $query->orderBy('created_at', 'desc');

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

    public function getById(Request $request, string $type, $id)
    {
        $model = $this->getModelClass($type);
        $item = $model::find($id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => ucfirst(Str::singular($type)) . ' not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function getBySlug(Request $request, string $type, $slug)
    {
        $model = $this->getModelClass($type);
        $item = $model::where('slug', $slug)->first();
        if (!$item) {
            return response()->json(['success' => false, 'message' => ucfirst(Str::singular($type)) . ' not found'], 404);
        }
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function getUsage(Request $request, string $type, $id)
    {
        $fk = $this->getForeignKey($type);
        $count = $fk ? Product::where($fk, $id)->count() : 0;
        return response()->json(['success' => true, 'data' => ['products' => $count]]);
    }

    public function create(Request $request, string $type)
    {
        $model = $this->getModelClass($type);
        $data = $request->all();

        if (empty($data['name'])) {
            return response()->json(['success' => false, 'message' => 'Name is required'], 400);
        }

        if (in_array($type, ['collections', 'vendors', 'suppliers', 'brands'])) {
            $slug = !empty($data['slug']) ? Str::slug($data['slug']) : SlugService::toAsciiSlug($data['name']);
            if ($model::where('slug', $slug)->exists()) {
                return response()->json(['success' => false, 'message' => 'Slug already in use'], 409);
            }
            $data['slug'] = $slug;
        }

        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), $type);
            $data['image'] = $upload['url'];
        }
        if ($request->hasFile('logo')) {
            $upload = MediaStorageService::uploadFile($request->file('logo'), $type);
            $data['logo'] = $upload['url'];
        }

        $created = $model::create($data);
        return response()->json(['success' => true, 'data' => $created], 201);
    }

    public function update(Request $request, string $type, $id)
    {
        $model = $this->getModelClass($type);
        $item = $model::find($id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => ucfirst(Str::singular($type)) . ' not found'], 404);
        }

        $data = $request->all();

        if (in_array($type, ['collections', 'vendors', 'suppliers', 'brands']) && !empty($data['slug'])) {
            $slug = Str::slug($data['slug']);
            if ($model::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                return response()->json(['success' => false, 'message' => 'Slug already in use'], 409);
            }
            $data['slug'] = $slug;
        }

        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), $type);
            $data['image'] = $upload['url'];
        }
        if ($request->hasFile('logo')) {
            $upload = MediaStorageService::uploadFile($request->file('logo'), $type);
            $data['logo'] = $upload['url'];
        }

        $item->update($data);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function remove(Request $request, string $type, $id)
    {
        $model = $this->getModelClass($type);
        $item = $model::find($id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => ucfirst(Str::singular($type)) . ' not found'], 404);
        }

        $fk = $this->getForeignKey($type);
        if ($fk) {
            $count = Product::where($fk, $id)->count();
            if ($count > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "This {$type} is currently used by {$count} product(s). Move or delete them first.",
                    'usageCount' => $count,
                    'code' => 'in_use',
                ], 409);
            }
        }

        $item->delete();
        return response()->json(['success' => true, 'message' => ucfirst(Str::singular($type)) . ' deleted']);
    }

    public function moveProducts(Request $request, string $type, $id)
    {
        $targetId = $request->input('targetId') ? (int) $request->input('targetId') : null;
        $fk = $this->getForeignKey($type);
        $count = 0;
        if ($fk) {
            $count = Product::where($fk, $id)->update([$fk => $targetId]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'sourceId' => (int) $id,
                'targetId' => $targetId,
                'productsMoved' => $count,
            ],
        ]);
    }
}
