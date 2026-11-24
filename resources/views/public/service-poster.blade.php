<svg xmlns="http://www.w3.org/2000/svg" width="960" height="{{ $height }}" viewBox="0 0 960 {{ $height }}" data-poster-layout="photo-caption">
    <rect width="960" height="{{ $height }}" fill="#fff"/>
    <g font-family="Arial,Helvetica,sans-serif">
        @foreach ($title as $line)
            <text x="480" y="{{ 80 + $loop->index * 70 }}" text-anchor="middle" font-family="Georgia,DejaVu Serif,serif" font-style="italic" font-size="62" font-weight="900" fill="#17233c" stroke="#d7ed35" stroke-width="9" stroke-linejoin="round" paint-order="stroke fill">{{ $line }}</text>
        @endforeach
        @if ($image)
            <image href="{{ $image }}" x="0" y="{{ $imageY }}" width="960" height="540" preserveAspectRatio="xMidYMid slice"/>
        @endif
        @foreach ($description as $line)
            <text x="52" y="{{ $bodyY + $loop->index * 40 }}" font-size="30" fill="#242638">{{ $line }}</text>
        @endforeach
        @foreach ($terms as $line)
            <text x="52" y="{{ $termsY + $loop->index * 36 }}" font-size="26" font-weight="700" fill="#17233c">{{ $line }}</text>
        @endforeach
        @foreach ($notes as $line)
            <text x="52" y="{{ $noteY + $loop->index * 30 }}" font-size="22" fill="#606474">{{ $line }}</text>
        @endforeach
    </g>
</svg>
