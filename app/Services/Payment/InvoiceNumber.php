<?php

namespace App\Services\Payment;

use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sequential, gap-free invoice numbers per Indian financial year (April–March).
 *
 * Gap-free is the requirement an accountant will actually check, which is why
 * the sequence is derived from a locked SELECT MAX inside the caller's
 * transaction rather than from a counter that a rolled-back payment would
 * silently consume.
 */
class InvoiceNumber
{
    public static function next(): string
    {
        $year = static::financialYear();

        return DB::transaction(function () use ($year) {
            $prefix = "INV-{$year}-";

            // FOR UPDATE on the index range: two payments verified in the same
            // millisecond must not be handed the same number.
            $last = Payment::query()
                ->where('invoice_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('invoice_number')
                ->value('invoice_number');

            $sequence = $last ? ((int) substr($last, -6)) + 1 : 1;

            return sprintf('%s%06d', $prefix, $sequence);
        });
    }

    /** The Indian financial year runs April to March; 2026-27 renders as 2026. */
    public static function financialYear(?\DateTimeInterface $at = null): string
    {
        $date = $at ? Carbon::instance($at) : now();

        return (string) ($date->month >= 4 ? $date->year : $date->year - 1);
    }
}
