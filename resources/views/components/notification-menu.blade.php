@props(['url', 'label' => 'Notifikasi'])

<div class="notification-root" data-notification-root data-url="{{ $url }}">
    <button class="notification-trigger" type="button" aria-label="{{ $label }}" aria-expanded="false" aria-controls="notification-panel">
        <x-icon name="bell" />
        <span class="notification-count" data-notification-count hidden>0</span>
    </button>
    <div class="notification-panel" id="notification-panel" data-notification-panel hidden>
        <div class="notification-panel-head">
            <strong>{{ $label }}</strong>
            <span data-notification-status aria-live="polite">Memuat...</span>
        </div>
        <div class="notification-list" data-notification-list></div>
    </div>
</div>

<script>
    (() => {
        const root = document.querySelector('[data-notification-root]');
        if (!root) return;

        const trigger = root.querySelector('.notification-trigger');
        const panel = root.querySelector('[data-notification-panel]');
        const count = root.querySelector('[data-notification-count]');
        const list = root.querySelector('[data-notification-list]');
        const status = root.querySelector('[data-notification-status]');

        const render = (items) => {
            list.replaceChildren();
            if (!items.length) {
                const empty = document.createElement('p');
                empty.className = 'notification-empty';
                empty.textContent = 'Tidak ada aktivitas baru.';
                list.append(empty);
                return;
            }

            items.forEach((item) => {
                const link = document.createElement('a');
                const title = document.createElement('strong');
                const message = document.createElement('span');
                const target = new URL(item.url, window.location.href);

                link.className = 'notification-item';
                link.href = target.origin === window.location.origin ? target.href : '#';
                title.textContent = item.title || 'Notifikasi';
                message.textContent = item.message || '';
                link.append(title, message);
                list.append(link);
            });
        };

        const refresh = async () => {
            try {
                const response = await fetch(root.dataset.url, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!response.ok) throw new Error(`Notifikasi gagal dimuat (${response.status}).`);

                const data = await response.json();
                const unread = Number(data.count) || 0;
                count.textContent = unread > 99 ? '99+' : String(unread);
                count.hidden = unread === 0;
                render(Array.isArray(data.items) ? data.items : []);
                status.textContent = unread ? `${unread} aktivitas perlu diperiksa` : 'Diperbarui otomatis';
                window.dispatchEvent(new CustomEvent('notifications:updated', { detail: data }));
            } catch (error) {
                status.textContent = 'Notifikasi sementara tidak dapat dimuat.';
                console.error(error);
            }
        };

        trigger.addEventListener('click', () => {
            const open = panel.hidden;
            panel.hidden = !open;
            trigger.setAttribute('aria-expanded', String(open));
            if (open) refresh();
        });

        refresh();
        window.setInterval(refresh, 15000);
    })();
</script>
