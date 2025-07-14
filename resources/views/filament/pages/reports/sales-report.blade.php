<x-filament::page>
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-tight">Laporan Penjualan</h1>
            <div class="flex space-x-2">
                {{ $this->exportAction }}
            </div>
        </div>

        <div class="bg-white rounded-xl shadow">
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament::page>
