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

Zodra we je overschrijving op de rekening zien verschijnen, bevestigen we je bijdrage.

## Zo maak je het over

@if ($qrPng && isset($message))
<img src="{{ $message->embedData($qrPng, 'overschrijving-'.$contribution->reference.'.png', 'image/png') }}" alt="QR-code voor je overschrijving" width="220" height="220" style="display: block; margin: 0 auto; border-radius: 12px;">

**Scan de QR-code met je bankapp** (kies scannen of QR-code in de app). De overschrijving staat dan volledig klaar: het bedrag, ons rekeningnummer en de mededeling zijn al ingevuld. Lees je deze mail op je telefoon? Sla de QR-code dan op als afbeelding en kies die foto in je bankapp bij het scannen.

Lukt het niet? Vul de overschrijving dan zelf in:
@endif

@if ($giftList->iban)
{{ $giftList->account_holder ?? 'Rekening' }} — {{ $giftList->iban }}<br>
Bedrag: € {{ number_format($contribution->amount / 100, 2, ',', '.') }}<br>
Mededeling: **{{ $contribution->reference }}**
@else
Vermeld zeker deze referentie in de mededeling van je overschrijving:

<x-mail::panel>
**{{ $contribution->reference }}**
</x-mail::panel>
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
