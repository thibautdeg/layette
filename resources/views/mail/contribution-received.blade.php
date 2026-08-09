<x-mail::message>
# Bedankt, {{ $contribution->name }}!

@if ($contribution->isPurchase())
Je hebt aangegeven dat je **{{ $gift->title }}** zelf koopt. Het cadeau staat nu als gereserveerd op de lijst, zodat niemand anders het nog kiest. Je hoeft verder niets te doen via deze site.
@else
Je hebt aangekondigd om **€ {{ number_format($contribution->amount / 100, 2, ',', '.') }}** bij te dragen aan **{{ $gift->title }}**.

Zodra we je overschrijving op de rekening zien verschijnen, bevestigen we je bijdrage.

## Zo maak je het over

Vermeld zeker deze referentie in de mededeling van je overschrijving:

<x-mail::panel>
**{{ $contribution->reference }}**
</x-mail::panel>

@if ($giftList->wero_phone)
**Via Wero:** stuur het bedrag naar {{ $giftList->wero_phone }} en zet de referentie in het bericht.
@endif

@if ($giftList->iban)
**Via overschrijving:**
{{ $giftList->account_holder ?? 'Rekening' }} — {{ $giftList->iban }}
Bedrag: € {{ number_format($contribution->amount / 100, 2, ',', '.') }}
Mededeling: {{ $contribution->reference }}
@endif

<x-mail::button :url="$instructionsUrl">
Bekijk de betaalinstructies
</x-mail::button>
@endif

## Je bijdragen opvolgen

Via je account kan je je {{ $contribution->isPurchase() ? 'reservatie' : 'bijdrage' }} altijd bekijken en opvolgen.

<x-mail::button :url="$accountUrl">
Mijn bijdragen bekijken
</x-mail::button>

Dikke merci!<br>
{{ $giftList->title }}
</x-mail::message>
