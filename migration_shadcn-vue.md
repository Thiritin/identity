# Migration from PrimeVue Volt to shadcn-vue

This document outlines the migration plan from PrimeVue Volt components to shadcn-vue components.

## Components Used in the Project

Based on the analysis of the codebase, the following PrimeVue Volt components are currently in use:

1. AccordionContent
2. AccordionHeader
3. AccordionPanel
4. Accordion
5. AutoComplete
6. AvatarGroup
7. Avatar
8. Badge
9. Breadcrumb
10. ButtonGroup
11. Button
12. Card
13. Checkbox
14. Chip
15. ConfirmDialog
16. ContrastButton
17. DangerButton
18. DataTable
19. DataView
20. DatePicker
21. Dialog
22. Divider
23. Drawer
24. Fieldset
25. Fluid
26. Inplace
27. InputMask
28. InputNumber
29. InputOtp
30. InputText
31. Listbox
32. Menu (TabMenu)
33. Message (InlineMessage)
34. MeterGroup
35. MultiSelect
36. OverlayBadge
37. Paginator
38. Panel
39. Password
40. Popover
41. ProgressBar
42. RadioButton
43. Rating
44. SecondaryButton
45. SelectButton
46. Select (Dropdown)
47. Skeleton
48. Slider
49. Splitter
50. StepItem
51. StepList
52. StepPanels
53. StepPanel
54. Stepper
55. Step
56. TabList
57. TabPanels
58. TabPanel
59. Tabs (TabView)
60. Tab
61. Tag
62. Textarea
63. Timeline
64. Toast
65. ToggleButton
66. ToggleSwitch
67. Toolbar
68. Tree

## Migration Strategy

### Installation

First, install shadcn-vue:

```bash
npx shadcn-vue@latest init
```

Follow the prompts to set up shadcn-vue in your project.

### Component Mapping

Below is a mapping of PrimeVue Volt components to their shadcn-vue equivalents:

| PrimeVue Volt Component | shadcn-vue Component | Notes |
|-------------------------|----------------------|-------|
| Accordion, AccordionPanel, etc. | Accordion | Use `<Accordion>`, `<AccordionItem>`, `<AccordionTrigger>`, and `<AccordionContent>` |
| Avatar, AvatarGroup | Avatar | Use `<Avatar>`, `<AvatarImage>`, and `<AvatarFallback>` |
| Badge | Badge | Direct replacement |
| Button, SecondaryButton, etc. | Button | Use with different variants: `primary`, `secondary`, `destructive`, etc. |
| Card | Card | Use `<Card>`, `<CardHeader>`, `<CardTitle>`, `<CardDescription>`, `<CardContent>`, and `<CardFooter>` |
| Checkbox | Checkbox | Use `<Checkbox>` with `<Label>` |
| Dialog, ConfirmDialog | Dialog | Use `<Dialog>`, `<DialogTrigger>`, `<DialogContent>`, `<DialogHeader>`, `<DialogTitle>`, `<DialogDescription>`, and `<DialogFooter>` |
| Divider | Separator | Direct replacement |
| Drawer | Sheet | Use `<Sheet>`, `<SheetTrigger>`, `<SheetContent>`, etc. |
| InputText | Input | Direct replacement |
| InputNumber | Input | Use with type="number" |
| Menu | DropdownMenu | Use `<DropdownMenu>`, `<DropdownMenuTrigger>`, `<DropdownMenuContent>`, `<DropdownMenuItem>`, etc. |
| Message, InlineMessage | Alert | Use with different variants: `default`, `destructive`, etc. |
| Password | Input | Use with type="password" |
| ProgressBar | Progress | Direct replacement |
| RadioButton | RadioGroup | Use `<RadioGroup>` and `<RadioGroupItem>` |
| Select, Dropdown | Select | Use `<Select>`, `<SelectTrigger>`, `<SelectValue>`, `<SelectContent>`, `<SelectItem>`, etc. |
| Tabs, TabPanel, etc. | Tabs | Use `<Tabs>`, `<TabsList>`, `<TabsTrigger>`, and `<TabsContent>` |
| Tag | Badge | Use with different variants |
| Textarea | Textarea | Direct replacement |
| Toast | Toast | Use `<Toaster>` and the `useToast` hook |
| ToggleSwitch | Switch | Direct replacement |

### Components without Direct Equivalents

Some PrimeVue Volt components don't have direct equivalents in shadcn-vue. For these, you may need to:

1. Use a combination of shadcn-vue components
2. Create custom components based on shadcn-vue primitives
3. Use third-party libraries that integrate well with shadcn-vue

Examples:

- **DataTable**: Consider using `@tanstack/vue-table` which integrates well with shadcn-vue
- **DatePicker**: Consider using `v-calendar` or another date picker library
- **MultiSelect**: Can be built using the `<Command>` component or a combination of `<Select>` and `<Checkbox>` components

## Migration Steps

1. Install shadcn-vue and its dependencies
2. Install specific components as needed:
   ```bash
   npx shadcn-vue@latest add button
   npx shadcn-vue@latest add input
   # etc.
   ```
3. Replace PrimeVue Volt components one by one, starting with the most commonly used ones
4. Update component props and event handlers to match shadcn-vue's API
5. Update styling as needed to maintain consistency

## Example Migration

### Before (PrimeVue Volt):

```vue
<template>
  <div>
    <InputText v-model="text" placeholder="Enter text" />
    <Button @click="submit">Submit</Button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import InputText from '@Shared/Components/volt/InputText.vue'
import Button from '@Shared/Components/volt/Button.vue'

const text = ref('')

const submit = () => {
  // ...
}
</script>
```

### After (shadcn-vue):

```vue
<template>
  <div>
    <Input v-model="text" placeholder="Enter text" />
    <Button @click="submit">Submit</Button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

const text = ref('')

const submit = () => {
  // ...
}
</script>
```

## Next Steps

After creating this migration plan:

1. Install shadcn-vue and its dependencies
2. Install the Switch component as specified in the requirements:
   ```bash
   npx shadcn-vue@latest add switch
   ```
3. Start with a small component (like Button or Input) and migrate it across the entire application
4. Test thoroughly after each component migration
5. Gradually migrate more complex components
6. Update documentation and component usage guidelines for your team
