<x-captive-layout :brand="$brand ?? null">
    @livewire('captive-auth', [
        'brandName'       => $brand?->brand_name,
        'brandColor'      => $brand?->brand_color,
        'brandLogoUrl'    => $brand?->brand_logo ? Storage::url($brand->brand_logo) : null,
        'brandHeading'    => $brand?->brand_heading,
        'brandSubheading' => $brand?->brand_subheading,
        'brandButtonText'       => $brand?->brand_button_text,
        'brandInputPlaceholder' => $brand?->brand_input_placeholder,
        'brandHelpText'        => $brand?->brand_help_text,
        'brandHelpLinkText'    => $brand?->brand_help_link_text,
        'brandHelpLinkUrl'     => $brand?->brand_help_link_url,
        'brandInstructions'    => $brand?->brand_instructions,
    ])
</x-captive-layout>
