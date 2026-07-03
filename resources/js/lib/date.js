// Shared date formatters for display. Backend sends ISO datetimes
// (e.g. 2026-06-29T03:59:34.000000Z) which must never be shown raw.

export function formatDate(d) {
    if (!d) return '';
    const date = new Date(d);
    return isNaN(date) ? '' : date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

export function formatDateTime(d) {
    if (!d) return '';
    const date = new Date(d);
    return isNaN(date) ? '' : date.toLocaleString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

// Compact relative time ("2h ago", "3d ago"), falling back to a date for older items.
export function timeAgo(d) {
    if (!d) return '';
    const date = new Date(d);
    if (isNaN(date)) return '';
    const secs = Math.floor((Date.now() - date.getTime()) / 1000);
    if (secs < 60) return 'just now';
    const mins = Math.floor(secs / 60);
    if (mins < 60) return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    if (days < 7) return `${days}d ago`;
    return formatDate(d);
}
