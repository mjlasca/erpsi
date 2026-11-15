import { formatMoney, formatNumber } from '@/lib/format';

type ComparisonPoint = {
    label: string;
    primary: string;
    secondary: string;
};

type LinePoint = {
    label: string;
    first: string;
    second: string;
};

const width = 720;
const height = 260;
const padding = { top: 24, right: 20, bottom: 42, left: 56 };

function maxValue(values: number[]): number {
    const max = Math.max(...values, 0);
    return max <= 0 ? 1 : max;
}

export function ComparisonBarChart({
    data,
    primaryLabel,
    secondaryLabel,
}: {
    data: ComparisonPoint[];
    primaryLabel: string;
    secondaryLabel: string;
}) {
    const max = maxValue(
        data.flatMap((point) => [
            Number(point.primary),
            Number(point.secondary),
        ]),
    );
    const plotWidth = width - padding.left - padding.right;
    const plotHeight = height - padding.top - padding.bottom;
    const step = data.length === 0 ? plotWidth : plotWidth / data.length;
    const barWidth = Math.max(6, Math.min(18, step / 3));

    const y = (value: number): number =>
        padding.top + plotHeight - (value / max) * plotHeight;

    return (
        <div className="w-full">
            <div className="mb-3 flex flex-wrap gap-4 text-xs text-gray-500">
                <span className="flex items-center gap-1.5">
                    <span className="h-2.5 w-2.5 rounded bg-blue-500" />
                    {primaryLabel}
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="h-2.5 w-2.5 rounded bg-emerald-500" />
                    {secondaryLabel}
                </span>
            </div>
            <svg
                viewBox={`0 0 ${width} ${height}`}
                role="img"
                aria-label={`${primaryLabel} / ${secondaryLabel}`}
                className="h-auto w-full overflow-visible"
            >
                {[0, 0.25, 0.5, 0.75, 1].map((fraction) => {
                    const value = max * fraction;
                    const lineY = y(value);
                    return (
                        <g key={fraction}>
                            <line
                                x1={padding.left}
                                x2={width - padding.right}
                                y1={lineY}
                                y2={lineY}
                                stroke="#f3f4f6"
                            />
                            <text
                                x={padding.left - 8}
                                y={lineY + 4}
                                textAnchor="end"
                                className="fill-gray-400 text-[10px]"
                            >
                                {formatNumber(value, 0)}
                            </text>
                        </g>
                    );
                })}
                {data.map((point, index) => {
                    const center = padding.left + step * index + step / 2;
                    const primaryHeight =
                        (Number(point.primary) / max) * plotHeight;
                    const secondaryHeight =
                        (Number(point.secondary) / max) * plotHeight;
                    const showLabel =
                        data.length <= 8 ||
                        index % Math.ceil(data.length / 8) === 0;

                    return (
                        <g key={`${point.label}-${index}`}>
                            <rect
                                x={center - barWidth - 2}
                                y={padding.top + plotHeight - primaryHeight}
                                width={barWidth}
                                height={primaryHeight}
                                rx={3}
                                className="fill-blue-500"
                            >
                                <title>
                                    {point.label} · {primaryLabel}:{' '}
                                    {formatMoney(point.primary)}
                                </title>
                            </rect>
                            <rect
                                x={center + 2}
                                y={padding.top + plotHeight - secondaryHeight}
                                width={barWidth}
                                height={secondaryHeight}
                                rx={3}
                                className="fill-emerald-500"
                            >
                                <title>
                                    {point.label} · {secondaryLabel}:{' '}
                                    {formatMoney(point.secondary)}
                                </title>
                            </rect>
                            {showLabel && (
                                <text
                                    x={center}
                                    y={height - 18}
                                    textAnchor="middle"
                                    className="fill-gray-400 text-[10px]"
                                >
                                    {point.label}
                                </text>
                            )}
                        </g>
                    );
                })}
            </svg>
        </div>
    );
}

export function DualLineChart({
    data,
    firstLabel,
    secondLabel,
}: {
    data: LinePoint[];
    firstLabel: string;
    secondLabel: string;
}) {
    const max = maxValue(
        data.flatMap((point) => [Number(point.first), Number(point.second)]),
    );
    const plotWidth = width - padding.left - padding.right;
    const plotHeight = height - padding.top - padding.bottom;
    const step = data.length <= 1 ? plotWidth : plotWidth / (data.length - 1);
    const x = (index: number): number => padding.left + step * index;
    const y = (value: number): number =>
        padding.top + plotHeight - (value / max) * plotHeight;

    const path = (key: 'first' | 'second'): string =>
        data
            .map((point, index) => {
                const pointX = x(index);
                const pointY = y(Number(point[key]));
                return `${index === 0 ? 'M' : 'L'} ${pointX} ${pointY}`;
            })
            .join(' ');

    return (
        <div className="w-full">
            <div className="mb-3 flex flex-wrap gap-4 text-xs text-gray-500">
                <span className="flex items-center gap-1.5">
                    <span className="h-0.5 w-4 rounded bg-blue-500" />
                    {firstLabel}
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="h-0.5 w-4 rounded bg-red-400" />
                    {secondLabel}
                </span>
            </div>
            <svg
                viewBox={`0 0 ${width} ${height}`}
                role="img"
                aria-label={`${firstLabel} / ${secondLabel}`}
                className="h-auto w-full overflow-visible"
            >
                {[0, 0.25, 0.5, 0.75, 1].map((fraction) => {
                    const value = max * fraction;
                    const lineY = y(value);
                    return (
                        <g key={fraction}>
                            <line
                                x1={padding.left}
                                x2={width - padding.right}
                                y1={lineY}
                                y2={lineY}
                                stroke="#f3f4f6"
                            />
                            <text
                                x={padding.left - 8}
                                y={lineY + 4}
                                textAnchor="end"
                                className="fill-gray-400 text-[10px]"
                            >
                                {formatNumber(value, 0)}
                            </text>
                        </g>
                    );
                })}
                <path
                    d={path('first')}
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2.5}
                    className="text-blue-500"
                />
                <path
                    d={path('second')}
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2.5}
                    className="text-red-400"
                />
                {data.map((point, index) => {
                    const showLabel =
                        data.length <= 8 ||
                        index % Math.ceil(data.length / 7) === 0;

                    return (
                        <g key={`${point.label}-${index}`}>
                            <circle
                                cx={x(index)}
                                cy={y(Number(point.first))}
                                r={3}
                                className="fill-blue-500"
                            >
                                <title>
                                    {point.label} · {firstLabel}:{' '}
                                    {formatMoney(point.first)}
                                </title>
                            </circle>
                            <circle
                                cx={x(index)}
                                cy={y(Number(point.second))}
                                r={3}
                                className="fill-red-400"
                            >
                                <title>
                                    {point.label} · {secondLabel}:{' '}
                                    {formatMoney(point.second)}
                                </title>
                            </circle>
                            {showLabel && (
                                <text
                                    x={x(index)}
                                    y={height - 18}
                                    textAnchor="middle"
                                    className="fill-gray-400 text-[10px]"
                                >
                                    {point.label}
                                </text>
                            )}
                        </g>
                    );
                })}
            </svg>
        </div>
    );
}
