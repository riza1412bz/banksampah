<?php

namespace Tests;

use App\Models\KategoriSampah;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Bersihkan memo harga aktif antar-test (RefreshDatabase rollback tidak
        // mereset static property sehingga kategori id reuse bisa salah).
        KategoriSampah::lupakanSemuaMemoHarga();
    }

    protected function tearDown(): void
    {
        KategoriSampah::lupakanSemuaMemoHarga();
        parent::tearDown();
    }
}
