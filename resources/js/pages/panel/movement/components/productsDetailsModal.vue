<template>
    <Dialog :open="modal" @update:open="closeModal">
        <DialogContent class="sm:max-w-[850px]">
            <DialogHeader>
                <DialogTitle>Agregar Productos al Movimiento</DialogTitle>
                <DialogDescription>
                    Agrega productos al movimiento ID: {{ movementData.id }}. Asegúrate de verificar las cantidades.
                </DialogDescription>
            </DialogHeader>

            <!-- Movement Details -->
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg mb-4">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Código</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ movementData.code }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Proveedor</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ movementData.supplier?.name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Fecha de Emisión</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ formatDate(movementData.issue_date) }}</p>
                    </div>
                </div>
            </div>

            <!-- Form to Add Products -->
            <form @submit="onAddProduct" class="py-4">
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <FormField v-slot="{ componentField }" name="product_id">
                        <FormItem>
                            <FormLabel>Producto</FormLabel>
                            <FormControl>
                                <ProductCombobox @select="onSelectProduct" :initialId="null" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField v-slot="{ componentField }" name="quantity">
                        <FormItem>
                            <FormLabel>Cantidad</FormLabel>
                            <FormControl>
                                <Input type="number" min="1" v-bind="componentField" placeholder="Cantidad" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <div class="flex items-end">
                        <Button type="submit" variant="outline" class="w-full">
                            <Plus class="w-4 h-4 mr-2" />
                            Agregar Producto
                        </Button>
                    </div>
                </div>
            </form>

            <!-- Table of Selected Products -->
            <div v-if="selectedProducts.length > 0" class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Productos Seleccionados</h3>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="text-center">Producto</TableHead>
                            <TableHead class="text-center">Cantidad</TableHead>
                            <TableHead class="text-center">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(product, index) in selectedProducts" :key="index">
                            <td class="text-center">{{ product.name }}</td>
                            <td class="text-center">{{ product.quantity }}</td>
                            <td class="text-center">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    class="text-red-600 hover:text-red-800"
                                    @click="removeProduct(index)"
                                >
                                    <Trash class="w-4 h-4" />
                                    <span class="sr-only">Eliminar producto</span>
                                </Button>
                            </td>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Footer Actions -->
            <div class="flex justify-end gap-3">
                <Button type="button" variant="outline" @click="closeModal">Cancelar</Button>
                <Button
                    type="button"
                    :disabled="selectedProducts.length === 0"
                    @click="onSubmit"
                >
                    Guardar Productos
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { ref } from 'vue';
import * as z from 'zod';
import { MovementResource } from '../interface/Movement';
import ProductCombobox from '@/components/Inputs/ProductCombobox.vue';
import { Plus, Trash } from 'lucide-vue-next';

// Props and Emits
const props = defineProps<{
    modal: boolean;
    movementData: MovementResource;
}>();

const emit = defineEmits<{
    (e: 'emit-close', open: boolean): void;
    (e: 'add-products', movementId: number, products: { product_id: number; quantity: number; name: string }[]): void;
}>();

// State for selected products
const selectedProducts = ref<{ product_id: number; quantity: number; name: string }[]>([]);
const selectedProductName = ref<string | null>(null);
const selectedProductId = ref<number | null>(null);

// Form validation schema for adding a single product
const formSchema = toTypedSchema(
    z.object({
        product_id: z.number({ message: 'Seleccione un producto' }),
        quantity: z.number({ message: 'La cantidad debe ser un número' }).min(1, 'La cantidad debe ser al menos 1'),
    })
);

// Form setup
const { handleSubmit, setFieldValue, resetForm } = useForm({
    validationSchema: formSchema,
    initialValues: {
        product_id: null,
        quantity: 1,
    },
});

// Format date function (same as in the table)
const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
        if (dateString.includes('T')) {
            return dateString.split('T')[0];
        }
        if (dateString.includes('/')) {
            const [day, month, year] = dateString.split('/');
            return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
        }
        return dateString;
    } catch (e) {
        console.error('Error formatting date:', e);
        return '';
    }
};

// Handle product selection
const onSelectProduct = (id: number, name: string) => {
    selectedProductId.value = id;
    selectedProductName.value = name;
    setFieldValue('product_id', id);
};

// Add product to the list
const onAddProduct = handleSubmit((values) => {
    if (selectedProductId.value && selectedProductName.value) {
        selectedProducts.value.push({
            product_id: selectedProductId.value,
            quantity: values.quantity,
            name: selectedProductName.value,
        });
        resetForm(); // Reset form after adding
        selectedProductId.value = null;
        selectedProductName.value = null;
    }
});

// Remove product from the list
const removeProduct = (index: number) => {
    selectedProducts.value.splice(index, 1);
};

// Submit all products
const onSubmit = () => {
    emit('add-products', props.movementData.id, selectedProducts.value);
    closeModal();
};

// Close modal
const closeModal = () => {
    selectedProducts.value = []; // Clear selected products on close
    emit('emit-close', false);
};
</script>