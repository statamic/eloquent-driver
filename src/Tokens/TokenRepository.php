<?php

namespace Statamic\Eloquent\Tokens;

use Statamic\Contracts\Tokens\Token as TokenContract;
use Statamic\Tokens\TokenRepository as FileTokenRepository;

class TokenRepository extends FileTokenRepository
{
    public function make(?string $token, string $handler, array $data = []): Token
    {
        return new Token($token, $handler, $data);
    }

    public function find($token)
    {
        $model = app('statamic.eloquent.tokens.model')::whereToken($token)->first();

        if (! $model) {
            return;
        }

        return Token::fromModel($model);
    }

    public function save($entry)
    {
        $model = $entry->toModel();
        $model->save();

        $entry->model($model->fresh());
    }

    public function delete($entry)
    {
        $entry->model()->delete();
    }

    public function collectGarbage()
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
