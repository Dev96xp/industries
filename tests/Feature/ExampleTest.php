<?php

test('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
test('the welcome page lists the trades we offer', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Our Trades')
        ->assertSeeInOrder(['Concrete Works', 'Roofing', 'Siding', 'Electrical', 'Plumbing', 'Framing', 'Drywall', 'Painting', 'AC & Heating']);
});
