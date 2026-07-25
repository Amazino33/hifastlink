<x-captive-layout :brand="$brand ?? null">
    @livewire('captive-auth', [
        'brandName'       => $brand?->brand_name,
        'brandColor'      => $brand?->brand_color,
        'brandLogoUrl'    => $brand?->brand_logo ? Storage::url($brand->brand_logo) : null,
        'brandHeading'    => $brand?->brand_heading,
        'brandSubheading' => $brand?->brand_subheading,
        'brandButtonText' => $brand?->brand_button_text,
        'brandHelpText'        => $brand?->brand_help_text,
        'brandInstructions'    => $brand?->brand_instructions,
    ])
</x-captive-layout>
