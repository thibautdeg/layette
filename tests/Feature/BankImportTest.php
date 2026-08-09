<?php

use App\Models\BankTransaction;
use App\Models\Contribution;
use Illuminate\Http\UploadedFile;

function statementFile(string $contents): UploadedFile
{
    return UploadedFile::fake()->createWithContent('afschrift.csv', $contents);
}

test('an import reads incoming transactions and skips outgoing ones', function () {
    $this->actingAs(admin());

    $csv = implode("\n", [
        'Boekingsdatum;Bedrag;Naam tegenpartij;Rekening tegenpartij;Mededeling',
        '05/08/2026;25,00;Tante Rita;BE12345678901234;BL-ABC123',
        '05/08/2026;-13,50;Supermarkt;BE99999999999999;Boodschappen',
    ]);

    $this->post(route('admin.bank.import'), ['statement' => statementFile($csv)])
        ->assertRedirect()
        ->assertSessionHas('import_result', fn (array $result): bool => $result['created'] === 1);

    $dbTransaction = BankTransaction::query()->sole();

    expect($dbTransaction->amount)->toBe(2500)
        ->and($dbTransaction->counterparty_name)->toBe('Tante Rita')
        ->and($dbTransaction->booked_at->toDateString())->toBe('2026-08-05');
});

test('a transaction with a known reference confirms the contribution automatically', function () {
    $this->actingAs(admin());
    $contribution = Contribution::factory()->create(['amount' => 2500]);

    $csv = implode("\n", [
        'Boekingsdatum;Bedrag;Naam tegenpartij;Mededeling',
        "05/08/2026;25,00;Tante Rita;Overschrijving {$contribution->reference} proficiat",
    ]);

    $this->post(route('admin.bank.import'), ['statement' => statementFile($csv)]);

    $dbContribution = Contribution::find($contribution->id);

    expect($dbContribution->isConfirmed())->toBeTrue()
        ->and($dbContribution->bankTransaction)->not->toBeNull();
});

test('a deviating amount does not block automatic matching', function () {
    $this->actingAs(admin());
    $contribution = Contribution::factory()->create(['amount' => 2500]);

    $csv = implode("\n", [
        'Boekingsdatum;Bedrag;Mededeling',
        "05/08/2026;20,00;{$contribution->reference}",
    ]);

    $this->post(route('admin.bank.import'), ['statement' => statementFile($csv)]);

    expect(Contribution::find($contribution->id)->isConfirmed())->toBeTrue();
});

test('uploading the same file twice does not create duplicates', function () {
    $this->actingAs(admin());

    $csv = implode("\n", [
        'Boekingsdatum;Bedrag;Mededeling',
        '05/08/2026;25,00;Zomaar een storting',
    ]);

    $this->post(route('admin.bank.import'), ['statement' => statementFile($csv)]);
    $this->post(route('admin.bank.import'), ['statement' => statementFile($csv)])
        ->assertSessionHas('import_result', fn (array $result): bool => $result['created'] === 0 && $result['duplicates'] === 1);

    expect(BankTransaction::query()->count())->toBe(1);
});

test('two identical deposits on the same day are two separate transactions', function () {
    $this->actingAs(admin());

    $csv = implode("\n", [
        'Boekingsdatum;Bedrag;Mededeling',
        '05/08/2026;25,00;Cadeautje',
        '05/08/2026;25,00;Cadeautje',
    ]);

    $this->post(route('admin.bank.import'), ['statement' => statementFile($csv)]);

    expect(BankTransaction::query()->count())->toBe(2);
});

test('a transaction without a recognizable reference stays in the open list', function () {
    $this->actingAs(admin());
    Contribution::factory()->create(['amount' => 2500]);

    $csv = implode("\n", [
        'Boekingsdatum;Bedrag;Mededeling',
        '05/08/2026;25,00;Zonder referentie',
    ]);

    $this->post(route('admin.bank.import'), ['statement' => statementFile($csv)]);

    expect(BankTransaction::query()->unmatched()->count())->toBe(1);
});

test('an admin can manually link an open transaction to a contribution', function () {
    $this->actingAs(admin());
    $contribution = Contribution::factory()->create(['amount' => 2500]);
    $transaction = BankTransaction::factory()->create();

    $this->post(route('admin.bank.match', ['bankTransaction' => $transaction]), [
        'contribution_id' => $contribution->id,
    ])->assertRedirect();

    $dbContribution = Contribution::find($contribution->id);
    $dbTransaction = BankTransaction::find($transaction->id);

    expect($dbContribution->isConfirmed())->toBeTrue()
        ->and($dbTransaction->contribution_id)->toBe($contribution->id);
});

test('an admin can mark a transaction as not relevant', function () {
    $this->actingAs(admin());
    $transaction = BankTransaction::factory()->create();

    $this->post(route('admin.bank.ignore', ['bankTransaction' => $transaction]))->assertRedirect();

    expect(BankTransaction::find($transaction->id)->isIgnored())->toBeTrue()
        ->and(BankTransaction::query()->unmatched()->count())->toBe(0);
});

test('a comma separated export with english headers also imports', function () {
    $this->actingAs(admin());

    $csv = implode("\n", [
        'Date,Amount,Counterparty,Description',
        '2026-08-05,25.00,Aunt Rita,BL-XYZ789',
    ]);

    $this->post(route('admin.bank.import'), ['statement' => statementFile($csv)]);

    $dbTransaction = BankTransaction::query()->sole();

    expect($dbTransaction->amount)->toBe(2500)
        ->and($dbTransaction->booked_at->toDateString())->toBe('2026-08-05');
});
