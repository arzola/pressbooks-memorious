<?php

use PressbooksBeacon\Interfaces\MigrationInterface;

return new class implements MigrationInterface {
    public function up(): void
    {
        if (app('db')->schema()->hasTable('pressbooks_beacon_index_jobs')) {
            return;
        }

        app('db')->schema()->create('pressbooks_beacon_index_jobs', function ($table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('blog_id');
            $table->string('job_type', 30);
            $table->longText('payload')->nullable();
            $table->string('status', 20)->default('pending');
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->dateTime('processed_at')->nullable();
            $table->index('blog_id');
            $table->index('status');
            $table->index('created_at');
        });
    }
};
