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
                            <Input v-bind="componentField" placeholder="Buscar producto" />
                        </FormControl>
                        <div class="flex gap-2 mt-2">
                            <Button type="button" variant="outline" size="sm">Caja</Button>
                            <Button type="button" variant="outline" size="sm">Fracción</Button>
                            <Button type="button" variant="outline" size="sm">Ambas</Button>
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
                            <Input type="number" step="0.01" v-bind="componentField" placeholder="Ingrese el precio" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>

                <FormField v-slot="{ componentField }" name="unit_price">
                    <FormItem>
                        <FormLabel>Precio Unitario *</FormLabel>
                        <FormControl>
                            <Input type="number" step="0.01" v-bind="componentField" value="0.00" readonly />
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
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import * as z from 'zod';

// Props and Emits
const props = defineProps<{
    modal: boolean;
}>();

const emit = defineEmits<{
    (e: 'emit-close', open: boolean): void;
    (e: 'add-product', product: { product_id: number; quantity: number; name: string; type: string; lot: string; expiry_date: string; unit_price: number }): void;
}>();

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
const { handleSubmit, setFieldValue, resetForm } = useForm({
    validationSchema: formSchema,
    initialValues: {
        product: '',
        lot: '',
        expiry_date: '',
        total_price: 0,
        unit_price: 0,
    },
});

// Mock product selection (replace with actual ProductCombobox logic if available)
const onAddProduct = handleSubmit((values) => {
    const product = {
        product_id: 1, // Mock ID, replace with actual product ID from ProductCombobox
        quantity: 1, // Default quantity, adjust as needed
        name: values.product,
        type: 'Caja', // Default type, adjust based on button selection
        lot: values.lot,
        expiry_date: values.expiry_date,
        unit_price: values.total_price, // Assuming total_price is per unit for simplicity
    };
    emit('add-product', product);
    resetForm();
    emit('emit-close', false);
});
</script>