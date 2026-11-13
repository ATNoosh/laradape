<?php

namespace App\Profiles;

use App\Models\UpgradeProfileModel;
use Pupgrader\Configuration\UpgradeConfiguration;
use Pupgrader\Configuration\UpgradeProfile;
use RuntimeException;

final class ProfileManager
{
    public function __construct(private ProfileCatalog $catalog) {}

    /** @param array<string, mixed> $data */
    public function create(array $data): UpgradeProfileModel
    {
        $profile = $this->validatedProfile($data);
        if (in_array($profile->name, $this->catalog->names(), true)) {
            throw new RuntimeException("Upgrade profile name already exists: {$profile->name}");
        }

        return UpgradeProfileModel::create([
            'name' => $profile->name,
            'description' => $profile->description,
            'configuration' => $profile->configuration->toArray(),
        ]);
    }

    /** @param array<string, mixed> $data */
    public function update(UpgradeProfileModel $model, array $data): UpgradeProfileModel
    {
        $profile = $this->validatedProfile($data);
        if ($profile->name !== $model->name && in_array($profile->name, $this->catalog->names(), true)) {
            throw new RuntimeException("Upgrade profile name already exists: {$profile->name}");
        }

        $model->update([
            'name' => $profile->name,
            'description' => $profile->description,
            'configuration' => $profile->configuration->toArray(),
        ]);

        return $model->refresh();
    }

    public function delete(UpgradeProfileModel $model): void
    {
        $model->delete();
    }

    /** @param array<string, mixed> $data */
    private function validatedProfile(array $data): UpgradeProfile
    {
        return new UpgradeProfile(
            name: (string) $data['name'],
            description: (string) ($data['description'] ?? ''),
            configuration: UpgradeConfiguration::fromArray((array) $data['configuration'])
        );
    }
}
