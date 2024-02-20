<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Statamic\Eloquent\Assets\AssetModel;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration {
    public function up()
    {
        Schema::table($this->prefix('assets_meta'), function (Blueprint $table) {
            $table->string('container')->after('handle')->index();
            $table->string('folder')->after('container')->index();
            $table->string('basename')->after('folder')->index();
            $table->string('filename')->after('basename')->index();
            $table->char('extension', 10)->after('filename')->index();
            $table->string('path')->after('extension')->index();
            $table->jsonb('meta')->after('path')->nullable();

            $table->index(['container', 'folder', 'basename']);
        });

        AssetModel::all()
            ->each(function ($model) {
                $path = Str::of($model->handle)->after('::')->replace('.meta/', '')->beforeLast('.yaml');

                if ($path->startsWith('./')) {
                    $path = $path->replaceFirst('./', '');
                }

                $model->container = Str::before($model->handle, '::');
                $model->path = $path;
                $model->folder = $path->contains('/') ? $path->beforeLast('/') : '/';
                $model->basename = $path->afterLast('/');
                $model->extension = Str::of($model->basename)->afterLast('.');
                $model->filename = Str::of($model->basename)->beforeLast('.');
                $model->meta = $model->data;
                $model->save();
            });

        Schema::table($this->prefix('assets_meta'), function (Blueprint $table) {
            $table->dropColumn('handle');
        });
    }

    public function down()
    {
        Schema::table($this->prefix('assets_meta'), function (Blueprint $table) {
            $table->string('handle')->index();
        });

        AssetModel::all()
            ->each(function ($model) {
                $model->handle = $model->container.'::'.$model->folder.'/.meta/'.$model->basename.'.yaml';
                $model->data = $model->meta;
                $model->saveQuietly();
            });

        Schema::table($this->prefix('assets_meta'), function (Blueprint $table) {
            $table->dropIndex(['container', 'folder', 'basename']);

            $table->dropColumn(['meta', 'path', 'basename', 'filename', 'extension', 'folder', 'container']);
        });
    }
};
