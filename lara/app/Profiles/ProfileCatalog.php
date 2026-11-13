<?php

namespace App\Profiles;

use App\Models\UpgradeProfileModel;
use Pupgrader\Configuration\UpgradeConfiguration;
use Pupgrader\Configuration\UpgradeProfile;
use Pupgrader\Configuration\UpgradeProfileCatalog;
use RuntimeException;

final class ProfileCatalog
{
    public function __construct(
        private UpgradeProfileCatalog $builtInProfiles = new UpgradeProfileCatalog(),
    ) {}

    /** @return list<UpgradeProfile> */
    public function all(): array
    {
        $profiles = $this->builtInProfiles->all();

        foreach (UpgradeProfileModel::query()->orderBy('name')->get() as $model) {
            $profiles[] = $this->toDomain($model);
        }

        return $profiles;
    }

    public function find(string $name): UpgradeProfile
    {
        foreach ($this->all() as $profile) {
            if ($profile->name === $name) {
                return $profile;
            }
        }

        throw new RuntimeException("Upgrade profile not found: {$name}");
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_map(
            static fn (UpgradeProfile $profile): string => $profile->name,
            $this->all()
        );
    }

    public function isBuiltIn(string $name): bool
    {
        return in_array($name, $this->builtInProfiles->names(), true);
    }

    private function toDomain(UpgradeProfileModel $model): UpgradeProfile
    {
        return new UpgradeProfile(
            name: (string) $model->name,
            description: (string) ($model->description ?? ''),
            configuration: UpgradeConfiguration::fromArray($model->configuration ?? []),
        );
    }
}
