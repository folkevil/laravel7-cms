<?php

namespace Tests\Feature\Backend\User;

use App\Domains\Auth\Models\User;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Class DeactivateReactivateUserTest.
 */
class DeactivateReactivateUserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function only_a_user_with_correct_permissions_can_visit_deactivated_users()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $this->actingAs($user = factory(User::class)->create());

        $user->syncPermissions(['view backend', 'access.user.reactivate']);

        $this->get('/admin/auth/user/deactivated')->assertOk();

        $user->syncPermissions(['view backend']);

        $response = $this->get('/admin/auth/user/deactivated');

        $response->assertSessionHas('flash_danger', __('You do not have access to do that.'));
    }

    /** @test */
    public function a_user_with_the_correct_permissions_can_reactivate_a_user()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $this->actingAs($user = factory(User::class)->create());

        $user->syncPermissions(['view backend', 'access.user.reactivate']);

        $deactivatedUser = factory(User::class)->state('inactive')->create();

        $this->assertDatabaseHas('users', [
            'id' => $deactivatedUser->id,
            'active' => false,
        ]);

        $this->patch('/admin/auth/user/'.$deactivatedUser->id.'/mark/1');

        $this->assertDatabaseHas('users', [
            'id' => $deactivatedUser->id,
            'active' => true,
        ]);
    }

    /** @test */
    public function a_user_without_the_correct_permissions_can_not_reactivate_a_user()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $this->actingAs($user = factory(User::class)->create());

        $user->syncPermissions(['view backend']);

        $deactivatedUser = factory(User::class)->state('inactive')->create();

        $this->assertDatabaseHas('users', [
            'id' => $deactivatedUser->id,
            'active' => false,
        ]);

        $response = $this->patch('/admin/auth/user/'.$deactivatedUser->id.'/mark/1');

        $response->assertSessionHas('flash_danger', __('You do not have access to do that.'));

        $this->assertDatabaseHas('users', [
            'id' => $deactivatedUser->id,
            'active' => false,
        ]);
    }

    /** @test */
    public function a_user_with_the_correct_permissions_can_deactivate_a_user()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $this->actingAs($user = factory(User::class)->create());

        $user->syncPermissions(['view backend', 'access.user.deactivate']);

        $activeUser = factory(User::class)->state('active')->create();

        $this->assertDatabaseHas('users', [
            'id' => $activeUser->id,
            'active' => true,
        ]);

        $this->patch('/admin/auth/user/'.$activeUser->id.'/mark/0');

        $this->assertDatabaseHas('users', [
            'id' => $activeUser->id,
            'active' => false,
        ]);
    }

    /** @test */
    public function a_user_without_the_correct_permissions_can_not_deactivate_a_user()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $this->actingAs($user = factory(User::class)->create());

        $user->syncPermissions(['view backend']);

        $activeUser = factory(User::class)->state('active')->create();

        $this->assertDatabaseHas('users', [
            'id' => $activeUser->id,
            'active' => true,
        ]);

        $response = $this->patch('/admin/auth/user/'.$activeUser->id.'/mark/0');

        $response->assertSessionHas('flash_danger', __('You do not have access to do that.'));

        $this->assertDatabaseHas('users', [
            'id' => $activeUser->id,
            'active' => true,
        ]);
    }

    /** @test */
    public function a_user_can_not_deactivate_themselves()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $this->actingAs($user = factory(User::class)->create());

        $user->syncPermissions(['view backend', 'access.user.deactivate']);

        $response = $this->patch('/admin/auth/user/'.$user->id.'/mark/0');

        $response->assertSessionHas('flash_danger', __('You can not do that to yourself.'));
    }

    /** @test */
    public function a_user_can_not_deactivate_the_master_admin()
    {
        $this->withoutMiddleware(RequirePassword::class);

        $this->actingAs($user = factory(User::class)->create());

        $user->syncPermissions(['view backend', 'access.user.deactivate']);

        $response = $this->patch('/admin/auth/user/'.$this->getMasterAdmin()->id.'/mark/0');

        $response->assertSessionHas('flash_danger', __('You can not deactivate the administrator account.'));
    }
}
