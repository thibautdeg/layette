<?php

use App\Mail\ContributionThankYouMail;
use App\Models\BankTransaction;
use App\Models\Contribution;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

test('the donor gets a thank-you mail when their contribution is confirmed manually', function () {
    Mail::fake();

    $this->actingAs(admin());
    $contribution = Contribution::factory()->create();

    $this->post(route('admin.contributions.confirm', ['contribution' => $contribution]));

    Mail::assertSent(ContributionThankYouMail::class, fn (ContributionThankYouMail $mail) => $mail->hasTo($contribution->user->email));
});

test('the donor gets a thank-you mail when the bank import matches their payment', function () {
    Mail::fake();

    $this->actingAs(admin());
    $contribution = Contribution::factory()->create(['amount' => 2500]);

    $csv = implode("\n", [
        'Boekingsdatum;Bedrag;Mededeling',
        "05/08/2026;25,00;{$contribution->reference}",
    ]);

    $this->post(route('admin.bank.import'), [
        'statement' => UploadedFile::fake()->createWithContent('afschrift.csv', $csv),
    ]);

    Mail::assertSent(ContributionThankYouMail::class);
});

test('confirming an already confirmed contribution does not send a second thank-you', function () {
    Mail::fake();

    $this->actingAs(admin());
    $contribution = Contribution::factory()->confirmed()->create();
    $transaction = BankTransaction::factory()->create();

    $this->post(route('admin.bank.match', ['bankTransaction' => $transaction]), [
        'contribution_id' => $contribution->id,
    ]);

    Mail::assertNotSent(ContributionThankYouMail::class);
});

test('a purchase reservation never gets a payment thank-you mail', function () {
    Mail::fake();

    $this->actingAs(admin());
    $contribution = Contribution::factory()->purchase()->create();

    $this->post(route('admin.contributions.confirm', ['contribution' => $contribution]));

    Mail::assertNotSent(ContributionThankYouMail::class);
});
