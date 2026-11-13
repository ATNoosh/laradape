<?php

namespace Tests\Integration\Http;

use App\Http\Controllers\PipelineRunController;
use App\Http\Middleware\ApiKeyAuth;
use App\Http\Middleware\IdempotencyKey;
use App\Jobs\ExecuteQueuedJob;
use App\Http\Controllers\JobStatusController;
use App\Http\Controllers\UpgradeQueueController;
use App\Pipeline\LaravelPipelineFactory;
use Core\Repository\JobExecutionStore;
use Core\Pipeline\Pipeline;
use Core\Pipeline\PipelineContext;
use Core\Pipeline\PipelineError;
use Core\Pipeline\PipelineResult;
use Core\Pipeline\PipelineFactory;
use Core\ValueObject\JobId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

        $router->middleware([ApiKeyAuth::class, "throttle:{$rateLimit}", IdempotencyKey::class])->group(function () use ($router) {
            $router->post('/pipelines/{name}/run', [PipelineRunController::class, 'run']);
            $router->post('/pipelines/{name}/queue', [PipelineRunController::class, 'queue']);
            $router->post('/api/v1/pipelines/{name}/run', [PipelineRunController::class, 'run']);
            $router->post('/api/v1/pipelines/{name}/queue', [PipelineRunController::class, 'queue']);
            $router->get('/api/v1/jobs/{id}', [JobStatusController::class, 'show']);
            $router->get('/api/v1/jobs/{id}/chain', [JobStatusController::class, 'chain']);
            $router->post('/api/v1/jobs/{id}/cancel', [JobStatusController::class, 'cancel']);
            $router->post('/api/v1/upgrades/queue', [UpgradeQueueController::class, 'store']);
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
        $app['config']->set('jobs.http.api_key', 'secret');
        $app['config']->set('jobs.http.idempotency_ttl_seconds', 300);
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
        ], ['X-API-KEY' => 'secret']);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'job_id' => 'http-1',
                    'status' => 'completed',
                    'message' => null,
                    'meta' => [],
                ],
                'error' => null,
                'meta' => [],
            ]);
        $response->assertHeader('Deprecation', 'true')
            ->assertHeader('Sunset', '2026-12-31')
            ->assertHeader('Link', '</api/v1/pipelines/{name}/run>; rel="successor-version"');

        $this->assertDatabaseHas('execution_jobs', [
            'id' => 'http-1',
            'pipeline_name' => 'http',
        ]);
    }

    public function test_pipeline_queue_endpoint_persists_and_enqueues_job(): void
    {
        Queue::fake();

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

        $response = $this->postJson('/api/v1/pipelines/http/queue', [
            'job_id' => 'queued-1',
        ], ['X-API-KEY' => 'secret']);

        $response->assertStatus(202)
            ->assertJson([
                'data' => [
                    'job_id' => 'queued-1',
                    'status' => 'queued',
                    'message' => null,
                    'meta' => [],
                ],
                'error' => null,
                'meta' => [],
            ]);

        $this->assertDatabaseHas('execution_jobs', [
            'id' => 'queued-1',
            'pipeline_name' => 'http',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ExecuteQueuedJob::class, function (ExecuteQueuedJob $queued): bool {
            return $queued->jobId === 'queued-1';
        });
    }

    public function test_job_status_endpoint_returns_current_status(): void
    {
        Queue::fake();

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

        $this->postJson('/api/v1/pipelines/http/queue', [
            'job_id' => 'status-1',
        ], ['X-API-KEY' => 'secret'])->assertStatus(202);

        $this->getJson('/api/v1/jobs/status-1', [
            'X-API-KEY' => 'secret',
        ])->assertOk()->assertJson([
            'data' => [
                'job_id' => 'status-1',
                'status' => 'pending',
                'chain' => null,
            ],
            'error' => null,
        ]);
    }

    public function test_job_status_endpoint_returns_not_found_for_unknown_job(): void
    {
        $this->getJson('/api/v1/jobs/missing', [
            'X-API-KEY' => 'secret',
        ])->assertNotFound()->assertJson([
            'data' => null,
            'error' => [
                'code' => 'job_not_found',
            ],
        ]);
    }

    public function test_upgrade_queue_endpoint_validates_and_persists_upgrade_payload(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Queue::fake();

        $response = $this->postJson('/api/v1/upgrades/queue', [
            'job_id' => 'upgrade-queue-1',
            'project_path' => 'C:/projects/legacy-app',
            'from' => '7.4',
            'to' => '8.3',
            'dry_run' => true,
            'options' => [
                'engine' => 'regex',
                'ai' => 'off',
                'exakat' => 'off',
            ],
        ], ['X-API-KEY' => 'secret']);

        $response->assertStatus(202)->assertJson([
            'data' => [
                'job_id' => 'upgrade-queue-1',
                'status' => 'queued',
            ],
            'error' => null,
        ]);

        $this->assertDatabaseHas('execution_jobs', [
            'id' => 'upgrade-queue-1',
            'pipeline_name' => 'pupgrader.upgrade.hop',
            'status' => 'pending',
        ]);

        $payload = json_decode(
            (string) $this->app['db']->table('execution_jobs')->where('id', 'upgrade-queue-1')->value('payload'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('7.4', $payload['from']);
        $this->assertSame('8.3', $payload['to']);
        $this->assertTrue($payload['dry_run']);
        $this->assertSame('regex', $payload['options']['engine']);
        $this->assertSame('upgrade-queue-1', $payload['root_job_id']);
        $this->assertNull($payload['parent_job_id']);
        $this->assertSame(0, $payload['hop_index']);
        $this->assertSame(4, $payload['hop_count']);

        $this->getJson('/api/v1/jobs/upgrade-queue-1/chain', [
            'X-API-KEY' => 'secret',
        ])->assertOk()
            ->assertJsonPath('data.root_job_id', 'upgrade-queue-1')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.total_hops', 4)
            ->assertJsonPath('data.completed_hops', 0)
            ->assertJsonPath('data.current_hop', 0);

        $this->postJson('/api/v1/jobs/upgrade-queue-1/cancel', [], [
            'X-API-KEY' => 'secret',
        ])->assertStatus(202)
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancelled_hops', 1);

        $this->assertDatabaseHas('execution_jobs', [
            'id' => 'upgrade-queue-1',
            'status' => 'cancelled',
        ]);
    }

    public function test_upgrade_queue_endpoint_rejects_invalid_payload(): void
    {
        $this->postJson('/api/v1/upgrades/queue', [
            'job_id' => 'invalid-upgrade',
            'project_path' => 'C:/projects/legacy-app',
            'from' => '7.4',
            'to' => '8.3',
            'options' => ['engine' => 'unsupported'],
        ], ['X-API-KEY' => 'secret'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_error');
    }

    public function test_upgrade_queue_endpoint_applies_and_snapshots_profile(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Queue::fake();

        $this->postJson('/api/v1/upgrades/queue', [
            'job_id' => 'profile-queue-1',
            'project_path' => 'C:/projects/legacy-app',
            'from' => '7.4',
            'to' => '8.3',
            'profile' => 'conservative',
        ], ['X-API-KEY' => 'secret'])->assertStatus(202);

        $payload = json_decode(
            (string) $this->app['db']->table('execution_jobs')->where('id', 'profile-queue-1')->value('payload'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('conservative', $payload['profile']);
        $this->assertSame('hybrid', $payload['options']['engine']);
        $this->assertSame('off', $payload['options']['ai']);
        $this->assertSame('hybrid', $payload['profile_configuration']['engine']);
        $this->assertSame('off', $payload['profile_configuration']['ai']['mode']);
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
        ], ['X-API-KEY' => 'secret']);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'job_id' => 'http-1-v1',
                    'status' => 'completed',
                    'message' => null,
                    'meta' => [],
                ],
                'error' => null,
                'meta' => [],
            ]);
        $response->assertHeaderMissing('Deprecation')
            ->assertHeaderMissing('Sunset')
            ->assertHeaderMissing('Link');

        $this->assertDatabaseHas('execution_jobs', [
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

        $response = $this->postJson('/pipelines/http/run', [], ['X-API-KEY' => 'secret']);

        $response->assertStatus(422)
            ->assertJson([
                'data' => null,
                'error' => [
                    'code' => 'validation_error',
                    'message' => 'Invalid request data',
                ],
                'meta' => [],
            ])
            ->assertJsonPath('error.details.job_id.0', 'The job id field is required.');
    }

    public function test_pipeline_run_returns_404_for_unknown_pipeline(): void
    {
        $response = $this->postJson('/pipelines/unknown/run', [
            'job_id' => 'http-2',
        ], ['X-API-KEY' => 'secret']);

        $response->assertStatus(404)
            ->assertJson([
                'data' => null,
                'error' => [
                    'code' => 'pipeline_not_found',
                    'message' => 'Unknown pipeline [unknown]',
                ],
                'meta' => [],
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
        ], ['X-API-KEY' => 'secret']);

        $response->assertStatus(409)
            ->assertJson([
                'data' => null,
                'error' => [
                    'code' => 'job_conflict',
                    'message' => 'boom',
                ],
                'meta' => [],
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
        ], ['X-API-KEY' => 'secret'])->assertOk();

        $response = $this->postJson('/pipelines/http/run', [
            'job_id' => 'http-4',
        ], ['X-API-KEY' => 'secret']);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'job_id' => 'http-4',
                    'status' => 'completed',
                    'message' => null,
                    'meta' => [],
                ],
                'error' => null,
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
        ], ['X-API-KEY' => 'secret']);

        $response->assertStatus(409)
            ->assertJson([
                'data' => null,
                'error' => [
                    'code' => 'job_conflict',
                    'message' => 'Job already running or completed',
                ],
                'meta' => [],
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

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-rl-1'], ['X-API-KEY' => 'secret'])
            ->assertOk();
        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-rl-2'], ['X-API-KEY' => 'secret'])
            ->assertOk();

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-rl-3'], ['X-API-KEY' => 'secret'])
            ->assertStatus(429);
    }

    public function test_pipeline_run_requires_api_key(): void
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

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-auth-1'])
            ->assertStatus(401)
            ->assertJson([
                'data' => null,
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid API key',
                ],
                'meta' => [],
            ]);

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-auth-2'], ['X-API-KEY' => 'wrong'])
            ->assertStatus(401)
            ->assertJson([
                'data' => null,
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid API key',
                ],
                'meta' => [],
            ]);
    }

    public function test_pipeline_run_idempotency_key_replays_response(): void
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

        $headers = [
            'X-API-KEY' => 'secret',
            'Idempotency-Key' => 'abc-123',
        ];

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-idem-1'], $headers)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'job_id' => 'http-idem-1',
                    'status' => 'completed',
                ],
                'error' => null,
                'meta' => [],
            ]);

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-idem-2'], $headers)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'job_id' => 'http-idem-1',
                    'status' => 'completed',
                ],
                'error' => null,
                'meta' => [],
            ]);
    }

    public function test_middleware_stack_rejects_rate_limit_before_idempotency_replay(): void
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

        $headers = [
            'X-API-KEY' => 'secret',
            'Idempotency-Key' => 'stack-1',
        ];

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-stack-1'], $headers)
            ->assertOk();
        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-stack-2'], $headers)
            ->assertOk();

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-stack-3'], $headers)
            ->assertStatus(429);
    }

    public function test_middleware_stack_requires_api_key_before_throttle(): void
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

        $this->postJson('/api/v1/pipelines/http/run', ['job_id' => 'http-stack-4'])
            ->assertStatus(401)
            ->assertJson([
                'data' => null,
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Invalid API key',
                ],
                'meta' => [],
            ]);
    }
}
