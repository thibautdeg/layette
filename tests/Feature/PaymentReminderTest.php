<?php

use App\Mail\ContributionPaymentReminderMail;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\GiftList;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->giftList = GiftList::factory()->create(['iban' => 'BE68539007547034']);
    $this->gift = Gift::factory()->for($this->giftList)->create(['title' => 'Kinderwagen']);
});

test('a contribution younger than three days gets no reminder', function () {
    Mail::fake();

    Contribution::factory()->for($this->gift)->create(['created_at' => now()->subDays(2)]);

    $this->artisan('contributions:send-payment-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('a first reminder goes out after three days', function () {
    Mail::fake();

    $contribution = Contribution::factory()->for($this->gift)->create(['created_at' => now()->subDays(3)]);

    $this->artisan('contributions:send-payment-reminders')->assertSuccessful();

    Mail::assertSent(
        ContributionPaymentReminderMail::class,
        fn (ContributionPaymentReminderMail $mail) => $mail->hasTo($contribution->user->email),
    );

    $dbContribution = Contribution::find($contribution->id);

    expect($dbContribution->payment_reminders_sent)->toBe(1)
        ->and($dbContribution->payment_reminded_at)->not->toBeNull();
});

test('the second reminder waits until day ten', function () {
    Mail::fake();

    Contribution::factory()->for($this->gift)->create([
        'created_at' => now()->subDays(9),
        'payment_reminders_sent' => 1,
    ]);

    $this->artisan('contributions:send-payment-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('a second reminder goes out after ten days', function () {
    Mail::fake();

    $contribution = Contribution::factory()->for($this->gift)->create([
        'created_at' => now()->subDays(10),
        'payment_reminders_sent' => 1,
    ]);

    $this->artisan('contributions:send-payment-reminders')->assertSuccessful();

    Mail::assertSent(ContributionPaymentReminderMail::class);

    expect(Contribution::find($contribution->id)->payment_reminders_sent)->toBe(2);
});

test('donors never get more than two reminders', function () {
    Mail::fake();

    Contribution::factory()->for($this->gift)->create([
        'created_at' => now()->subDays(60),
        'payment_reminders_sent' => 2,
    ]);

    $this->artisan('contributions:send-payment-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('a missed run still sends only the reminder that is due', function () {
    Mail::fake();

    $contribution = Contribution::factory()->for($this->gift)->create(['created_at' => now()->subDays(30)]);

    $this->artisan('contributions:send-payment-reminders')->assertSuccessful();

    Mail::assertSentCount(1);

    expect(Contribution::find($contribution->id)->payment_reminders_sent)->toBe(1);
});

test('confirmed and cancelled contributions get no reminder', function () {
    Mail::fake();

    Contribution::factory()->for($this->gift)->confirmed()->create(['created_at' => now()->subDays(30)]);
    Contribution::factory()->for($this->gift)->cancelled()->create(['created_at' => now()->subDays(30)]);

    $this->artisan('contributions:send-payment-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('a purchase reservation gets no payment reminder', function () {
    Mail::fake();

    Contribution::factory()->for($this->gift)->purchase()->create(['created_at' => now()->subDays(30)]);

    $this->artisan('contributions:send-payment-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('no reminders go out when the list has no account number', function () {
    Mail::fake();

    $this->giftList->update(['iban' => null]);

    Contribution::factory()->for($this->gift)->create(['created_at' => now()->subDays(30)]);

    $this->artisan('contributions:send-payment-reminders')->assertSuccessful();

    Mail::assertNothingSent();
});

test('the reminder repeats the same payment details', function () {
    $contribution = Contribution::factory()->for($this->gift)->create(['amount' => 4000]);

    $rendered = (new ContributionPaymentReminderMail($contribution))->render();

    expect($rendered)
        ->toContain('BE68 5390 0754 7034')
        ->toContain('40,00')
        ->toContain($contribution->reference)
        ->toContain('Kinderwagen');
});
