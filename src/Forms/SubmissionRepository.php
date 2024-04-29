<?php

namespace Statamic\Eloquent\Forms;

use Statamic\Contracts\Forms\Submission as SubmissionContract;
use Statamic\Contracts\Forms\SubmissionQueryBuilder as SubmissionQueryBuilderContract;
use Statamic\Stache\Repositories\SubmissionRepository as StacheRepository;

class SubmissionRepository extends StacheRepository
{
    public function save($submission)
    {
        $model = $submission->toModel();
        $model->save();

        $submission->model($submission->fresh());
    }

    public function delete($submission)
    {
        $submission->model()->delete();
    }

    public static function bindings(): array
    {
        return [
            SubmissionContract::class => Submission::class,
            SubmissionQueryBuilderContract::class => SubmissionQueryBuilder::class,
        ];
    }
}
