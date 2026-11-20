<?php

use App\Models\Category;
use App\Models\Contractor;
use App\Models\Subcategory;
use App\Models\User;
use Livewire\Volt\Volt;

it('links the company name to the edit page and hides status and actions on mobile', function () {
    $contractor = Contractor::factory()->create(['company_name' => 'Acme Roofing', 'contact_name' => 'Joe Builder', 'phone' => '(308) 555-0100']);

    $html = Volt::actingAs(User::factory()->create())
        ->test('admin.contractors.index')
        ->assertSee('Acme Roofing')
        ->html();

    expect($html)->toContain('href="'.route('admin.contractors.edit', $contractor).'"')
        ->and(substr_count($html, 'Joe Builder'))->toBe(3)
        ->and($html)->toContain('href="tel:3085550100"')
        ->and(substr_count($html, 'px-4 py-3 text-zinc-500 sm:table-cell'))->toBeGreaterThanOrEqual(2);
});

it('still toggles status and deletes contractors on larger screens', function () {
    $contractor = Contractor::factory()->create(['is_active' => true]);

    $component = Volt::actingAs(User::factory()->create())->test('admin.contractors.index');

    $component->call('toggleActive', $contractor->id);
    expect($contractor->fresh()->is_active)->toBeFalse();

    $component->call('deleteContractor', $contractor->id);
    expect(Contractor::find($contractor->id))->toBeNull();
});

it('assigns several subcategories when creating and editing a contractor', function () {
    $roofing = Subcategory::factory()->create(['name_en' => 'Roofing']);
    $siding = Subcategory::factory()->create(['category_id' => $roofing->category_id, 'name_en' => 'Siding']);
    $plumbing = Subcategory::factory()->create(['name_en' => 'Plumbing']);

    Volt::actingAs(User::factory()->create())
        ->test('admin.contractors.create')
        ->set('companyName', 'Top Roofs LLC')
        ->set('subcategoryIds', [(string) $roofing->id, (string) $siding->id])
        ->call('save')
        ->assertHasNoErrors();

    $contractor = Contractor::where('company_name', 'Top Roofs LLC')->firstOrFail();
    expect($contractor->subcategories->pluck('id')->sort()->values()->all())->toBe([$roofing->id, $siding->id]);

    Volt::actingAs(User::factory()->create())
        ->test('admin.contractors.edit', ['contractor' => $contractor])
        ->assertSet('subcategoryIds', [(string) $roofing->id, (string) $siding->id])
        ->set('subcategoryIds', [(string) $plumbing->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($contractor->fresh()->subcategories->pluck('id')->all())->toBe([$plumbing->id]);
});

it('filters contractors by category and then by subcategory', function () {
    $construction = Category::factory()->create(['name_en' => 'Construction']);
    $roofing = Subcategory::factory()->create(['category_id' => $construction->id, 'name_en' => 'Roofing']);
    $siding = Subcategory::factory()->create(['category_id' => $construction->id, 'name_en' => 'Siding']);
    $auto = Subcategory::factory()->create(['name_en' => 'Towing']);

    Contractor::factory()->create(['company_name' => 'Roof Co'])->subcategories()->attach($roofing);
    Contractor::factory()->create(['company_name' => 'Side Co'])->subcategories()->attach($siding);
    Contractor::factory()->create(['company_name' => 'Tow Co'])->subcategories()->attach($auto);

    Volt::actingAs(User::factory()->create())
        ->test('admin.contractors.index')
        ->set('categoryFilter', (string) $construction->id)
        ->assertViewHas('contractors', fn ($list) => $list->pluck('company_name')->all() === ['Roof Co', 'Side Co'])
        ->set('subcategoryFilter', (string) $roofing->id)
        ->assertViewHas('contractors', fn ($list) => $list->pluck('company_name')->all() === ['Roof Co'])
        ->set('categoryFilter', '')
        ->assertSet('subcategoryFilter', '')
        ->assertViewHas('contractors', fn ($list) => $list->count() === 3);
});

it('blocks deleting a subcategory that is assigned to a contractor', function () {
    $subcategory = Subcategory::factory()->create();
    Contractor::factory()->create()->subcategories()->attach($subcategory);

    Volt::actingAs(User::factory()->create())
        ->test('admin.categories.index')
        ->call('deleteSubcategory', $subcategory->id);

    expect(Subcategory::find($subcategory->id))->not->toBeNull();
});

it('keeps an inactive subcategory selectable when the contractor already has it', function () {
    $inactive = Subcategory::factory()->create(['name_en' => 'Old Trade', 'is_active' => false]);

    expect(Category::forSubcategoryPicker()->flatMap->subcategories->pluck('id'))->not->toContain($inactive->id)
        ->and(Category::forSubcategoryPicker([$inactive->id])->flatMap->subcategories->pluck('id'))->toContain($inactive->id);
});

it('groups contractors by category and subcategory for the mobile browser', function () {
    $construction = Category::factory()->create(['name_en' => 'Construction', 'slug' => 'construction']);
    $roofing = Subcategory::factory()->create(['category_id' => $construction->id, 'name_en' => 'Roofing']);
    $siding = Subcategory::factory()->create(['category_id' => $construction->id, 'name_en' => 'Siding']);

    $both = Contractor::factory()->create(['company_name' => 'Both Co']);
    $both->subcategories()->attach([$roofing->id, $siding->id]);
    Contractor::factory()->create(['company_name' => 'Roof Only'])->subcategories()->attach($roofing);
    Contractor::factory()->create(['company_name' => 'No Trade Co']);

    $component = Volt::actingAs(User::factory()->create())->test('admin.contractors.index');

    $component->assertViewHas('browseTree', function ($tree) use ($construction, $roofing) {
        $group = $tree->firstWhere('category.id', $construction->id);
        $roofingGroup = $group['subcategories']->firstWhere('subcategory.id', $roofing->id);

        return $group['count'] === 2
            && $group['subcategories']->count() === 2
            && $roofingGroup['contractors']->pluck('company_name')->all() === ['Both Co', 'Roof Only'];
    })
        ->assertViewHas('unclassified', fn ($unclassified) => $unclassified->pluck('company_name')->all() === ['No Trade Co'])
        ->assertSee('🏗️', false)
        ->assertSee('Unclassified');
});

it('skips the mobile browser while searching', function () {
    Contractor::factory()->create(['company_name' => 'Searchable Co']);

    Volt::actingAs(User::factory()->create())
        ->test('admin.contractors.index')
        ->set('search', 'Searchable')
        ->assertViewHas('browseTree', fn ($tree) => $tree->isEmpty())
        ->assertSee('Searchable Co');
});
