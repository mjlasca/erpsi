export function withQuery(
    url: string,
    filters: Record<string, string | number | boolean | null | undefined>,
): string {
    const params = new URLSearchParams();

    Object.entries(filters).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') {
            return;
        }

        params.set(key, String(value));
    });

    const query = params.toString();

    return query === '' ? url : `${url}?${query}`;
}
