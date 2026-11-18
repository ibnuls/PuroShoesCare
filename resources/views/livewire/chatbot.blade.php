<div class="puro-chat" x-data="{
    open: false,
    messages: [],
    init() { try { const data = JSON.parse(localStorage.getItem('puro-chat-v1') || '{}'); if (Date.now() - (data.at || 0) < 86400000) this.messages = (data.messages || []).filter(m => m && typeof m.text === 'string' && ['user', 'assistant'].includes(m.role)).slice(-20) } catch {} },
    save() { try { localStorage.setItem('puro-chat-v1', JSON.stringify({ at: Date.now(), messages: this.messages.slice(-20) })) } catch {} },
    answer(d) {
        this.messages.push({ role: 'user', text: d.question }, { role: 'assistant', text: d.response.answer, sources: d.response.sources || [], handoff: d.response.handoff, whatsapp: d.response.whatsapp || null, draft: d.response.draft || null });
        this.messages = this.messages.slice(-20);
        this.save();
        this.$nextTick(() => { this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight })
    }
}" @chat-answer.window="answer($event.detail)"
    @keydown.escape.window="open=false">
    <button class="chat-launcher" type="button" @click="open=!open;$nextTick(()=>{if(open)$refs.question.focus()})"
        :aria-expanded="open" aria-controls="puro-chat-panel"><span aria-hidden="true">✳</span><span>Bantuan
            PURO</span></button>
    <section id="puro-chat-panel" class="chat-panel" x-show="open" x-cloak role="dialog" aria-label="Asisten PURO"
        aria-modal="false">
        <header class="chat-header">
            <div><span class="chat-led"></span><strong>Asisten PURO</strong><small>Panduan layanan · Gemini AI</small>
            </div>
            <div><button type="button" @click="messages=[];save()"
                    aria-label="Hapus percakapan di perangkat ini">↺</button><button type="button" @click="open=false"
                    aria-label="Tutup chatbot">×</button></div>
        </header>
        <div class="chat-messages" x-ref="messages">
            <div class="chat-message assistant" x-show="!messages.length"><small>ASISTEN PURO</small>
                <p>Halo! Mau tanya cara pesan, treatment, biaya jemput, atau pembayaran?</p>
                <p class="small">Jawaban bersumber dari panduan. Untuk keputusan transaksi, lanjutkan ke admin.</p>
            </div><template x-for="(message,index) in messages" :key="index">
                <article class="chat-message" :class="message.role"><small
                        x-text="message.role==='user'?'KAMU':'ASISTEN PURO'"></small>
                    <p x-text="message.text"></p><template x-if="message.sources?.length">
                        <div class="chat-sources"><strong>Sumber informasi</strong><template
                                x-for="source in message.sources"><a
                                    :href="['/panduan', '/book', '/layanan'].some(path => source.url?.startsWith(window
                                        .location.origin + path)) ? source.url : '/panduan'"
                                    x-text="source.title" target="_blank" rel="noopener"></a></template></div>
                    </template><template x-if="message.handoff">
                        <div class="chat-handoff"><strong>Lanjutkan dengan admin</strong>
                            <p class="small">Tinjau ringkasan lalu tekan Kirim di WhatsApp.</p>
                            <details>
                                <summary>Lihat ringkasan pesan</summary>
                                <p x-text="message.draft"></p>
                            </details><template x-if="message.whatsapp?.startsWith('https://wa.me/')"><a
                                    class="btn btn-admin btn-wide" :href="message.whatsapp" target="_blank"
                                    rel="noopener noreferrer">Buka WhatsApp admin ↗</a></template><template
                                x-if="!message.whatsapp"><a class="inline-link" href="{{ route('contact') }}"
                                    wire:navigate @click="open=false">Lihat kontak cabang ↗</a></template>
                        </div>
                    </template>
                </article>
            </template>
            <div wire:loading wire:target="send" class="chat-message assistant">Sedang membaca panduan…</div>
        </div>
        <form class="chat-form"
            x-on:submit.prevent="$wire.history=messages.slice(-6).map(m=>({role:m.role,text:m.text.slice(0,2500)}));$wire.send()">
            <label class="sr-only" for="chat-question">Tulis pertanyaan</label><input id="chat-question"
                x-ref="question" wire:model="question" placeholder="Tulis pertanyaanmu…" maxlength="1200"
                autocomplete="off" required><button class="btn btn-dark" type="submit" wire:loading.attr="disabled"
                wire:target="send" aria-label="Kirim pertanyaan">↗</button>
        </form>
        @error('question')
            <p class="chat-validation">{{ $message }}</p>
        @enderror
        <p class="chat-privacy">Riwayat tersimpan 24 jam di browser ini. Jangan tulis PIN, OTP, atau data pembayaran
            sensitif.</p>
    </section>
</div>
