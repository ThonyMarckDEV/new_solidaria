<template>
    <Dialog :open="modal" @update:open="emit('emit-close', false)">
        <DialogContent class="sm:max-w-[425px]">
            <DialogHeader>
                <DialogTitle>Nuevo Movimiento</DialogTitle>
            </DialogHeader>

            <!-- Form to Add Product -->
            <form @submit.prevent="onAddProduct" class="space-y-4">
                <FormField v-slot="{ componentField }" name="product">
                    <FormItem>
                        <FormLabel>Producto *</FormLabel>
                        <FormControl>
                            <ProductCombobox v-bind="componentField" @select="onProductSelect" :initialId="null" />
                        </FormControl>
                        <div class="flex gap-2 mt-2">
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                :class="{ 'bg-gray-200 dark:bg-gray-700': selectedType === 'Caja' }"
                                @click="selectedType = 'Caja'"
                            >
                                Caja
                            </Button>
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                :class="{ 'bg-gray-200 dark:bg-gray-700': selectedType === 'Fracción' }"
                                @click="selectedType = 'Fracción'"
                            >
                                Fracción
                            </Button>
                            <Button 
                                type="button" 
                                variant="outline" 
                                size="sm" 
                                :class="{ 'bg-gray-200 dark:bg-gray-700': selectedType === 'Ambas' }"
                                @click="selectedType = 'Ambas'"
                            >
                                Ambas
                            </Button>
                        </div>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <FormField v-slot="{ componentField }" name="lot">
                    <FormItem>
                        <FormLabel>Lote *</FormLabel>
                        <FormControl>
                            <Input v-bind="componentField" placeholder="" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <FormField v-slot="{ componentField }" name="expiry_date">
                    <FormItem>
                        <FormLabel>Fecha de Vencimiento *</FormLabel>
                        <FormControl>
                            <Input type="date" v-bind="componentField" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <FormField v-slot="{ componentField }" name="total_price">
                    <FormItem>
                        <FormLabel>Precio Total *</FormLabel>
                        <FormControl>
                            <Input type="number" step="0.01" v-bind="componentField" placeholder="Ingrese el precio" @input="updateUnitPrice" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <FormField v-slot="{ componentField }" name="unit_price">
                    <FormItem>
                        <FormLabel>Precio Unitario *</FormLabel>
                        <FormControl>
                            <Input type="number" step="0.01" v-bind="componentField" readonly />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <!-- Footer Actions -->
                <div class="flex justify-end gap-3">
                    <Button type="button" variant="outline" @click="emit('emit-close', false)">Cancelar</Button>
                    <Button type="submit">Guardar</Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import ProductCombobox from '@/components/Inputs/ProductCombobox.vue'; // Adjust the path as needed
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import * as z from 'zod';
import { ref } from 'vue';

// Props and Emits
const props = defineProps<{
    modal: boolean;
}>();

const emit = defineEmits<{
    (e: 'emit-close', open: boolean): void;
    (e: 'add-product', product: { product_id: number; quantity: number; name: string; type: string; lot: string; expiry_date: string; unit_price: number }): void;
}>();

// State for selected product and type
const selectedProductId = ref<number | null>(null);
const selectedProductName = ref<string | null>(null);
const selectedType = ref<string>('Caja'); // Default to 'Caja'

// Form validation schema
const formSchema = toTypedSchema(
    z.object({
        product: z.string().min(1, 'Seleccione un producto'),
        lot: z.string().min(1, 'El lote es requerido'),
        expiry_date: z.string().min(1, 'La fecha de vencimiento es requerida'),
        total_price: z.number({ message: 'El precio total debe ser un número' }).min(0, 'El precio total debe ser mayor o igual a 0'),
        unit_price: z.number({ message: 'El precio unitario debe ser un número' }).min(0, 'El precio unitario debe ser mayor o igual a 0'),
    })
);

// Form setup
const { handleSubmit, setFieldValue, resetForm, values } = useForm({
    validationSchema: formSchema,
    initialValues: {
        product: '',
        lot: '',
        expiry_date: '',
        total_price: 0,
        unit_price: 0,
    },
});

// Handle product selection
const onProductSelect = (id: number, name: string) => {
    selectedProductId.value = id;
    selectedProductName.value = name;
    setFieldValue('product', name);
};

// Update unit price based on total price
const updateUnitPrice = () => {
    const totalPrice = values.total_price || 0;
    const quantity = 1; // Assuming quantity is 1 as per the original logic
    const unitPrice = totalPrice / quantity;
    setFieldValue('unit_price', unitPrice);
};

// Handle form submission
const onAddProduct = handleSubmit((values) => {
    if (!selectedProductId.value || !selectedProductName.value) {
        return; // Prevent submission if no product is selected
    }

    const product = {
        product_id: selectedProductId.value,
        quantity: 1, // Default quantity, adjust if needed
        name: selectedProductName.value,
        type: selectedType.value,
        lot: values.lot,
        expiry_date: values.expiry_date,
        unit_price: values.unit_price,
    };

    emit('add-product', product);
    resetForm();
    selectedProductId.value = null;
    selectedProductName.value = null;
    selectedType.value = 'Caja'; // Reset type
    emit('emit-close', false);
});
</script>