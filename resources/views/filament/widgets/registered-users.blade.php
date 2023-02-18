<x-filament::widget>
    <x-filament::card>
        <x-filament::card.heading>
            User Statistics
        </x-filament::card.heading>
        <div class="flex justify-center items-center gap-6 w-full">
            <div class="text-center">
                <div class="text-3xl">{{ $registered }}</div>
                <div class="text-sm text-gray-400">Registered users</div>
            </div>
            <div class="text-center">
                <div class="text-3xl">{{ $unverified }}</div>
                <div class="text-sm text-gray-400">Unverified</div>
            </div>
            <div class="text-center">
                <div class="text-3xl">{{ $verified }}</div>
                <div class="text-sm text-gray-400">Verified</div>
            </div>
            <div class="text-center">
                <div class="text-3xl">{{ $groupless }}</div>
                <div class="text-sm text-gray-400">Groupless</div>
            </div>
            <div class="text-center">
                <div class="text-3xl">{{ $grouped }}</div>
                <div class="text-sm text-gray-400">In Group</div>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
