<?php

use App\Filament\Resources\Polls\Pages\ManagePolls;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Modules\Accounts\Http\Middleware\EnsurePasskeySession;
use App\Modules\Polls\Models\Poll;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin)->withSession([EnsurePasskeySession::SESSION_KEY => time()]);
});

it('lists users and polls', function () {
    $user = User::factory()->create(['email' => 'max@example.com']);
    createPoll(['title' => 'Sommerfest']);

    Livewire::test(ManageUsers::class)->assertCanSeeTableRecords([$user]);
    Livewire::test(ManagePolls::class)->assertSee('Sommerfest');
});

it('blocks and unblocks users', function () {
    $user = User::factory()->create();

    Livewire::test(ManageUsers::class)->callAction(TestAction::make('block')->table($user));
    expect($user->refresh()->isBlocked())->toBeTrue();

    Livewire::test(ManageUsers::class)->callAction(TestAction::make('block')->table($user));
    expect($user->refresh()->isBlocked())->toBeFalse();
});

it('grants admin rights', function () {
    $user = User::factory()->create();

    Livewire::test(ManageUsers::class)->callAction(TestAction::make('admin')->table($user));

    expect($user->refresh()->is_admin)->toBeTrue();
});

it('deletes polls', function () {
    [$poll] = createPoll();

    Livewire::test(ManagePolls::class)->callAction(TestAction::make('delete')->table($poll));

    expect(Poll::query()->count())->toBe(0);
});

it('does not let normal users use admin pages', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/benutzer')->assertForbidden();
    $this->get('/admin/umfragen')->assertForbidden();
    $this->get('/admin/protokoll')->assertForbidden();
});

it('policies deny non-admins', function () {
    $user = User::factory()->create();
    [$poll] = createPoll();

    expect($user->can('viewAny', User::class))->toBeFalse()
        ->and($user->can('delete', $poll))->toBeFalse()
        ->and($this->admin->can('delete', $poll))->toBeTrue()
        ->and($this->admin->can('create', User::class))->toBeFalse()
        ->and($this->admin->can('delete', $this->admin))->toBeFalse();
});
