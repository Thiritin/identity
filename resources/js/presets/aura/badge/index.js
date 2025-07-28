export default {
    root: ({ props }) => ({
        class: [
            // Font
            'font-bold',

            {
                'text-xs leading-6': props.size == null,
                'text-lg leading-9': props.size == 'large',
                'text-2xl leading-12': props.size == 'xlarge'
            },

            // Alignment
            'text-center inline-block',

            // Size
            'p-0 px-1',
            {
                'min-w-6 h-6': props.size == null,
                'min-w-9 h-9': props.size == 'large',
                'min-w-12 h-12': props.size == 'xlarge'
            },

            // Shape
            {
                'rounded-full': props.value.length == 1,
                'rounded-[0.71rem]': props.value.length !== 1
            },

            // Color
            'text-primary-inverse',
            {
                'bg-primary': props.severity == null || props.severity == 'primary',
                'bg-surface-500 dark:bg-surface-400': props.severity == 'secondary',
                'bg-green-500 dark:bg-green-400': props.severity == 'success',
                'bg-blue-500 dark:bg-blue-400': props.severity == 'info',
                'bg-orange-500 dark:bg-orange-400': props.severity == 'warning',
                'bg-purple-500 dark:bg-purple-400': props.severity == 'help',
                'bg-red-500 dark:bg-red-400': props.severity == 'danger'
            }
        ]
    })
};
