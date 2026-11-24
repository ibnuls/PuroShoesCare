// Livewire supplies Alpine.
const recentOrdersKey = 'puro-recent-orders';

function recentOrders() {
    try {
        const rows = JSON.parse(localStorage.getItem(recentOrdersKey) || '[]');
        return rows.filter(row => /^PURO-[A-F0-9]{16}$/.test(row.code) && Date.now() - row.at < 2592000000).slice(0, 10);
    } catch {
        return [];
    }
}

document.addEventListener('alpine:init', () => {
    Alpine.data('puroRecentOrders', () => ({
        rows: recentOrders(),

        refresh() {
            this.rows = recentOrders();
        },

        forget() {
            try {
                localStorage.removeItem(recentOrdersKey);
            } catch {}
            this.rows = [];
        },
    }));

    Alpine.data('puroReceipt', () => ({
        copied: false,

        remember(code, label) {
            try {
                const rows = recentOrders().filter(row => row.code !== code);
                rows.unshift({ code, label, at: Date.now() });
                localStorage.setItem(recentOrdersKey, JSON.stringify(rows.slice(0, 10)));
                this.$dispatch('puro-recent-orders-updated');
            } catch {}
        },

        async copy(code) {
            try {
                await navigator.clipboard.writeText(code);
                this.copied = true;
            } catch {
                this.copied = false;
            }
        },
    }));

    Alpine.data('puroDialog', () => ({
        trigger: null,

        open() {
            if (this.$el.open) return;
            this.trigger = document.activeElement;
            this.$el.showModal();
        },

        restoreFocus() {
            this.trigger?.focus();
        },
    }));

    Alpine.data('puroFeatured', () => ({
        position: 1,
        atStart: true,
        atEnd: true,

        init() {
            this.$nextTick(() => this.update());
        },

        update() {
            const track = this.$refs.track;
            const first = track.firstElementChild;
            if (!first) return;

            const step = first.offsetWidth + (parseFloat(getComputedStyle(track).columnGap) || 0);
            this.position = Math.round(track.scrollLeft / step) + 1;
            this.atStart = track.scrollLeft < 2;
            this.atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
        },

        move(direction) {
            const track = this.$refs.track;
            const first = track.firstElementChild;
            if (!first) return;

            const step = first.offsetWidth + (parseFloat(getComputedStyle(track).columnGap) || 0);
            const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
            track.scrollBy({ left: direction * step, behavior: reduceMotion ? 'auto' : 'smooth' });
        },
    }));

    Alpine.data('puroCatalogue', () => ({
        target: 'puro-services-catalogue',

        init() {
            this.update();
        },

        update() {
            const hash = location.hash.slice(1);
            const known = Array.from(this.$refs.categories.querySelectorAll('a'))
                .some(link => link.getAttribute('aria-controls') === hash);
            this.target = known ? hash : 'puro-services-catalogue';
        },
    }));

    Alpine.data('puroServiceDetails', () => ({
        service: {},
        trigger: null,

        show(service) {
            this.service = service;
            this.trigger = document.activeElement;
            this.$el.showModal();
        },

        restoreFocus() {
            this.trigger?.focus();
            this.service = {};
        },
    }));
});
