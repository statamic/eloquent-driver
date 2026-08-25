<?php

namespace Statamic\Eloquent\Tokens;

use Illuminate\Support\Collection;
use Statamic\Contracts\Tokens\Token as TokenContract;
use Statamic\Tokens\TokenRepository as Repository;

class TokenRepository extends Repository
{
    public function all(): Collection
    {
        return app('statamic.eloquent.tokens.model')::query()
            ->get()
            ->map(fn ($model) => Token::fromModel($model))
            ->values();
    }

    public function find(string $token): ?Token
    {
        $model = app('statamic.eloquent.tokens.model')::whereToken($token)->first();

        if (! $model) {
            return null;
        }

        return Token::fromModel($model);
    }

    public function save(TokenContract $token): bool
    {
        return $token->toModel()->save();
    }

    public function delete(TokenContract $token): bool
    {
        return $token->toModel()->delete();
    }

    public function collectGarbage(): void
    {
        app('statamic.eloquent.tokens.model')::where('expire_at', '<', now())->delete();
    }

    public static function bindings(): array
    {
        return [
            TokenContract::class => Token::class,
        ];
    }
}
