<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\MediaStorageService;
use App\Services\SlugService;
use Illuminate\Http\Request;

class AdminCategoryWebController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $parents = Category::whereNull('parent_id')->get();

        return view('admin.categories.index', compact('categories', 'parents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $data = $request->except(['image']);

        if ($request->hasFile('image')) {
            $upload = MediaStorageService::uploadFile($request->file('image'), 'categories');
            $data['image'] = $upload['url'];
        }

        $data['slug'] = $request->filled('slug')
            ? trim(strtolower($request->input('slug')))
            : SlugService::toAsciiSlug($data['name']);

        Category::create($data);

        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $data = $request->except(['image', '_token', '_method']);

        if ($request->hasFile('image')) {
            if ($category->image) {
                MediaStorageService::deleteFile($category->image);
            }
            $upload = MediaStorageService::uploadFile($request->file('image'), 'categories');
            $data['image'] = $upload['url'];
        }

        $category->update($data);

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
