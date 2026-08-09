<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Contribution;
use Inertia\Inertia;
use Inertia\Response;

class BankTransactionController extends Controller
{
    public function index(): Response
    {
        $unmatched = BankTransaction::query()
            ->unmatched()
            ->latest('booked_at')
            ->get();

        $recent = BankTransaction::query()
            ->whereNotNull('contribution_id')
            ->orWhereNotNull('ignored_at')
            ->with('contribution')
            ->latest('booked_at')
            ->limit(25)
            ->get();

        $pendingContributions = Contribution::query()
            ->pending()
            ->whereNotNull('amount')
            ->with('gift')
            ->latest()
            ->get();

        return Inertia::render('admin/Bank', [
            'unmatched' => $unmatched->map(fn (BankTransaction $transaction): array => $this->transactionPayload($transaction))->values(),
            'recent' => $recent->map(fn (BankTransaction $transaction): array => $this->transactionPayload($transaction))->values(),
            'pendingContributions' => $pendingContributions->map(fn (Contribution $contribution): array => [
                'id' => $contribution->id,
                'label' => "{$contribution->reference} — {$contribution->name} — € ".number_format($contribution->amount / 100, 2, ',', '.')." ({$contribution->gift->title})",
            ])->values(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function transactionPayload(BankTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'booked_at' => $transaction->booked_at->toDateString(),
            'amount' => $transaction->amount,
            'counterparty_name' => $transaction->counterparty_name,
            'counterparty_account' => $transaction->counterparty_account,
            'description' => $transaction->description,
            'is_ignored' => $transaction->isIgnored(),
            'contribution_reference' => $transaction->contribution?->reference,
        ];
    }
}
