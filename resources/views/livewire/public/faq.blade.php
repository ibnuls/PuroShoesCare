<section class="services-faq v3610-catalog-section" id="puro-services-faq" aria-labelledby="services-faq-title">
    <div class="services-faq-heading">
        <div><span class="eyebrow">FAQ / PURO CARE</span><h2 id="services-faq-title">Pertanyaan umum<span>.</span></h2></div>
        <p>Kenali treatment yang sesuai sebelum memesan. Klik pertanyaan untuk melihat jawabannya.</p>
    </div>
    <div class="services-faq-list">
        @foreach ([
            "Apa perbedaan Fast Clean dan Deep Clean?" => "Fast Clean membersihkan bagian luar sepatu, sedangkan Deep Clean mencakup pembersihan menyeluruh pada bagian dalam dan luar sepatu.",
            "Kalau sepatu sangat kotor, sebaiknya pilih treatment apa?" => "Kamu bisa memilih Extra Clean, terutama untuk sepatu outdoor atau safety shoes dengan kotoran yang lebih intens.",
            "Apakah PURO menerima perawatan flat shoes dan sandal?" => "Ya, tersedia Woman & Kids Shoes untuk perawatan sepatu wanita seperti flat shoes, sandal, serta sepatu anak.",
            "Apakah sepatu kulit bisa dirawat di PURO?" => "Bisa. Leather Care mencakup pembersihan sepatu kulit serta aplikasi leather balm dan wax.",
            "Apakah midsole sepatu yang menguning bisa diperbaiki?" => "Tersedia treatment Unyellowing untuk membantu mengatasi midsole sepatu yang menguning. Hasil perawatan mengikuti bahan dan kondisi awal sepatu.",
            "Apakah PURO bisa mewarnai ulang sepatu?" => "Bisa. Pilih treatment Repaint untuk memperbarui warna sepatu yang sudah mulai pudar.",
            "Apakah sol sepatu yang terlepas bisa diperbaiki?" => "Bisa. Treatment Reglue membantu merekatkan kembali sol atau bagian sepatu yang terlepas.",
            "Apakah PURO menerima perawatan tas?" => "Ya, Bag Care mencakup pembersihan menyeluruh pada bagian luar dan dalam tas. Pilih layanan Clutch, Shoulder Bag, atau Backpack sesuai jenis tas.",
            "Apakah helm bisa dibersihkan di PURO?" => "Bisa. Helmet Care mencakup pembersihan helm sekaligus perawatan poles untuk membantu menyamarkan bekas lecet pada permukaannya. Kebutuhan treatment dikonfirmasi staf sesuai kondisi helm.",
            "Apakah PURO menerima perawatan topi?" => "Ya, Cap & Hat Care menyediakan pembersihan serta perawatan reshape atau pembentukan ulang topi. Sampaikan kebutuhan perawatanmu agar staf bisa mengonfirmasi penanganan dan penawaran harga.",
        ] as $question => $answer)
            <details class="services-faq-item">
                <summary><span class="services-faq-number" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $question }}</span><span class="services-faq-icon" aria-hidden="true"></span></summary>
                <div class="services-faq-answer"><p>{{ $answer }}</p></div>
            </details>
        @endforeach
    </div>
    <div class="services-faq-footer">
        <p>Sudah menemukan treatment yang cocok?</p>
        <a class="btn btn-dark" href="{{ route('book') }}" wire:navigate>Buat pesanan ↗</a>
        <a class="btn btn-ghost" href="#puro-services-catalogue">Kembali ke kategori ↑</a>
    </div>
</section>
