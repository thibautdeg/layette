<x-mail::panel>
Rekeningnummer<br>
**{{ $giftList->formattedIban() }}**

Bedrag<br>
**€ {{ number_format($contribution->amount / 100, 2, ',', '.') }}**

Mededeling<br>
**{{ $contribution->reference }}**
@if ($giftList->account_holder)

Op naam van<br>
{{ $giftList->account_holder }}
@endif
</x-mail::panel>
