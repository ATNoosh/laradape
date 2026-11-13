<?php

namespace App\Pipeline;

use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\Pipeline;
use Pupgrader\Pipeline\UpgradePipelineFactory;
use Pupgrader\Context\UpgradeContextKeys;
use Pupgrader\UpgradeOptions;
use Pupgrader\Version\PhpVersion;
use Pupgrader\Version\UpgradePath;
use Pupgrader\Reporting\NullProgressReporter;
use Pupgrader\Reporting\ProgressReporter;

final class PupgraderUpgradeHopPipeline implements PayloadAwarePipeline
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        private array $payload,
        private UpgradePipelineFactory $factory,
        private ProgressReporter $progressReporter = new NullProgressReporter(),
    ) {}

    public function name(): string
    {
        return 'pupgrader.upgrade.hop';
    }

    public function withPayload(array $payload): self
    {
        return new self($payload, $this->factory, $this->progressReporter);
    }

    public function run(PipelineContext $context): PipelineResult
    {
        try {
            $context->set(UpgradeContextKeys::EXECUTION_JOB_ID, (string) ($this->payload['execution_job_id'] ?? ''));
            $context->set(UpgradeContextKeys::PROGRESS_REPORTER, $this->progressReporter);
            $from = PhpVersion::parse($this->requiredString('from'));
            $to = PhpVersion::parse($this->requiredString('to'));
            $hops = UpgradePath::between($from, $to);
            $index = (int) ($this->payload['hop_index'] ?? -1);

            if (! isset($hops[$index])) {
                throw new \InvalidArgumentException("Invalid upgrade hop index [{$index}]");
            }

            $pipeline = $this->factory->createHop(
                projectPath: $this->requiredString('project_path'),
                hop: $hops[$index],
                dryRun: (bool) ($this->payload['dry_run'] ?? false),
                options: $this->options(),
                prepareWorkspace: $index === 0,
                finalize: $index === count($hops) - 1,
            );

            return $pipeline->run($context);
        } catch (\Throwable $exception) {
            return PipelineResult::failure(new PipelineError(
                'pupgrader_hop_failed',
                $exception->getMessage()
            ));
        }
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
        $options = is_array($this->payload['options'] ?? null) ? $this->payload['options'] : [];

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
