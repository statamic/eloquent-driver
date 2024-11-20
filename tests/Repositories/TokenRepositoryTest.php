<?php

namespace Tests\Repositories;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Statamic\Contracts\Tokens\Token;
use Statamic\Eloquent\Tokens\TokenRepository;
use Tests\TestCase;

class TokenRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = new TokenRepository;

        $this->repo->make('abc', 'ExampleHandler', ['foo' => 'bar'])->save();
    }

    #[Test]
    public function it_gets_a_token()
    {
        tap($this->repo->find('abc'), function ($token) {
            $this->assertInstanceOf(Token::class, $token);
            $this->assertEquals('abc', $token->token());
            $this->assertEquals('ExampleHandler', $token->handler());
            $this->assertEquals(['foo' => 'bar'], $token->data()->all());
            $this->assertInstanceOf(Carbon::class, $token->expiry());
        });

        $this->assertNull($this->repo->find('unknown'));
    }

    #[Test]
    public function it_saves_a_token_to_the_database()
    {
        $token = $this->repo->make('new', 'ExampleHandler', ['foo' => 'bar']);

        $this->assertNull($this->repo->find('new'));

        $this->repo->save($token);

        $this->assertNotNull($item = $this->repo->find('new'));
        $this->assertEquals(['foo' => 'bar'], $item->data()->all());
    }
}
