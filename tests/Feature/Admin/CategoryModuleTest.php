<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('seeds every category and subcategory and can run twice without duplicating or overwriting', function () {
    $this->seed(CategorySeeder::class);

    $expectedCategories = count(CategorySeeder::PRODUCT_CATEGORIES) + count(CategorySeeder::CATEGORIES);
    $expectedSubcategories = collect(CategorySeeder::CATEGORIES)->sum(fn (array $category) => count($category['subcategories']));

    expect(Category::count())->toBe($expectedCategories)
        ->and(Subcategory::count())->toBe($expectedSubcategories);

    Category::where('slug', 'construction')->update(['name' => 'Obra civil', 'is_active' => false]);
    Subcategory::where('slug', 'roofing')->update(['name' => 'Techado']);

    $this->seed(CategorySeeder::class);

    expect(Category::count())->toBe($expectedCategories)
        ->and(Subcategory::count())->toBe($expectedSubcategories)
        ->and(Category::where('slug', 'construction')->first())
        ->name->toBe('Obra civil')
        ->is_active->toBeFalse()
        ->and(Subcategory::where('slug', 'roofing')->value('name'))->toBe('Techado');
});

it('keeps the original product categories first and the service categories after them', function () {
    $this->seed(CategorySeeder::class);

    expect(Category::orderBy('sort_order')->pluck('slug')->take(7)->all())
        ->toBe(['materials', 'labor', 'equipment', 'permits', 'other', 'construction', 'maintenance']);
});

it('does not duplicate an existing product category or detach its products', function () {
    $materials = Category::factory()->create(['name' => 'Materials', 'slug' => 'materials']);
    $product = Product::factory()->create(['category_id' => $materials->id]);

    $this->seed(CategorySeeder::class);

    expect(Category::where('name', 'Materials')->count())->toBe(1)
        ->and($product->fresh()->category_id)->toBe($materials->id);
});

it('returns the label in spanish or english depending on the locale', function () {
    $category = Category::factory()->create(['name' => 'Construcción', 'name_en' => 'Construction']);
    $subcategory = Subcategory::factory()->create(['name' => 'Techos', 'name_en' => 'Roofing']);
    $withoutEnglish = Category::factory()->create(['name' => 'Materials', 'name_en' => null]);

    app()->setLocale('en');
    expect($category->label())->toBe('Construction')
        ->and($subcategory->label())->toBe('Roofing')
        ->and($withoutEnglish->label())->toBe('Materials');

    app()->setLocale('es');
    expect($category->label())->toBe('Construcción')
        ->and($subcategory->label())->toBe('Techos');
});

it('creates a category with a unique slug', function () {
    Category::factory()->create(['slug' => 'construction']);

    Volt::actingAs($this->admin)
        ->test('admin.categories.create')
        ->set('name', 'Construcción')
        ->set('nameEn', 'Construction')
        ->set('sortOrder', 3)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::where('name', 'Construcción')->first())
        ->slug->toBe('construction-2')
        ->sort_order->toBe(3);
});

it('edits a category without changing its slug', function () {
    $category = Category::factory()->create(['name' => 'Old', 'slug' => 'old-slug']);

    Volt::actingAs($this->admin)
        ->test('admin.categories.edit', ['category' => $category])
        ->set('name', 'New name')
        ->set('isActive', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($category->fresh())
        ->name->toBe('New name')
        ->slug->toBe('old-slug')
        ->is_active->toBeFalse();
});

it('creates and edits a subcategory, moving it to another category', function () {
    $category = Category::factory()->create();
    $other = Category::factory()->create();

    Volt::actingAs($this->admin)
        ->test('admin.categories.subcategories.create', ['category' => $category])
        ->set('name', 'Techos')
        ->set('nameEn', 'Roofing')
        ->call('save')
        ->assertHasNoErrors();

    $subcategory = Subcategory::where('slug', 'roofing')->firstOrFail();
    expect($subcategory->category_id)->toBe($category->id);

    Volt::actingAs($this->admin)
        ->test('admin.categories.subcategories.edit', ['subcategory' => $subcategory])
        ->set('categoryId', $other->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($subcategory->fresh())
        ->category_id->toBe($other->id)
        ->slug->toBe('roofing');
});

it('toggles categories and subcategories active and inactive', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $subcategory = Subcategory::factory()->create(['category_id' => $category->id, 'is_active' => true]);

    Volt::actingAs($this->admin)
        ->test('admin.categories.index')
        ->call('toggleCategory', $category->id)
        ->call('toggleSubcategory', $subcategory->id);

    expect($category->fresh()->is_active)->toBeFalse()
        ->and($subcategory->fresh()->is_active)->toBeFalse();
});

it('does not delete a category that has subcategories or products', function () {
    $withSubcategories = Category::factory()->create();
    Subcategory::factory()->create(['category_id' => $withSubcategories->id]);

    $withProducts = Category::factory()->create();
    Product::factory()->create(['category_id' => $withProducts->id]);

    Volt::actingAs($this->admin)
        ->test('admin.categories.index')
        ->call('deleteCategory', $withSubcategories->id)
        ->call('deleteCategory', $withProducts->id);

    expect(Category::find($withSubcategories->id))->not->toBeNull()
        ->and(Category::find($withProducts->id))->not->toBeNull();
});

it('deletes an empty category and an unused subcategory', function () {
    $empty = Category::factory()->create();
    $subcategory = Subcategory::factory()->create();

    Volt::actingAs($this->admin)
        ->test('admin.categories.index')
        ->call('deleteCategory', $empty->id)
        ->call('deleteSubcategory', $subcategory->id);

    expect(Category::find($empty->id))->toBeNull()
        ->and(Subcategory::find($subcategory->id))->toBeNull();
});

it('redirects guests away from every category screen', function () {
    $category = Category::factory()->create();
    $subcategory = Subcategory::factory()->create(['category_id' => $category->id]);

    collect([
        route('admin.categories.index'),
        route('admin.categories.create'),
        route('admin.categories.edit', $category),
        route('admin.categories.subcategories.create', $category),
        route('admin.categories.subcategories.edit', $subcategory),
    ])->each(fn (string $url) => $this->get($url)->assertRedirect(route('login')));
});

it('shows subcategories in english first with the spanish name as reference', function () {
    app()->setLocale('en');
    $subcategory = Subcategory::factory()->create(['name' => 'Techos', 'name_en' => 'Roofing']);

    Volt::actingAs($this->admin)
        ->test('admin.categories.index')
        ->assertSeeInOrder(['Spanish', 'Roofing', 'Techos']);
});
