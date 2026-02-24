<?php

namespace Tests\Integration\Http;

use App\Http\Controllers\PipelineRunController;
use App\Pipeline\LaravelPipelineFactory;
use Core\Repository\JobExecutionStore;
use Core\Job\Job;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineFactory;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $rateLimit = config('jobs.http.rate_limit', '60,1');

        $router->middleware("throttle:{$rateLimit}")->group(function () use ($router) {
            $router->post('/pipelines/{name}/run', [PipelineRunController::class, 'run']);
            $router->post('/api/v1/pipelines/{name}/run', [PipelineRunController::class, 'run']);
        });
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
        $app['config']->set('jobs.http.rate_limit', '2,1');
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
                'message' => null,
                'meta' => [],
            ]);
        $response->assertHeader('Deprecation', 'true')
            ->assertHeader('Sunset', '2026-12-31')
            ->assertHeader('Link', '</api/v1/pipelines/{name}/run>; rel="successor-version"');

        $this->assertDatabaseHas('jobs', [
            'id' => 'http-1',
            'pipeline_name' => 'http',
        ]);
    }

    public function test_versioned_pipeline_run_endpoint_dispatches_job(): void
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

        $response = $this->postJson('/api/v1/pipelines/http/run', [
            'job_id' => 'http-1-v1',
        ]);

        $response->assertOk()
            ->assertJson([
                'job_id' => 'http-1-v1',
                'status' => 'completed',
                'message' => null,
                'meta' => [],
            ]);
        $response->assertHeaderMissing('Deprecation')
            ->assertHeaderMissing('Sunset')
            ->assertHeaderMissing('Link');

        $this->assertDatabaseHas('jobs', [
            'id' => 'http-1-v1',
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
            ->assertJson([
                'code' => 'validation_error',
                'message' => 'Invalid request data',
            ])
            ->assertJsonPath('details.job_id.0', 'The job id field is required.');
    }

    public function test_pipeline_run_returns_404_for_unknown_pipeline(): void
    {
        $response = $this->postJson('/pipelines/unknown/run', [
            'job_id' => 'http-2',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'code' => 'pipeline_not_found',
                'message' => 'Unknown pipeline [unknown]',
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
                        return PipelineResult::failure(new PipelineError('X', 'boom'));
                }
            },
        ]);
    });

        $response = $this->postJson('/pipelines/http/run', [
            'job_id' => 'http-3',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'code' => 'job_conflict',
                'message' => 'boom',
            ]);
    }

    public function test_pipeline_run_returns_completed_on_duplicate_request(): void
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

        $this->postJson('/pipelines/http/run', [
            'job_id' => 'http-4',
        ])->assertOk();

        $response = $this->postJson('/pipelines/http/run', [
            'job_id' => 'http-4',
        ]);

        $response->assertOk()
            ->assertJson([
                'job_id' => 'http-4',
                'status' => 'completed',
                'message' => null,
                'meta' => [],
            ]);
    }

    public function test_pipeline_run_returns_conflict_when_job_is_locked(): void
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

        $store = $this->app->make(JobExecutionStore::class);
        $jobId = new JobId('http-5');
        $store->acquire($jobId);

        $response = $this->postJson('/pipelines/http/run', [
            'job_id' => 'http-5',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'code' => 'job_conflict',
                'message' => 'Job already running or completed',
            ]);
    }

    public function test_pipeline_run_is_rate_limited(): void
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

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-rl-1'])
            ->assertOk();
        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-rl-2'])
            ->assertOk();

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-rl-3'])
            ->assertStatus(429);
    }
}
