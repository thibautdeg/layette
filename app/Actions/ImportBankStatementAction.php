<?php

namespace App\Actions;

use App\DTO\BankStatementImportResultData;
use App\Models\BankTransaction;
use App\Models\Contribution;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ImportBankStatementAction
{
    public function __construct(public ConfirmContributionAction $confirmContribution) {}

    /**
     * Import the incoming transactions from an exported bank statement (CSV).
     * Outgoing transactions are ignored, previously imported transactions are skipped,
     * and transactions with a recognizable reference are matched to their contribution.
     */
    public function handle(string $contents): BankStatementImportResultData
    {
        $rows = $this->parse($contents);

        $created = 0;
        $duplicates = 0;
        $matched = 0;
        $unmatched = 0;

        $occurrences = [];

        foreach ($rows as $row) {
            $key = "{$row['booked_at']}|{$row['amount']}|{$row['counterparty_account']}|{$row['description']}";
            $occurrences[$key] = ($occurrences[$key] ?? -1) + 1;

            $fingerprint = hash('sha256', "{$key}|{$occurrences[$key]}");

            if (BankTransaction::query()->where('fingerprint', $fingerprint)->exists()) {
                $duplicates++;

                continue;
            }

            $transaction = BankTransaction::query()->create([
                'fingerprint' => $fingerprint,
                'counterparty_name' => $row['counterparty_name'],
                'counterparty_account' => $row['counterparty_account'],
                'description' => $row['description'],
                'amount' => $row['amount'],
                'booked_at' => $row['booked_at'],
            ]);

            $created++;

            if ($this->matchTransaction($transaction)) {
                $matched++;
            } else {
                $unmatched++;
            }
        }

        return new BankStatementImportResultData($created, $duplicates, $matched, $unmatched);
    }

    /**
     * Try to find a pending contribution whose reference appears in the transaction description.
     */
    protected function matchTransaction(BankTransaction $transaction): bool
    {
        $description = $transaction->normalizedDescription();

        if ($description === '') {
            return false;
        }

        $contribution = Contribution::query()
            ->pending()
            ->whereNotNull('amount')
            ->get()
            ->first(fn (Contribution $contribution): bool => str_contains($description, $contribution->normalizedReference()));

        if ($contribution === null) {
            return false;
        }

        $this->confirmContribution->handle($contribution, $transaction);

        return true;
    }

    /**
     * @return list<array{booked_at: string, amount: int, counterparty_name: string|null, counterparty_account: string|null, description: string|null}>
     */
    protected function parse(string $contents): array
    {
        $contents = $this->normalizeEncoding($contents);

        $lines = preg_split('/\r\n|\r|\n/', trim($contents)) ?: [];
        $lines = array_values(array_filter($lines, fn (string $line): bool => trim($line) !== ''));

        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'statement' => 'Het bestand bevat geen verrichtingen.',
            ]);
        }

        $delimiter = $this->detectDelimiter($lines[0]);
        $header = array_map(fn (?string $column): string => mb_strtolower(trim((string) $column, "\" \t")), str_getcsv($lines[0], $delimiter, '"', '\\'));

        $columns = $this->mapColumns($header);

        if ($columns['booked_at'] === null || $columns['amount'] === null) {
            throw ValidationException::withMessages([
                'statement' => 'Het bestand wordt niet herkend: er is geen datum- of bedragkolom gevonden.',
            ]);
        }

        $rows = [];

        foreach (array_slice($lines, 1) as $line) {
            $cells = str_getcsv($line, $delimiter, '"', '\\');

            $amount = $this->parseAmount($cells[$columns['amount']] ?? '');
            $bookedAt = $this->parseDate($cells[$columns['booked_at']] ?? '');

            if ($amount === null || $bookedAt === null) {
                continue;
            }

            if ($amount <= 0) {
                continue;
            }

            $rows[] = [
                'booked_at' => $bookedAt,
                'amount' => $amount,
                'counterparty_name' => $this->cell($cells, $columns['counterparty_name']),
                'counterparty_account' => $this->cell($cells, $columns['counterparty_account']),
                'description' => $this->cell($cells, $columns['description']),
            ];
        }

        return $rows;
    }

    protected function normalizeEncoding(string $contents): string
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'ISO-8859-1');
        }

        return $contents;
    }

    protected function detectDelimiter(string $headerLine): string
    {
        return substr_count($headerLine, ';') >= substr_count($headerLine, ',') ? ';' : ',';
    }

    /**
     * Map the header columns of the export to the fields we need, based on common
     * Belgian bank exports (KBC, Belfius, ING, BNP Paribas Fortis, Argenta).
     *
     * @param  list<string>  $header
     * @return array{booked_at: int|null, amount: int|null, description: int|null, counterparty_name: int|null, counterparty_account: int|null}
     */
    protected function mapColumns(array $header): array
    {
        return [
            'booked_at' => $this->findColumn($header, ['boekingsdatum', 'boekdatum', 'uitvoeringsdatum', 'datum', 'date']),
            'amount' => $this->findColumn($header, ['bedrag', 'amount']),
            'description' => $this->findColumn($header, ['vrije mededeling', 'gestructureerde mededeling', 'mededeling', 'communicatie', 'omschrijving', 'transactie', 'beschrijving', 'communication', 'description', 'detail']),
            'counterparty_name' => $this->findColumn($header, ['naam tegenpartij', 'tegenpartij bevat', 'naam van de tegenpartij', 'tegenpartij', 'naam', 'counterparty']),
            'counterparty_account' => $this->findColumn($header, ['rekening tegenpartij', 'tegenrekening', 'iban tegenpartij', 'rekeningnummer tegenpartij']),
        ];
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $candidates
     */
    protected function findColumn(array $header, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            foreach ($header as $index => $column) {
                if (str_contains($column, $candidate)) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Parse an amount like "1.234,56", "-12,50" or "1234.56" into cents.
     */
    protected function parseAmount(string $value): ?int
    {
        $value = trim(str_replace(['€', ' ', "\u{A0}"], '', $value));

        if ($value === '') {
            return null;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value * 100);
    }

    protected function parseDate(string $value): ?string
    {
        $value = trim($value, "\" \t");

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'd.m.Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $cells
     */
    protected function cell(array $cells, ?int $index): ?string
    {
        if ($index === null) {
            return null;
        }

        $value = trim($cells[$index] ?? '');

        return $value === '' ? null : $value;
    }
}
