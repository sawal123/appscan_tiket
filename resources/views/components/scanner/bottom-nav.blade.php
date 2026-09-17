@props([
    'active' => 'scan',
])

@php
    $items = [
        [
            'key' => 'scan',
            'label' => 'Scan',
            'icon' => 'scan-line',
            'route' => route('scanner.index'),
            'testId' => 'nav-scan-link',
        ],
        [
            'key' => 'verified',
            'label' => 'Terverifikasi',
            'icon' => 'badge-check',
            'route' => route('scanner.verified'),
            'testId' => 'nav-verified-link',
        ],
    ];
@endphp

<nav class="bottom-nav" aria-label="Navigasi utama" data-testid="bottom-navigation">
    @foreach ($items as $item)
        @php($isActive = $active === $item['key'])
        <a
            class="bottom-nav__item {{ $isActive ? 'is-active' : '' }}"
            href="{{ $item['route'] }}"
            data-testid="{{ $item['testId'] }}"
            @if ($isActive) aria-current="page" @endif
        >
            <i data-lucide="{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
