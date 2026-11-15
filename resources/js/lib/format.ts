let currentLocale = 'es';
let currentCurrency = 'MXN';
let currentTimezone: string | undefined;

export function setFormattingSettings(
    locale: string,
    currency = 'MXN',
    timezone?: string,
): void {
    currentLocale = locale === 'en' ? 'en' : 'es';
    currentCurrency = currency;
    currentTimezone = timezone;
}

function intlLocale(): string {
    return currentLocale === 'en' ? 'en-US' : 'es-MX';
}

export function formatMoney(value: string | number | null | undefined): string {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat(intlLocale(), {
        style: 'currency',
        currency: currentCurrency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number.isFinite(amount) ? amount : 0);
}

export function formatNumber(
    value: string | number | null | undefined,
    digits = 3,
): string {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat(intlLocale(), {
        minimumFractionDigits: 0,
        maximumFractionDigits: digits,
    }).format(Number.isFinite(amount) ? amount : 0);
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleDateString(intlLocale(), {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
          });
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
        ? value
        : date.toLocaleString(intlLocale(), {
              day: '2-digit',
              month: 'short',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
              ...(currentTimezone ? { timeZone: currentTimezone } : {}),
          });
}

export function text(value: unknown): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'string') {
        return value;
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    if (value instanceof Date) {
        return formatDateTime(value.toISOString());
    }

    return '—';
}
