<?php
namespace Tests\Feature;
use Tests\TestCase;
class HealthTest extends TestCase { public function test_api_root_is_available():void{$this->get('/')->assertOk()->assertJson(['status'=>'ok']);} }
