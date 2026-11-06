<?php

namespace App\Jobs;

use App\Jobs\Concerns\WritesCsvExport;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * The payments CSV — the file an accountant reconciles against the Razorpay
 * settlement report, so the columns are chosen for that job rather than for
 * the admin table.
 *
 * Two of them matter more than they look:
 *
 *   • `razorpay_payment_id` is the join key on the gateway's side. Without it
 *     a mismatch cannot be traced to a transaction and the whole export is
 *     just numbers.
 *   • the refund columns are separate rather than netted into the amount.
 *     A netted row cannot be reconciled against a settlement that lists the
 *     charge and the refund as two movements on two different dates.
 *
 * Amounts are rupees with two decimals, exactly as stored. Paise exist only at
 * the Razorpay boundary and converting here would be a units mismatch in the
 * one document where units must be unambiguous.
 */
class ExportPayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WritesCsvExport;

    public int $tries = 2;

    public int $timeout = 900;

    /** @param  array<string, mixed>  $filters */
    public function __construct(
        public int $requesterId,
        public array $filters = [],
    ) {
        $this->onQueue(config('queue.queues.reports'));
    }

    public function handle(): void
    {
        if (! $requester = User::find($this->requesterId)) {
            return;
        }

        $result = $this->writeCsv(
            filename: 'payments-'.now()->format('Ymd-His').'-'.$this->requesterId.'.csv',
            headings: [
                'Invoice number', 'Created at', 'Paid at', 'Profile code', 'Member', 'Plan',
                'Amount', 'Currency', 'Status', 'Method',
                'Razorpay order id', 'Razorpay payment id',
                'Refund id', 'Refunded amount', 'Refund reason',
            ],
            writeRows: function (callable $row) {
                $count = 0;

                $this->query()->chunkById(1000, function ($rows) use ($row, &$count) {
                    foreach ($rows as $payment) {
                        $row([
                            $payment->invoice_number,
                            $payment->created_at?->toDateTimeString(),
                            $payment->verified_at?->toDateTimeString(),
                            $payment->profile_code,
                            $payment->member_name,
                            $payment->plan_name,
                            number_format((float) $payment->amount, 2, '.', ''),
                            $payment->currency,
                            $payment->status,
                            $payment->method,
                            $payment->razorpay_order_id,
                            $payment->razorpay_payment_id,
                            $payment->refund_id,
                            $payment->refunded_amount !== null
                                ? number_format((float) $payment->refunded_amount, 2, '.', '')
                                : null,
                            $payment->refund_reason,
                        ]);

                        $count++;
                    }
                }, 'payments.id', 'id');

                return $count;
            },
        );

        $this->announceExport($requester, 'payments', $result);
    }

    /**
     * LEFT JOIN on users because `payments.user_id` is nullOnDelete — a
     * deleted member must not erase the financial record, and the row still
     * has to appear in the export with a blank name rather than vanish from
     * the reconciliation.
     */
    protected function query()
    {
        return Payment::query()
            ->leftJoin('users', 'users.id', '=', 'payments.user_id')
            ->leftJoin('membership_plans', 'membership_plans.id', '=', 'payments.membership_plan_id')
            ->select([
                'payments.id', 'payments.invoice_number', 'payments.created_at', 'payments.verified_at',
                'payments.amount', 'payments.currency', 'payments.status', 'payments.method',
                'payments.razorpay_order_id', 'payments.razorpay_payment_id',
                'payments.refund_id', 'payments.refunded_amount', 'payments.refund_reason',
                'users.profile_code', 'users.name as member_name',
                'membership_plans.name as plan_name',
            ])
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('payments.status', $v))
            ->when($this->filters['plan_id'] ?? null, fn ($q, $v) => $q->where('payments.membership_plan_id', $v))
            ->when($this->filters['user_id'] ?? null, fn ($q, $v) => $q->where('payments.user_id', $v))
            ->when($this->filters['from'] ?? null, fn ($q, $v) => $q->where('payments.created_at', '>=', $v))
            ->when($this->filters['to'] ?? null, fn ($q, $v) => $q->where('payments.created_at', '<=', $v));
    }
}
