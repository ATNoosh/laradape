<?php

namespace Tests\Feature;

use App\Models\UpgradeProfileModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpgradeProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profiles', $this->profilePayload('wordpress-legacy'));

        $response->assertRedirect(route('profiles.index'));
        $this->assertDatabaseHas('upgrade_profiles', ['name' => 'wordpress-legacy']);
    }

    public function test_dashboard_can_load_the_profile_catalog(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('profiles', 2)
            );
    }

    public function test_profile_names_reserved_for_built_in_profiles_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profiles', $this->profilePayload('conservative'))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('upgrade_profiles', ['name' => 'conservative']);
    }

    public function test_authenticated_user_can_delete_a_custom_profile(): void
    {
        $user = User::factory()->create();
        $profile = UpgradeProfileModel::create([
            'name' => 'temporary',
            'description' => 'Temporary profile',
            'configuration' => $this->profilePayload('temporary')['configuration'],
        ]);

        $this->actingAs($user)
            ->delete(route('profiles.destroy', $profile->name))
            ->assertRedirect(route('profiles.index'));

        $this->assertDatabaseMissing('upgrade_profiles', ['name' => 'temporary']);
    }

    /** @return array<string, mixed> */
    private function profilePayload(string $name): array
    {
        return [
            'name' => $name,
            'description' => 'Legacy project profile',
            'configuration' => [
                'engine' => 'hybrid',
                'scan_dirs' => ['src', 'wp-content'],
                'rector' => [
                    'mode' => 'auto',
                    'config_path' => null,
                    'exclude' => [],
                    'timeout_seconds' => 600,
                ],
                'exakat' => [
                    'mode' => 'auto',
                    'binary_path' => null,
                    'config_path' => null,
                    'analyzers' => [],
                    'timeout_seconds' => 1200,
                ],
                'ai' => [
                    'mode' => 'off',
                    'provider' => 'ollama',
                    'base_url' => 'http://127.0.0.1:11434',
                    'model' => 'qwen2.5-coder:1.5b',
                    'max_files_per_hop' => 5,
                ],
            ],
        ];
    }
}
