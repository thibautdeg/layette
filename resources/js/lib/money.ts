export function formatEuro(cents: number): string {
    return new Intl.NumberFormat('nl-BE', {
        style: 'currency',
        currency: 'EUR',
    }).format(cents / 100);
}

export function centsToEuroInput(cents: number): string {
    return (cents / 100).toFixed(2);
}

export function euroInputToCents(value: string | number): number {
    const normalized =
        typeof value === 'string' ? value.replace(',', '.') : value;
    const amount = Number(normalized);

    return Number.isFinite(amount) ? Math.round(amount * 100) : 0;
}
