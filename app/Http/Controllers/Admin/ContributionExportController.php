<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContributionExportController extends Controller
{
    /**
     * Download all contributions as a CSV, ready for writing thank-you cards.
     */
    public function index(): StreamedResponse
    {
        $contributions = Contribution::query()
            ->with(['gift', 'user'])
            ->latest()
            ->get();

        return response()->streamDownload(function () use ($contributions): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\u{FEFF}");

            fputcsv($handle, ['Naam', 'Samen met', 'Mailadres', 'Cadeau', 'Type', 'Bedrag', 'Status', 'Aangekondigd op', 'Bevestigd op', 'Mededeling'], ';', '"', '\\');

            foreach ($contributions as $contribution) {
                fputcsv($handle, [
                    $contribution->name,
                    $contribution->together_with,
                    $contribution->user->email,
                    $contribution->giftTitle(),
                    $contribution->type->label(),
                    $contribution->amount !== null ? number_format($contribution->amount / 100, 2, ',', '') : '',
                    $contribution->status->label(),
                    $contribution->created_at?->toDateString(),
                    $contribution->confirmed_at?->toDateString(),
                    $contribution->message,
                ], ';', '"', '\\');
            }

            fclose($handle);
        }, 'bijdragen-geboortelijst.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
