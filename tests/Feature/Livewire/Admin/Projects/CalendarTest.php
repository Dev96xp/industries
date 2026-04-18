<?php

use Livewire\Volt\Volt;

it('can render', function () {
    $component = Volt::test('admin.projects.calendar');

    $component->assertSee('');
});
