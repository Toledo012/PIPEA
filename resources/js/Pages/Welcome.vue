<script setup>
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import LoginForm from '@/Pages/Auth/LoginForm.vue'
defineProps({
  canLogin: Boolean,
})

const showLogin = ref(false)
</script>

<template>
  <Head title="PIPEA" />

  <div class="min-h-screen bg-[#F2F2F2]">

    <!-- HEADER -->
    <header class="bg-[#2C2C2C] text-white">
      <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
        <div class="text-lg font-semibold tracking-wide">
          SISTEMA PIPEA
        </div>

        <nav class="hidden md:flex gap-6 text-sm">
          <a href="#" class="hover:text-[#00A79D] transition">Inicio</a>
          <a href="#" class="hover:text-[#00A79D] transition">Elemento 1</a>
          <a href="#" class="hover:text-[#00A79D] transition">Elemento 2</a>
          <a href="#" class="hover:text-[#00A79D] transition">Elemento 3</a>
        </nav>
      </div>
    </header>

    <!-- HERO -->
    <section class="bg-white">
      <div class="mx-auto max-w-7xl px-6 py-16 grid md:grid-cols-2 gap-10 items-center">

        <div class="flex justify-center">
          <div class="h-64 w-64 bg-[#E5E5E5] flex items-center justify-center text-[#2C2C2C] font-semibold">
            LOGO PIPEA
          </div>
        </div>

        <div>
          <h1 class="text-3xl font-bold text-[#B3262E] uppercase">
            Sistema de monitoreo y seguimiento
          </h1>

          <p class="mt-6 text-gray-700 leading-relaxed">
            Plataforma institucional para la supervisión y evaluación
            de programas estratégicos.
          </p>

          <div class="mt-8">
            <button
              v-if="canLogin"
              @click="showLogin = true"
              class="inline-block rounded-md bg-[#00A79D] px-8 py-3 text-white font-semibold shadow hover:opacity-90 transition"
            >
              Iniciar sesión
            </button>
          </div>
        </div>

      </div>
    </section>

    <!-- MODAL LOGIN -->
    <div
      v-if="showLogin"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
      @click.self="showLogin = false"
    >
      <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
        <div class="flex items-center justify-between border-b px-6 py-4">
          <h2 class="text-lg font-semibold text-[#2C2C2C]">Iniciar sesión</h2>
          <button @click="showLogin = false" class="text-gray-500 hover:text-black">✕</button>
        </div>

        <div class="px-6 py-5">
          <LoginForm :can-reset-password="true" />
        </div>
      </div>
    </div>

  </div>
</template>