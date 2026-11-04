@props(['active'])
<x-badge :tone="$active ? 'green' : 'slate'">{{ $active ? 'Aktif' : 'Nonaktif' }}</x-badge>
