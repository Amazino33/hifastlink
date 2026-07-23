<x-captive-layout :brand="$brand ?? null">
    @livewire('captive-auth', [
        'brandName'    => $brand?->brand_name,
        'brandColor'   => $brand?->brand_color,
        'brandLogoUrl' => $brand?->brand_logo ? Storage::url($brand->brand_logo) : null,
    ])
</x-captive-layout>
