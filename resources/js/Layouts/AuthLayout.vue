<template>
    <div>
        <div class="absolute left-0 bottom-0 pointer-events-none hidden lg:block">
            <img class="z-0" alt="Background Logo" src="../../assets/bg-logo.svg"/>
        </div>
        <div class="container z-40 px-4 sm:px-12 pt-16 pb-12 mx-auto flex items-center md:min-h-screen md:-mt-12"
             style="max-width:600px">
            <main>
                <slot></slot>
            </main>
        </div>
    </div>
</template>
