<x-mail::message>
# Bedankt, {{ $contribution->name }}!

@if ($contribution->isPurchase())
Je hebt aangegeven dat je **{{ $gift->title }}** zelf koopt. Het cadeau staat nu als gereserveerd op de lijst, zodat niemand anders het nog kiest. Je hoeft verder niets te doen via deze site.
@else
@if ($contribution->isFree())
Je hebt aangekondigd om **€ {{ number_format($contribution->amount / 100, 2, ',', '.') }}** te geven als vrije bijdrage. Wat lief!
@else
Je hebt aangekondigd om **€ {{ number_format($contribution->amount / 100, 2, ',', '.') }}** bij te dragen aan **{{ $gift->title }}**.
@endif

## Zo maak je het over

@if ($giftList->iban)
Neem deze mail er even bij wanneer het je uitkomt en maak het bedrag over met deze gegevens:

@include('mail.partials.payment-details')

Zodra we je overschrijving op de rekening zien verschijnen, bevestigen we je bijdrage.

@if ($qrPng && isset($message))
Lees je deze mail op je computer? Dan kan het ook zo: scan de code hieronder met je bankapp en de overschrijving staat volledig klaar.

<img src="{{ $message->embedData($qrPng, 'overschrijving-'.$contribution->reference.'.png', 'image/png') }}" alt="QR-code voor je overschrijving" width="180" height="180" style="display: block; margin: 0 auto; border-radius: 12px;">
@endif
@else
We bezorgen je onze rekeninggegevens persoonlijk. Vermeld zeker deze referentie in de mededeling van je overschrijving:

<x-mail::panel>
**{{ $contribution->reference }}**
</x-mail::panel>
@endif
@endif

## Je bijdragen opvolgen

Via je account kan je je {{ $contribution->isPurchase() ? 'reservatie' : 'bijdrage' }} altijd bekijken en opvolgen.

<x-mail::button :url="$accountUrl">
Mijn bijdragen bekijken
</x-mail::button>

Dikke merci!<br>
{{ $giftList->title }}
</x-mail::message>
