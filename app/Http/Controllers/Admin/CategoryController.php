<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /** GET /admin/categories — with how many events use each one. */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Category::withCount('events')->orderBy('id')->get()
                ->map(fn ($c) => $this->format($c)),
        ]);
    }

    /** POST /admin/categories */
    public function store(CategoryRequest $request): JsonResponse
    {
        $data = $this->clean($request->validated());
        abort_if(Category::where('slug', $data['slug'])->exists(), 422, 'A category with this name already exists.');

        $category = Category::create($data);

        return response()->json(['data' => $this->format($category->loadCount('events'))], 201);
    }

    /** PUT /admin/categories/{category} */
    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $data = $this->clean($request->validated(), $category);
        abort_if(Category::where('slug', $data['slug'])->whereKeyNot($category->id)->exists(), 422, 'A category with this name already exists.');

        $category->update($data);

        return response()->json(['data' => $this->format($category->loadCount('events'))]);
    }

    /** DELETE /admin/categories/{category} — its events keep existing without a category. */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['data' => ['message' => 'Category deleted.']]);
    }

    private function clean(array $data, ?Category $existing = null): array
    {
        $nameEn = Str::of($data['name_en'])->squish()->toString();

        return [
            'name_en' => $nameEn,
            'name_km' => trim((string) ($data['name_km'] ?? '')) ?: $nameEn,
            // Keep the old slug when renaming, so links and filters keep working
            'slug' => $data['slug'] ?? $existing?->slug ?? Str::slug($nameEn),
        ];
    }

    private function format(Category $c): array
    {
        return [
            'id' => $c->id,
            'slug' => $c->slug,
            'name_en' => $c->name_en,
            'name_km' => $c->name_km,
            'events_count' => $c->events_count ?? 0,
        ];
    }
}
