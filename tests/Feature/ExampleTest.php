<?php
namespace Tests\Feature;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
class ExampleTest extends TestCase {
    #[Test] public function application_boots(): void { $this->get('/login')->assertStatus(200); }
}
