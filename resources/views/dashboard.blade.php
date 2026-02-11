<x-app-layout>
	<div class="container py-4">
		<div class="router-filter-container d-flex overflow-auto pb-2 mb-3 no-scrollbar">
			<button class="btn btn-outline-primary rounded-pill me-2 filter-chip active"
					onclick="fetchStats('all', this)">
				<i class="fas fa-globe"></i> All Network
			</button>

			@foreach($allRouters as $router)
				<button class="btn btn-outline-secondary rounded-pill me-2 filter-chip"
						onclick="fetchStats('{{ $router->ip_address }}', this)">
					<i class="fas fa-wifi"></i> {{ $router->name ?? $router->identity ?? $router->nas_identifier }}
				</button>
			@endforeach
		</div>

		<style>
			/* Hide scrollbar for PWA feel */
			.no-scrollbar::-webkit-scrollbar { display: none; }
			.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
			.filter-chip { white-space: nowrap; transition: all 0.2s; }
			.filter-chip.active { background-color: #0d6efd; color: white; border-color: #0d6efd; }
		</style>

		{{-- Dashboard content goes here --}}
	</div>
</x-app-layout>