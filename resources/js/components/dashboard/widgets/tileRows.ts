import type { WidgetMode } from './widgetMode';

/**
 * How many tiles go in each row, for a widget showing a variable number of tiles
 * (charts, stats) that should always fill the width:
 *
 * - 3 columns (desktop): everything in one row.
 * - 2 columns (medium): balanced rows of at most 3, bigger rows first (3 → 2+1, 4 → 2+2, 5 → 3+2, 7 → 3+2+2).
 * - 1 column (mobile): pairs (3 → 2+1, 5 → 2+2+1), or `mobilePerRow` per row (1 = stacked).
 */
export function tileRows(
    count: number,
    layout: WidgetMode,
    mobilePerRow = 2,
): number[] {
    if (count <= 0) {
        return [];
    }

    if (layout === 'desktop') {
        return [count];
    }

    const rowCount =
        layout === 'medium'
            ? count <= 2
                ? 1
                : Math.max(2, Math.ceil(count / 3))
            : Math.ceil(count / mobilePerRow);
    const base = Math.floor(count / rowCount);
    const extra = count % rowCount;

    return Array.from(
        { length: rowCount },
        (_, row) => base + (row < extra ? 1 : 0),
    );
}

const gcd = (a: number, b: number): number => (b === 0 ? a : gcd(b, a % b));

/**
 * CSS grid styles that lay tiles out in {@see tileRows}: the grid gets as many
 * columns as needed for every row size to divide evenly, and each tile spans
 * its share of its row.
 */
export function tileGrid(
    count: number,
    layout: WidgetMode,
    mobilePerRow = 2,
): {
    grid: Record<string, string>;
    tile: (index: number) => Record<string, string>;
} {
    const rows = tileRows(count, layout, mobilePerRow);
    const columns = rows.reduce(
        (lcm, size) => (lcm * size) / gcd(lcm, size),
        1,
    );
    const spans = rows.flatMap((size) =>
        Array.from({ length: size }, () => columns / size),
    );

    return {
        grid: {
            display: 'grid',
            gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))`,
        },
        tile: (index) => ({ gridColumn: `span ${spans[index] ?? columns}` }),
    };
}
