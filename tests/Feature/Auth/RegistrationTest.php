<?php

use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $component = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $this->travel(5)->seconds();

    $response = $component->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('home', absolute: false));

    $this->assertAuthenticated();
});

function fillRegistrationForm(string $name = 'Jane Doe', string $email = 'jane@example.com'): Testable
{
    return Volt::test('auth.register')
        ->set('name', $name)
        ->set('email', $email)
        ->set('password', 'password')
        ->set('password_confirmation', 'password');
}

test('bots that fill the hidden honeypot field are silently ignored', function () {
    $component = fillRegistrationForm()->set('website', 'http://spam.test');
    $this->travel(5)->seconds();
    $component->call('register')->assertRedirect(route('home', absolute: false));

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
    $this->assertGuest();
});

test('forms submitted instantly after loading are ignored', function () {
    fillRegistrationForm()->call('register');

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

test('random bot-generated names are rejected', function (string $name) {
    $component = fillRegistrationForm($name);
    $this->travel(5)->seconds();
    $component->call('register')->assertHasErrors(['name']);

    expect(User::count())->toBe(0);
})->with(['DdyaMTxVcRXkoaPci', 'dopwPTbSygdOwKAwUdXXu']);

test('real names with capitals are still accepted', function (string $name) {
    $component = fillRegistrationForm($name);
    $this->travel(5)->seconds();
    $component->call('register')->assertHasNoErrors();
})->with(['Mary McDonald', 'José García-López', "Sean O'Brien", 'Ana De La Cruz']);

test('only three accounts can be registered per IP each hour', function () {
    foreach (range(1, 4) as $attempt) {
        $component = fillRegistrationForm('Jane Doe', "jane{$attempt}@example.com");
        $this->travel(5)->seconds();
        $component->call('register');
        auth()->logout();
    }

    expect(User::count())->toBe(3);
});
