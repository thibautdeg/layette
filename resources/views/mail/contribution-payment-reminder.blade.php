<x-mail::message>
# Nog even dit, {{ $contribution->name }}

@if ($contribution->isFree())
Je vrije bijdrage van **€ {{ number_format($contribution->amount / 100, 2, ',', '.') }}** staat nog te wachten op je overschrijving.
@else
Je bijdrage van **€ {{ number_format($contribution->amount / 100, 2, ',', '.') }}** aan **{{ $gift->title }}** staat nog te wachten op je overschrijving.
@endif

Geen haast hoor — we willen het je enkel makkelijk maken om het niet te vergeten. Hier staan de gegevens nog eens:

@include('mail.partials.payment-details')

Heb je het intussen al overgeschreven? Dan is deze mail gekruist met je betaling en mag je hem gerust vergeten.

@if ($qrPng && isset($message))
Lees je deze mail op je computer? Dan kan het ook zo: scan de code hieronder met je bankapp en de overschrijving staat volledig klaar.

<img src="{{ $message->embedData($qrPng, 'overschrijving-'.$contribution->reference.'.png', 'image/png') }}" alt="QR-code voor je overschrijving" width="180" height="180" style="display: block; margin: 0 auto; border-radius: 12px;">
@endif

<x-mail::button :url="$accountUrl">
Mijn bijdragen bekijken
</x-mail::button>

Dikke merci!<br>
{{ $giftList->title }}
</x-mail::message>
