<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /** GET /categories — for filters and the event form. */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::orderBy('id')->get());
    }

    /**
     * POST /categories (organizers and admins) — { name_en, name_km? }
     * If a category with the same name already exists, that one is returned instead of a duplicate.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $nameEn = Str::of($request->validated('name_en'))->squish()->ucfirst()->toString();
        $slug = Str::slug($nameEn) ?: 'category-'.Str::lower(Str::random(6));

        $category = Category::firstOrCreate(
            ['slug' => $slug],
            ['name_en' => $nameEn, 'name_km' => $request->validated('name_km') ?: $nameEn],
        );

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode($category->wasRecentlyCreated ? 201 : 200);
    }
}
