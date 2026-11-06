<?php

namespace App\Events;

use App\Models\ProfileView;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired only on the FIRST view of a person on a given day — see
 * RecordProfileView. A repeat view writes a row but raises nothing, so the
 * owner is never told twice about one indecisive visitor.
 */
class ProfileViewed
{
    use Dispatchable, SerializesModels;

    public function __construct(public ProfileView $view) {}
}
