/**
 * Tegelkleuren voor de ludieke lijst. We roteren door een vaste set zodat
 * naast elkaar staande cadeaus altijd een andere kleur krijgen.
 */
export const tileColors = [
    'bg-sky',
    'bg-sun',
    'bg-mint',
    'bg-blush',
    'bg-tangerine',
    'bg-lime',
] as const;

export function tileColor(index: number): string {
    return tileColors[index % tileColors.length];
}

export function percentage(part: number, total: number): number {
    if (total <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((part / total) * 100));
}
