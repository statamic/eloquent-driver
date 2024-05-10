<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;
use Statamic\Eloquent\Forms\FormModel;
use Statamic\Eloquent\Forms\SubmissionModel;

return new class extends Migration {
    public function up()
    {
        Schema::table($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->string('form', 30)->nullable()->index()->after('id');
        });

        $forms = FormModel::all()->pluck('handle', 'id');

        SubmissionModel::all()
            ->each(function ($submission) use ($forms) {
                if ($form = $forms->get($submission->form_id)) {
                    $submission->form = $form;
                    $submission->save();
                }
            });

        Schema::table($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->dropForeign(['form_id']);
            $table->dropColumn('form_id');
        });
    }

    public function down()
    {
        Schema::table($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->unsignedBigInteger('form_id')->index();
        });

        $forms = FormModel::all()->pluck('handle', 'id');

        SubmissionModel::all()
            ->each(function ($submission) use ($forms) {
                if ($form = $forms->get($submission->form)) {
                    $submission->form_id = $form;
                    $submission->save();
                }
            });

        Schema::table($this->prefix('form_submissions'), function (Blueprint $table) {
            $table->dropColumn('form');
        });
    }
};
