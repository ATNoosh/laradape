<?php

use PHPUnit\Framework\TestCase;
use Core\ValueObject\JobId;

final class JobIdTest extends TestCase
{
    public function test_job_id_cannot_be_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new JobId('');
    }

    public function test_job_id_accepts_valid_value(): void
    {
        $id = new JobId('abc-123');

        $this->assertSame('abc-123', $id->value());
    }
}
