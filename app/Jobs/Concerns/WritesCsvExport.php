<?php

namespace App\Jobs\Concerns;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Storage;

/**
 * The shared half of every export job: stream rows to a CSV on the private
 * disk, audit it, tell the requester.
 *
 * Streamed through a php://temp handle rather than concatenated into a string.
 * A 128,000-row export built by string concatenation is the whole file in
 * memory twice — once as the string and once as whatever `put()` copies it
 * into — and it dies at exactly the size where the export starts to matter.
 * php://temp spills to disk past a couple of megabytes and costs nothing
 * below that.
 *
 * The private disk is not negotiable. These files are the most damaging
 * artefacts the system produces; a public URL to one is a member-base leak
 * with a permalink.
 */
trait WritesCsvExport
{
    /**
     * @param  array<int, string>  $headings
     * @param  callable(callable(array<int, mixed>): void): int  $writeRows
     *                                                                       receives a `$row` callback and returns how many rows it wrote
     */
    protected function writeCsv(string $filename, array $headings, callable $writeRows): array
    {
        $handle = fopen('php://temp/maxmemory:2097152', 'r+');

        // UTF-8 BOM. Excel on Windows reads a bare UTF-8 CSV as the system
        // codepage, which turns every non-ASCII name into mojibake — and on
        // this platform that is most of them.
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, $headings);

        $count = $writeRows(function (array $row) use ($handle) {
            fputcsv($handle, $row);
        });

        rewind($handle);

        $path = 'exports/'.$filename;

        Storage::disk('private')->put($path, $handle);

        fclose($handle);

        return ['path' => $path, 'rows' => $count];
    }

    /**
     * Audit first, notify second.
     *
     * The audit row is the record that a staff member extracted the data; the
     * notification is a convenience. If the dispatcher throws, the extraction
     * is still on the trail, which is the ordering that matters.
     */
    protected function announceExport(User $requester, string $subject, array $result): void
    {
        app(AuditLogger::class)->log(
            'export.generated',
            $requester,
            newValues: ['subject' => $subject, 'rows' => $result['rows'], 'path' => $result['path']],
            description: "Exported {$result['rows']} {$subject} rows",
            actor: $requester,
        );

        app(NotificationDispatcher::class)->send(
            user: $requester,
            type: 'export_ready',
            title: ucfirst($subject).' export ready',
            body: "{$result['rows']} rows are ready to download.",
            data: ['path' => $result['path'], 'subject' => $subject],
            force: true,
        );
    }
}
