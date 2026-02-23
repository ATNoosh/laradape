<?php

namespace Tests\Integration\Http;

use App\Http\Controllers\PipelineRunController;
use App\Pipeline\LaravelPipelineFactory;
use Core\Execution\JobRepository;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineFactory;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase;

final class PipelineRunControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../../../database/migrations');
    }

    protected function getPackageProviders($app)
    {
        return [
            \App\Providers\ExecutionServiceProvider::class,
            \App\Providers\PipelineServiceProvider::class,
        ];
    }

    protected function defineRoutes($router): void
    {
        $router->post('/pipelines/{name}/run', [PipelineRunController::class, 'run']);
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function test_pipeline_run_endpoint_dispatches_job(): void
    {
        $this->app->singleton(PipelineFactory::class, function () {
            return new LaravelPipelineFactory([
                'http' => fn () => new class implements Pipeline {
                    public function name(): string
                    {
                        return 'http';
                    }

                    public function run(PipelineContext $context): PipelineResult
                    {
                        return PipelineResult::success();
                    }
                },
            ]);
        });

        $response = $this->postJson('/pipelines/http/run', [
            'job_id' => 'http-1',
        ]);

        $response->assertOk()
            ->assertJson([
                'job_id' => 'http-1',
                'status' => 'completed',
            ]);

        $this->assertDatabaseHas('jobs', [
            'id' => 'http-1',
            'pipeline_name' => 'http',
        ]);
    }

    public function test_pipeline_run_requires_job_id(): void
    {
        $this->app->singleton(PipelineFactory::class, function () {
            return new LaravelPipelineFactory([
                'http' => fn () => new class implements Pipeline {
                    public function name(): string
                    {
                        return 'http';
                    }

                    public function run(PipelineContext $context): PipelineResult
                    {
                        return PipelineResult::success();
                    }
                },
            ]);
        });

        $response = $this->postJson('/pipelines/http/run', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['job_id']);
    }

    public function test_pipeline_run_returns_404_for_unknown_pipeline(): void
    {
        $response = $this->postJson('/pipelines/unknown/run', [
            'job_id' => 'http-2',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Unknown pipeline [unknown]',
            ]);
    }

    public function test_pipeline_run_returns_conflict_on_failure(): void
    {
        $this->app->singleton(PipelineFactory::class, function () {
            return new LaravelPipelineFactory([
                'http' => fn () => new class implements Pipeline {
                    public function name(): string
                    {
                        return 'http';
                    }

                    public function run(PipelineContext $context): PipelineResult
                    {
                        return PipelineResult::failure(new \Core\Pipeline\PipelineError('X', 'boom'));
                    }
                },
            ]);
        });

        $response = $this->postJson('/pipelines/http/run', [
            'job_id' => 'http-3',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'job_id' => 'http-3',
                'status' => 'failed',
            ]);
    }
}
