<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpgradeProfileRequest;
use App\Models\UpgradeProfileModel;
use App\Profiles\ProfileCatalog;
use App\Profiles\ProfileManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

final class UpgradeProfileController extends Controller
{
    public function index(ProfileCatalog $catalog): Response
    {
        return Inertia::render('Profiles/Index', [
            'profiles' => array_map(
                fn ($profile): array => $this->present($profile, $catalog->isBuiltIn($profile->name)),
                $catalog->all()
            ),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Profiles/Form', ['profile' => null]);
    }

    public function store(UpgradeProfileRequest $request, ProfileManager $manager): RedirectResponse
    {
        $manager->create($request->validated());

        return Redirect::route('profiles.index');
    }

    public function edit(UpgradeProfileModel $profile): Response
    {
        return Inertia::render('Profiles/Form', ['profile' => $this->presentModel($profile)]);
    }

    public function update(UpgradeProfileRequest $request, UpgradeProfileModel $profile, ProfileManager $manager): RedirectResponse
    {
        $manager->update($profile, $request->validated());

        return Redirect::route('profiles.index');
    }

    public function destroy(UpgradeProfileModel $profile, ProfileManager $manager): RedirectResponse
    {
        $manager->delete($profile);

        return Redirect::route('profiles.index');
    }

    /** @return array<string, mixed> */
    private function present(object $profile, bool $builtIn): array
    {
        return [
            'name' => $profile->name,
            'description' => $profile->description,
            'configuration' => $profile->configuration->toArray(),
            'built_in' => $builtIn,
        ];
    }

    /** @return array<string, mixed> */
    private function presentModel(UpgradeProfileModel $profile): array
    {
        return [
            'name' => (string) $profile->name,
            'description' => (string) ($profile->description ?? ''),
            'configuration' => $profile->configuration,
            'built_in' => false,
        ];
    }
}
