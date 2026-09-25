<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get();
        return view('manager.expense_categories', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'keywords' => 'nullable|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        Category::create([
            'name' => $request->input('name'),
            'code' => Str::slug($request->input('name')),
            'keywords' => $request->input('keywords'),
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('manager.expense_categories')->with('success', 'Category created successfully.');
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
            'keywords' => 'nullable',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean'
        ]);
        
        $keywords = $request->input('keywords', '');
        if (is_array($keywords)) {
            $keywords = implode(', ', array_filter(array_map('trim', $keywords)));
        } else {
            $keywords = implode(', ', array_filter(array_map('trim', explode(',', $keywords))));
        }

        $category->update([
            'name' => $request->input('name'),
            'code' => Str::slug($request->input('name')),
            'keywords' => $keywords,
            'description' => $request->input('description'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('manager.expense_categories')->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if (\App\Models\ExpensePolicy::where('category_id', $id)->exists() || \App\Models\Claim::where('category_id', $id)->exists()) {
            return redirect()->route('manager.expense_categories')->with('error', 'Cannot delete category with existing active claims or policies. Please set it to inactive instead.');
        }

        $category->delete();

        return redirect()->route('manager.expense_categories')->with('success', 'Category deleted successfully.');
    }
}
