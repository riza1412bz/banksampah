<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Halaman depan mengarahkan tamu ke halaman masuk. */
    public function test_halaman_depan_mengarahkan_tamu_ke_masuk(): void
    {
        $this->get('/')->assertRedirect(route('masuk'));
    }
}
