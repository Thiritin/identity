<template>
    <div class="min-h-screen bg-white flex">
        <div class="hidden lg:block relative w-0 flex-1">
            <img alt="EF26 Background Image" class="absolute inset-0 h-full w-full object-cover" src="https://www.eurofurence.org/EF26/assets/images/background_3K_l.jpg" />
        </div>
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <slot></slot>
            </div>
        </div>
    </div>
</template>
