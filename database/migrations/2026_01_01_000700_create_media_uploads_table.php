<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The admin panel's media library.
     *
     * Before this table there was no way to get a file onto disk from the
     * admin panel at all — banner images, success-story photos and CMS OG
     * images were typed in as paths, which meant someone had to put the file
     * there by other means first.
     *
     * A table rather than listing the disk, for two reasons. A disk listing
     * cannot answer "who uploaded this and when", and every mutating admin
     * action is audited (docs/06 §7) — the audit row alone is not enough
     * because it is not queryable as a library. And S3 listings are paginated
     * and slow; this is the same paginated Eloquent index every other admin
     * module is, with no special case.
     *
     * The row is a *record of* the file, not the file's identity: `path` is
     * what gets copied into `banners.image_path` and friends, exactly as
     * before, so nothing downstream has a foreign key here and deleting a row
     * never orphans a banner. That is deliberate — see
     * `Web\Admin\MediaController::destroy`.
     */
    public function up(): void
    {
        Schema::create('media_uploads', function (Blueprint $table) {
            $table->id();

            // nullOnDelete, not cascade: the file outlives the staff account
            // that uploaded it, and a live banner must not lose its library
            // entry because someone left the company.
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('disk', 30);
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');

            // Read off the image at upload time. The library shows it so a
            // banner slot's aspect ratio can be checked before the page goes
            // live rather than after.
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            // Free-form, staff-supplied. Not a folder tree: a flat label is
            // enough to find last quarter's campaign art, and a tree is a
            // structure someone has to maintain.
            $table->string('collection', 40)->default('general');

            $table->timestamps();

            // The library's only listing order, newest first.
            $table->index(['collection', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_uploads');
    }
};
