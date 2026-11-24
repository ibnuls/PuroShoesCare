<div id="puro-assistant-root" x-data="{
    open: false,
    sending: false,
    draft: '',
    messages: [],
    init() {
        try {
            const data = JSON.parse(localStorage.getItem('puro-chat-v1') || '{}');
            if (Date.now() - (data.at || 0) < 86400000) {
                this.messages = (data.messages || []).filter(m => m && typeof m.text === 'string' && ['user', 'assistant'].includes(m.role)).slice(-20);
            }
        } catch {}
    },
    setOpen(value) {
        this.open = value;
        this.$nextTick(() => value ? this.$refs.question.focus() : this.$refs.launcher.focus());
    },
    save() {
        try {
            localStorage.setItem('puro-chat-v1', JSON.stringify({ at: Date.now(), messages: this.messages.slice(-20) }));
        } catch {}
    },
    async send(question = this.draft) {
        const text = question.trim();
        if (!text || this.sending) return;
        this.sending = true;
        this.$wire.question = text;
        this.$wire.history = this.messages.slice(-6).map(m => ({ role: m.role, text: m.text.slice(0, 2500) }));
        try {
            await this.$wire.send();
        } finally {
            this.sending = false;
        }
    },
    sourceHref(url) {
        try {
            const link = new URL(url, location.origin);
            if (link.origin === location.origin && ['/panduan', '/book', '/layanan'].includes(link.pathname)) return link.href;
        } catch {}
        return @js(route('guide'));
    },
    answer(result) {
        if (this.draft.trim() === result.question) this.draft = '';
        this.messages.push(
            { role: 'user', text: result.question },
            { role: 'assistant', text: result.response.answer, sources: result.response.sources || [], handoff: result.response.handoff, whatsapp: result.response.whatsapp || null, draft: result.response.draft || null },
        );
        this.messages = this.messages.slice(-20);
        this.save();
        this.$nextTick(() => { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight });
    }
}" @chat-answer.window="answer($event.detail)" @keydown.escape.window="if (open) setOpen(false)">
    <button type="button" class="puro-assistant-launcher" aria-label="Buka Asisten PURO" aria-controls="puro-assistant-panel" x-ref="launcher" x-show="!open" :aria-expanded="open" @click="setOpen(true)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5H7l-4 3v-8.5A8.5 8.5 0 0 1 11.5 5H13a7 7 0 0 1 7 6.5Z"/><path d="M8 11h8M8 14h5M18 2v5M15.5 4.5h5"/></svg><span>Asisten PURO</span>
    </button>
    <section class="puro-assistant-panel" id="puro-assistant-panel" role="dialog" aria-label="Asisten PURO" x-show="open" x-cloak>
        <header class="puro-assistant-head">
            <div class="puro-assistant-avatar" aria-hidden="true">P<span>.</span></div>
            <div><h2>Asisten PURO</h2><p>Panduan layanan & bantuan · Gemini</p></div>
            <button type="button" class="puro-assistant-close" aria-label="Tutup chatbot" @click="setOpen(false)">×</button>
        </header>
        <div class="puro-assistant-links">
            <a href="{{ route('guide') }}" wire:navigate @click="setOpen(false)">Panduan pelanggan ↗</a>
            <a href="{{ route('services') }}#puro-services-faq" wire:navigate @click="setOpen(false)">FAQ</a>
            <a href="{{ route('track') }}" wire:navigate @click="setOpen(false)">Cek status</a>
        </div>
        <div class="puro-assistant-log chat-messages" role="log" aria-live="polite" aria-relevant="additions" aria-label="Percakapan dengan Asisten PURO" x-ref="messages">
            <article class="puro-assistant-message assistant" x-show="!messages.length">
                <small>Asisten PURO</small><p>Halo! Mau tanya cara pesan, treatment, biaya jemput, atau pembayaran?</p>
            </article>
            <template x-for="(message, index) in messages" :key="index">
                <article class="puro-assistant-message" :class="message.role">
                    <small x-text="message.role === 'user' ? 'Kamu' : 'Asisten PURO'"></small><p x-text="message.text"></p>
                    <template x-if="message.sources?.length">
                        <div class="puro-assistant-sources">
                            <small>Sumber panduan</small>
                            <template x-for="source in message.sources"><a :href="sourceHref(source.url)" x-text="source.title + ' ↗'" target="_blank" rel="noopener"></a></template>
                        </div>
                    </template>
                    <template x-if="message.handoff">
                        <div class="puro-assistant-handoff">
                            <strong>Lanjutkan dengan admin</strong>
                            <p>Tinjau ringkasan lalu tekan Kirim di WhatsApp.</p>
                            <details><summary>Lihat ringkasan pesan</summary><p x-text="message.draft"></p></details>
                            <template x-if="message.whatsapp?.startsWith('https://wa.me/')">
                                <a class="puro-assistant-wa" :href="message.whatsapp" target="_blank" rel="noopener noreferrer">Buka WhatsApp admin ↗</a>
                            </template>
                            <template x-if="!message.whatsapp"><a href="{{ route('contact') }}" wire:navigate @click="setOpen(false)">Lihat kontak cabang ↗</a></template>
                        </div>
                    </template>
                </article>
            </template>
            <div wire:loading wire:target="send" class="puro-assistant-message pending">Sedang membaca panduan…</div>
        </div>
        <div class="puro-assistant-shortcuts" aria-label="Contoh pertanyaan" x-show="!messages.length">
            @foreach (['Cara pesan' => 'Bagaimana cara memesan layanan PURO?', 'Biaya jemput' => 'Bagaimana perhitungan biaya jemput?', 'Fast vs Deep Clean' => 'Apa bedanya Fast Clean dan Deep Clean?'] as $label => $question)
                <button type="button" :disabled="sending" @click="send(@js($question))">{{ $label }}</button>
            @endforeach
        </div>
        <form id="puro-assistant-form" class="chat-form" @submit.prevent="send()">
            <label class="sr-only" for="chat-question">Tulis pertanyaan</label>
            <div class="puro-assistant-inputrow">
                <textarea id="chat-question" rows="2" x-ref="question" x-model="draft" placeholder="Tulis pertanyaanmu…" maxlength="1200" autocomplete="off" required></textarea>
                <button type="submit" :disabled="sending" aria-label="Kirim pertanyaan">↑</button>
            </div>
            @error('question') <p class="chat-validation" role="alert">{{ $message }}</p> @enderror
            <p class="puro-assistant-privacy">Riwayat tersimpan 24 jam di browser ini. Hindari data pembayaran sensitif.</p>
        </form>
        <div class="puro-assistant-bottom">
            <button type="button" class="puro-assistant-admin" :disabled="sending" @click="send('Saya mau bicara dengan admin PURO.')">Hubungi admin ↗</button>
            <button type="button" aria-label="Hapus percakapan di perangkat ini" :disabled="sending" @click="messages = []; save(); $refs.question.focus()">Hapus percakapan</button>
        </div>
    </section>
</div>
