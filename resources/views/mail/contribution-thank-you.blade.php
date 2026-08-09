<x-mail::message>
# Dankjewel{{ $contribution->together_with !== null ? ', '.$contribution->displayName() : ', '.$contribution->name }}! 💛

Je {{ $contribution->isFree() ? 'vrije bijdrage' : 'bijdrage' }} van **€ {{ number_format($contribution->amount / 100, 2, ',', '.') }}**{{ $contribution->isFree() ? '' : " voor **{$gift->title}**" }} is goed aangekomen op onze rekening.

We zijn er superblij mee — dikke merci!

<x-mail::button :url="route('account.contributions')">
Mijn bijdragen bekijken
</x-mail::button>

Veel liefs<br>
{{ $giftList->title }}
</x-mail::message>
