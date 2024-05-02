<?php

namespace Statamic\Eloquent\Tokens;

use Illuminate\Database\Eloquent\Model;
use Statamic\Contracts\Tokens\Token as Contract;
use Statamic\Tokens\Token as AbstractToken;

class Token extends AbstractToken
{
    protected $model;

    public static function fromModel(Model $model)
    {
        return (new static($model->token, $model->handler, $model->data))
            ->expireAt($model->expire_at)
            ->model($model);
    }

    public function toModel()
    {
        return self::makeModelFromContract($this);
    }

    public static function makeModelFromContract(Contract $source)
    {
        $class = app('statamic.eloquent.tokens.model');

        return $class::firstOrNew(['token' => $source->token()])->fill([
            'handler'   => $source->handler(),
            'data'      => $source->data(),
            'expire_at' => $source->expiry(),
        ]);
    }

    public function model($model = null)
    {
        if (func_num_args() === 0) {
            return $this->model;
        }

        $this->model = $model;

        return $this;
    }
}
