<?php

use App\Models\User;
use App\Modules\Polls\Actions\SubmitVote;

it('requires sign-in for the account page', function () {
    $this->get('/konto')->assertRedirect('/anmelden');
    $this->get('/konto/sicherheit')->assertRedirect('/anmelden');
});

it('shows own polls and participations on the account page', function () {
    $user = User::factory()->create(['email' => 'erika@example.com']);
    createPoll(['title' => 'Meine eigene Umfrage'], $user);
    [$foreign] = createPoll(['title' => 'Fremde Umfrage', 'organizer_email' => 'max@example.com']);
    app(SubmitVote::class)->create($foreign, 'Erika', 'erika@example.com', []);

    $this->actingAs($user)->get('/konto')->assertOk()
        ->assertSee('Meine eigene Umfrage')
        ->assertSee('Fremde Umfrage');
});

it('updates the profile name', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/konto/profil', ['name' => 'Erika Mustermann'])->assertRedirect();

    expect($user->refresh()->name)->toBe('Erika Mustermann');
});

it('logs out', function () {
    $this->actingAs(User::factory()->create())->post('/abmelden')->assertRedirect('/');
    $this->assertGuest();
});

it('does not let users set internal fields via mass assignment', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/konto/profil', ['name' => 'Erika', 'is_admin' => true, 'blocked_at' => null]);

    expect($user->refresh()->is_admin)->toBeFalse();
});
