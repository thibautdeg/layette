<?php

namespace App\Actions;

use App\Models\Contribution;
use App\Models\GiftList;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class GeneratePaymentQrAction
{
    /**
     * An EPC QR code (SEPA credit transfer) as SVG, or null when the
     * gift list is missing the payment details the EPC format requires.
     */
    public function handle(GiftList $giftList, Contribution $contribution): ?string
    {
        if ($giftList->iban === null || $giftList->account_holder === null) {
            return null;
        }

        if ($contribution->amount === null || $contribution->amount < 1) {
            return null;
        }

        $renderer = new ImageRenderer(new RendererStyle(192), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString(
            $this->payload($giftList, $contribution),
            Encoder::DEFAULT_BYTE_MODE_ENCODING,
            ErrorCorrectionLevel::M(),
        );
    }

    /**
     * The QR payload according to the EPC069-12 standard for SEPA credit transfers.
     */
    public function payload(GiftList $giftList, Contribution $contribution): string
    {
        return implode("\n", [
            'BCD',
            '002',
            '2',
            'SCT',
            '',
            mb_substr((string) $giftList->account_holder, 0, 70),
            mb_strtoupper(str_replace(' ', '', (string) $giftList->iban)),
            'EUR'.number_format(($contribution->amount ?? 0) / 100, 2, '.', ''),
            '',
            '',
            $contribution->reference,
        ]);
    }
}
