<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head } from '@inertiajs/vue3'
import LoginForm from '@/Components/Auth/LoginForm.vue'

defineProps({
  canResetPassword: Boolean,
  status: String,
})
</script>

<template>
  <GuestLayout>
    <Head title="Iniciar sesión" />

    <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
      {{ status }}
    </div>

    <LoginForm :can-reset-password="canResetPassword" />
  </GuestLayout>
</template>