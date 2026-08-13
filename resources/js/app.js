import 'bootstrap';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

document.addEventListener('DOMContentLoaded', () => {
    const voucherTable = document.querySelector('[data-voucher-table] tbody');
    if (!voucherTable) return;

    window.Echo.private('admin.vouchers').listen('.voucher.issued', (event) => {
        const row = document.createElement('tr');
        [event.voucher_code, `Campaign #${event.campaign_id}`].forEach((value) => {
            const cell = document.createElement('td');
            cell.textContent = value;
            row.appendChild(cell);
        });

        const statusCell = document.createElement('td');
        const badge = document.createElement('span');
        const isSent = event.sms_status === 'sent';
        badge.className = `badge text-bg-${isSent ? 'success' : 'danger'}`;
        badge.textContent = isSent ? 'SMS sent' : 'SMS failed';
        statusCell.appendChild(badge);
        row.appendChild(statusCell);

        [event.issued_at, '—'].forEach((value) => {
            const cell = document.createElement('td');
            cell.textContent = value;
            row.appendChild(cell);
        });
        voucherTable.prepend(row);
    });
});
