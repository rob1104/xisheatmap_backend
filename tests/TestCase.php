<?php

namespace Tests;

use App\Services\ListaNominalImportService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Stubs\ListaNominalImportServiceStub;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if (! class_exists(ListaNominalImportService::class)) {
            class_alias(ListaNominalImportServiceStub::class, ListaNominalImportService::class);
        }

        parent::setUp();
    }
}
