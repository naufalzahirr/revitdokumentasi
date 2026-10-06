<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories.index', ['categories' => Category::withCount('documents')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan dan tersedia di pilihan kategori nota.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        DB::transaction(function () use ($category, $data) {
            Document::where('category', $category->name)->update(['category' => $data['name']]);
            $category->update($data);
        });

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui, termasuk pada nota yang menggunakan kategori ini.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->documents()->exists()) {
            return redirect()->route('categories.index')->withErrors(['category' => 'Kategori masih digunakan oleh nota. Ubah kategori pada nota tersebut sebelum menghapusnya.']);
        }
        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category)]], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori harus berupa teks.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'name.unique' => 'Kategori tersebut sudah tersedia.',
        ]);
    }
}
