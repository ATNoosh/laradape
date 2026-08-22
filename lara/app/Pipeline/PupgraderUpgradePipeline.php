<?php

namespace App\Pipeline;

use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use Core\Pipeline\PipelineResult;
use Pupgrader\UpgradeOptions;
use Pupgrader\UpgradeRunner;
use Pupgrader\Version\PhpVersion;

final class PupgraderUpgradePipeline implements PayloadAwarePipeline
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        private array $payload,
        private UpgradeRunner $runner
    ) {}

    public function name(): string
    {
        return 'pupgrader.upgrade';
    }

    public function withPayload(array $payload): self
    {
        return new self($payload, $this->runner);
    }

    public function run(PipelineContext $context): PipelineResult
    {
        try {
            $result = $this->runner->run(
                projectPath: $this->requiredString('project_path'),
                from: PhpVersion::parse($this->requiredString('from')),
                to: PhpVersion::parse($this->requiredString('to')),
                dryRun: (bool) ($this->payload['dry_run'] ?? false),
                options: $this->options()
            );
        } catch (\Throwable $exception) {
            return PipelineResult::failure(new PipelineError(
                'pupgrader_execution_failed',
                $exception->getMessage()
            ));
        }

        if (! $result->success) {
            return PipelineResult::failure(new PipelineError(
                'pupgrader_upgrade_failed',
                $result->errorMessage ?? 'Pupgrader upgrade failed'
            ));
        }

        $context->set('pupgrader_result', $result);

        return PipelineResult::success([
            'engine' => $result->engine,
            'changes' => $result->upgradeReport->changeCount(),
            'hops' => count($result->hops),
        ]);
    }

    private function requiredString(string $key): string
    {
        $value = $this->payload[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new \InvalidArgumentException("Missing upgrade payload field [{$key}]");
        }

        return $value;
    }

    private function options(): UpgradeOptions
    {
        $options = is_array($this->payload['options'] ?? null)
            ? $this->payload['options']
            : [];

        return new UpgradeOptions(
            engine: (string) ($options['engine'] ?? 'auto'),
            rectorMode: (string) ($options['rector_mode'] ?? 'auto'),
            ai: (string) ($options['ai'] ?? 'auto'),
            exakat: (string) ($options['exakat'] ?? 'auto'),
            ollamaUrl: (string) ($options['ollama_url'] ?? 'http://127.0.0.1:11434'),
            ollamaModel: (string) ($options['ollama_model'] ?? 'qwen2.5-coder:1.5b'),
            aiMaxFilesPerHop: (int) ($options['ai_max_files_per_hop'] ?? 5),
            scanDirs: is_array($options['scan_dirs'] ?? null) ? $options['scan_dirs'] : ['src', 'app', 'lib', 'modules'],
            outputPath: isset($options['output_path']) ? (string) $options['output_path'] : null,
            forceOverwrite: (bool) ($options['force_overwrite'] ?? false),
        );
    }
}
