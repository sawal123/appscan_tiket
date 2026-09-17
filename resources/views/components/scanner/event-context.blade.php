@props([
    'name' => 'Belum ada event aktif',
    'date' => '—',
    'location' => '—',
    'label' => 'Event Aktif',
])

<section class="event-context" aria-labelledby="event-name" data-testid="event-context">
    <div>
        <p class="eyebrow" data-testid="event-label">{{ $label }}</p>
        <h1 id="event-name" data-testid="event-name">{{ $name }}</h1>
    </div>
    <dl class="event-meta" data-testid="event-meta">
        <div>
            <dt><i data-lucide="calendar-days"></i><span class="sr-only">Tanggal</span></dt>
            <dd>{{ $date }}</dd>
        </div>
        <div>
            <dt><i data-lucide="map-pin"></i><span class="sr-only">Gate</span></dt>
            <dd>{{ $location }}</dd>
        </div>
    </dl>
</section>
