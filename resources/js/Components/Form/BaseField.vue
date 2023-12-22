<template>
    <TwoColumnInputLayout>
        <BaseLabel v-if="type !== 'checkbox'" :field-id="fieldId">{{ label }}</BaseLabel>
        <div v-else></div>

        <div class='mt-1 sm:mt-0 sm:col-span-2'>
            <component :is="component"
                       :id="fieldId"
                       v-model="value"
                       :class="[error ? 'border-red-600' : 'border-gray-300']"
                       :hint="hint"
                       :label="label"
                       :placeholder="placeholder"
                       :type="type"
                       v-bind="$attrs"/>
            <FieldError :value="error"/>
        </div>
    </TwoColumnInputLayout>
</template>

<script>
import TwoColumnInputLayout from "@/Components/Form/Layouts/TwoColumnInput.vue";
import BaseLabel from "@/Components/Form/BaseLabel.vue";
import BaseInput from "@/Components/BaseInput.vue";
import ShortTextInput from "@/Components/Form/Inputs/ShortTextInput.vue";
import FieldError from "@/Components/Form/FieldError.vue";
import SelectInput from "@/Components/Form/Inputs/SelectInput.vue";
import LongTextInput from "@/Components/Form/Inputs/LongTextInput.vue";
import FileInput from "@/Components/Form/Inputs/FileInput.vue";
import CheckboxInput from "@/Components/Form/Inputs/CheckboxInput.vue";


export default {
    name: "BaseField",
    emits: ['update:modelValue'],
    components: {
        CheckboxInput,
        FileInput,
        LongTextInput,
        SelectInput,
        FieldError,
        ShortTextInput,
        BaseInput,
        BaseLabel,
        TwoColumnInputLayout
    },
    props: {
        type: {
            type: String,
            default: 'text'
        },
        modelValue: {
            type: [String, null],
            required: false,
        },
        label: {
            type: String,
            default: ''
        },
        language: {
            type: String
        },
        hint: {
            type: String,
            default: ''
        },
        placeholder: {
            type: String,
            default: ''
        },
        options: {
            type: [Array, null],
            default: []
        },
        error: {
            type: String
        },
        fieldId: {
            type: String,
            required: true
        }
    },
    computed: {
        component() {
            if (["text", "password", "email"].includes(this.type)) {
                return ShortTextInput;
            }
            if (this.type === "checkbox") {
                return CheckboxInput;
            }
            if (this.type === "textarea") {
                return LongTextInput;
            }
            if (this.type === "select") {
                return SelectInput;
            }
            if (this.type === "file") {
                return FileInput;
            }
        },
        value: {
            get() {
                if (this.language) {
                    return this.modelValue[this.language];
                }
            },
            set(value) {
                if (this.language) {
                    this.$emit('update:modelValue', {
                        ...this.modelValue,
                        [this.language]: value
                    });
                } else {
                    this.$emit('update:modelValue', value);
                }

            }
        }

    },
    setup(props) {
        const fieldId = "input-" + props.label.trim().replace(/\W/g, '').toLowerCase() + "-" + Math.random().toString(36).slice(2);
        return {
            fieldId
        };
    },
}
</script>

<style scoped>

</style>
