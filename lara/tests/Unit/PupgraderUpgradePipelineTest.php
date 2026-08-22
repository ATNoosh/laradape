<?php

namespace Tests\Unit;

use App\Pipeline\PupgraderUpgradePipeline;
use Core\Pipeline\PipelineContext;
use PHPUnit\Framework\TestCase;
use Pupgrader\UpgradeRunner;

final class PupgraderUpgradePipelineTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        $this->projectPath = sys_get_temp_dir() . '/laradape-pupgrader-' . uniqid();
        mkdir($this->projectPath . '/src', 0777, true);

        file_put_contents($this->projectPath . '/src/legacy.php', <<<'PHP'
<?php
$value = $text{0};
PHP);
        file_put_contents($this->projectPath . '/composer.json', json_encode([
            'require' => ['php' => '^7.4'],
        ]));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectPath);
    }

    public function test_executes_pupgrader_from_serializable_payload(): void
    {
        $pipeline = new PupgraderUpgradePipeline(
            payload: [
                'project_path' => $this->projectPath,
                'from' => '7.4',
                'to' => '8.0',
                'dry_run' => false,
                'options' => [
                    'engine' => 'regex',
                    'ai' => 'off',
                    'exakat' => 'off',
                ],
            ],
            runner: new UpgradeRunner()
        );

        $result = $pipeline->run(new PipelineContext());

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString(
            '$text[0]',
            file_get_contents($this->projectPath . '/src/legacy.php')
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
