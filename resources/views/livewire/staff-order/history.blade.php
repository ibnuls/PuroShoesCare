    @if ($order->treatments->isNotEmpty())
        <details class="payment-review-history">
            <summary>Dokumentasi treatment & QC</summary>
            @foreach ($order->treatments as $t)
                <article class="cycle-card">
                    <h3>Siklus {{ $t->cycle_no }} · {{ $t->status }}</h3>
                    <p>{{ $t->notes }}</p>
                    <div class="photo-strip">
                        @foreach ($t->photos as $p)
                            <figure><a href="{{ $p->media->url }}" target="_blank" rel="noopener"><img
                                        src="{{ $p->media->url }}"
                                        alt="{{ $p->phase }} siklus {{ $t->cycle_no }}" loading="lazy"></a>
                                <figcaption>{{ $p->phase }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                    @if ($t->qc)
                        <div class="notice {{ $t->qc->result === 'PASSED' ? 'success' : 'warn' }}">QC:
                            {{ $t->qc->result }}
                            · {{ $t->qc->notes }}</div>
                    @endif
                </article>
            @endforeach
        </details>
    @endif
    @if ($user->isRole('admin') && $user->allows('payments.review') && $order->quotes->isNotEmpty())
        <details class="payment-review-history">
            <summary>Riwayat bukti pembayaran</summary>
            @foreach ($order->quotes as $quote)
                @foreach ($quote->submissions as $s)
                    <article class="submission-row"><strong>Tagihan v{{ $quote->version }} / Upaya
                            #{{ $s->attempt_no }}</strong><span class="pill neutral">{{ $s->decision }}</span>
                        <p>{{ $s->reference->canonical_reference }} ·
                            {{ \App\Support\Puro::money($s->claimed_amount) }} ·
                            {{ \App\Support\Puro::date($s->submitted_at) }}</p>
                        <p>{{ $s->review_reason }}</p><a href="{{ $s->proof->url }}" target="_blank" rel="noopener"
                            class="inline-link">Buka bukti tersimpan ↗</a>
                    </article>
                @endforeach
            @endforeach
        </details>
    @endif
