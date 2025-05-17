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
            <FormMessage />
          </FormItem>
        </FormField>

        <!-- Type selection buttons (shown only if product is selected) -->
        <div v-if="selectedProduct" class="flex gap-2 mt-2">
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
            v-if="selectedProduct?.state_fraction"
            type="button"
            variant="outline"
            size="sm"
            :class="{ 'bg-gray-200 dark:bg-gray-700': selectedType === 'Fracción' }"
            @click="selectedType = 'Fracción'"
          >
            Fracción
          </Button>
          <Button
            v-if="selectedProduct?.state_fraction"
            type="button"
            variant="outline"
            size="sm"
            :class="{ 'bg-gray-200 dark:bg-gray-700': selectedType === 'Ambas' }"
            @click="selectedType = 'Ambas'"
          >
            Ambas
          </Button>
        </div>

        <!-- Quantity inputs based on selectedType and state_fraction -->
        <div v-if="selectedProduct" class="space-y-4">
          <!-- Caja input -->
          <FormField v-if="selectedType === 'Caja' || selectedType === 'Ambas'" v-slot="{ componentField }" name="boxes">
            <FormItem>
              <FormLabel>Cajas</FormLabel>
              <FormControl>
                <Input
                  type="number"
                  v-bind="componentField"
                  min="0"
                  @input="handleBoxesInput"
                  placeholder="Ingrese cantidad de cajas"
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>

          <!-- Fracción input -->
          <FormField
            v-if="selectedProduct?.state_fraction && (selectedType === 'Fracción' || selectedType === 'Ambas')"
            v-slot="{ componentField }"
            name="fractions"
          >
            <FormItem>
              <FormLabel>Fracciones (Máximo {{ selectedProduct?.fraction }})</FormLabel>
              <FormControl>
                <Input
                  type="number"
                  v-bind="componentField"
                  :max="selectedProduct?.fraction"
                  min="0"
                  @input="handleFractionsInput"
                  placeholder="Ingrese cantidad de fracciones"
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          </FormField>
        </div>

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
              <Input
                type="number"
                step="0.01"
                v-bind="componentField"
                placeholder="Ingrese el precio"
                @input="updateUnitPrice"
              />
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
import ProductCombobox from '@/components/Inputs/ProductCombobox.vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import * as z from 'zod';
import { ref } from 'vue';

export interface ProductResource {
  id: number;
  name: string;
  composition: string;
  presentation: string;
  form_farm: string;
  barcode: string;
  laboratory_id: number;
  laboratory: string;
  category_id: number;
  category: string;
  fraction: number;
  state_fraction: boolean;
  state_igv: boolean;
  state: boolean;
}

// Props and Emits
const props = defineProps<{
  modal: boolean;
}>();

const emit = defineEmits<{
  (e: 'emit-close', open: boolean): void;
  (e: 'add-product', product: {
    product_id: number;
    boxes: number;
    fractions: number;
    name: string;
    type: string;
    lot: string;
    expiry_date: string;
    unit_price: number;
  }): void;
}>();

// State
const selectedProduct = ref<ProductResource | null>(null);
const selectedType = ref<string>('Caja');

// Form validation schema
const formSchema = toTypedSchema(
  z.object({
    product: z.string().min(1, 'Seleccione un producto'),
    boxes: z.number({ message: 'La cantidad de cajas debe ser un número' }).min(0, 'La cantidad de cajas debe ser mayor o igual a 0').optional(),
    fractions: z
      .number({ message: 'La cantidad de fracciones debe ser un número' })
      .min(0, 'La cantidad de fracciones debe ser mayor o igual a 0')
      .optional(),
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
    boxes: 0,
    fractions: 0,
    lot: '',
    expiry_date: '',
    total_price: 0,
    unit_price: 0,
  },
});

// Handle product selection
const onProductSelect = (product: ProductResource | null) => {
  selectedProduct.value = product;
  selectedType.value = 'Caja';
  if (product) {
    setFieldValue('product', product.name);
    setFieldValue('boxes', 0);
    setFieldValue('fractions', 0);
  } else {
    setFieldValue('product', '');
    setFieldValue('boxes', 0);
    setFieldValue('fractions', 0);
  }
};

// Handle boxes input
const handleBoxesInput = () => {
  const boxes = values.boxes || 0;
  setFieldValue('boxes', Math.max(0, boxes)); 
  updateUnitPrice();
};


const handleFractionsInput = () => {
  if (!selectedProduct.value) return;

  let fractions = values.fractions || 0;
  const maxFractions = selectedProduct.value.fraction;

  if (selectedType.value === 'Ambas' && fractions > maxFractions) {
    const extraBoxes = Math.floor(fractions / maxFractions);
    fractions = fractions % maxFractions;
    setFieldValue('boxes', (values.boxes || 0) + extraBoxes);
    setFieldValue('fractions', fractions);
  } else if (fractions > maxFractions) {
    setFieldValue('fractions', maxFractions);
  } else if (fractions < 0) {
    setFieldValue('fractions', 0);
  }

  updateUnitPrice();
};


const updateUnitPrice = () => {
  const totalPrice = parseFloat(values.total_price) || 0;
  let unitPrice = 0;

  if (selectedProduct.value) {
    const boxes = parseFloat(values.boxes) || 0;
    const fractions = parseFloat(values.fractions) || 0;
    const igvFactor = selectedProduct.value.state_igv ? 1.18 : 1;

    if (selectedType.value === 'Caja') {
      unitPrice = boxes > 0 ? (totalPrice / boxes) * igvFactor : 0;
    } else if (selectedType.value === 'Fracción') {
      unitPrice = fractions > 0 ? (totalPrice / fractions) * igvFactor : 0;
    } else if (selectedType.value === 'Ambas') {
      unitPrice = boxes > 0 ? (totalPrice / boxes) * igvFactor : 0;
    }
  }

  setFieldValue('unit_price', unitPrice > 0 ? Number(unitPrice.toFixed(2)) : 0);
};


const onAddProduct = handleSubmit((values) => {
  if (!selectedProduct.value) {
    return; 
  }

  const product = {
    product_id: selectedProduct.value.id,
    boxes: values.boxes || 0,
    fractions: selectedProduct.value.state_fraction && (selectedType.value === 'Fracción' || selectedType.value === 'Ambas') ? (values.fractions || 0) : 0,
    name: selectedProduct.value.name,
    type: selectedType.value,
    lot: values.lot,
    expiry_date: values.expiry_date,
    unit_price: values.unit_price,
  };

  emit('add-product', product);
  resetForm();
  selectedProduct.value = null;
  selectedType.value = 'Caja';
  emit('emit-close', false);
});
</script>