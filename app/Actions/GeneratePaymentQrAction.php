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
        if (! $this->isPayable($giftList, $contribution)) {
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
     * The same QR code as binary PNG data, for contexts without SVG
     * support such as mail clients.
     */
    public function png(GiftList $giftList, Contribution $contribution): ?string
    {
        if (! $this->isPayable($giftList, $contribution)) {
            return null;
        }

        $matrix = Encoder::encode(
            $this->payload($giftList, $contribution),
            ErrorCorrectionLevel::M(),
            Encoder::DEFAULT_BYTE_MODE_ENCODING,
        )->getMatrix();

        $scale = 12;
        $quietZone = 4 * $scale;
        $size = max(1, $matrix->getWidth() * $scale + 2 * $quietZone);

        $image = imagecreatetruecolor($size, $size);
        $white = (int) imagecolorallocate($image, 255, 255, 255);
        $black = (int) imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, $white);

        for ($y = 0; $y < $matrix->getHeight(); $y++) {
            for ($x = 0; $x < $matrix->getWidth(); $x++) {
                if ($matrix->get($x, $y) === 1) {
                    imagefilledrectangle(
                        $image,
                        $quietZone + $x * $scale,
                        $quietZone + $y * $scale,
                        $quietZone + ($x + 1) * $scale - 1,
                        $quietZone + ($y + 1) * $scale - 1,
                        $black,
                    );
                }
            }
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
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

    protected function isPayable(GiftList $giftList, Contribution $contribution): bool
    {
        return $giftList->iban !== null
            && $giftList->account_holder !== null
            && $contribution->amount !== null
            && $contribution->amount >= 1;
    }
}
