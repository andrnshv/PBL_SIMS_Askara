@props(['items' => []])

<nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2 text-xs text-slate-500']) }}>
    @foreach ($items as $item)
        @if (! $loop->first)
            <span aria-hidden="true">/</span>
        @endif

        @if (! $loop->last && ! empty($item['url']))
            <a href="{{ $item['url'] }}" class="transition hover:text-[#4C8DF6]">{{ $item['label'] }}</a>
        @elseif ($loop->last)
            <span class="font-medium text-[#0A1E45]" aria-current="page">{{ $item['label'] }}</span>
        @else
            <span>{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>