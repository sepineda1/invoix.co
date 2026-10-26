<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

/* Solicitar enlace de restablecimiento (request) o definir la nueva contrasena (reset). */
const props = defineProps({ mode: String, status: String, token: String, email: String });
const form = useForm({ email: props.email ?? '', token: props.token ?? '', password: '', password_confirmation: '' });
const submit = () => (props.mode === 'request' ? form.post('/recuperar-contrasena') : form.post('/restablecer-contrasena', { onFinish: () => form.reset('password', 'password_confirmation') }));
</script>

<template>
    <Head :title="mode === 'request' ? 'Recuperar contraseña' : 'Nueva contraseña'" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-[#f3f6f9]">
        <img src="/brand/invoix.png" alt="invoix.co" class="mb-6 w-[260px]" />
        <form class="s-card w-[390px] max-w-[calc(100vw-24px)] px-[30px] py-[28px] shadow-[0_2px_10px_rgba(0,0,0,.08)]" @submit.prevent="submit">
            <h1 class="text-[22px] font-bold text-[#34495e]">{{ mode === 'request' ? 'Recuperar contraseña' : 'Nueva contraseña' }}</h1>
            <p class="mb-[18px] mt-1 text-[13px] text-[#34495e]">
                {{ mode === 'request' ? 'Te enviaremos un enlace a tu correo para crear una nueva contraseña.' : 'Escribe tu nueva contraseña (mínimo 8 caracteres).' }}
            </p>
            <p v-if="status" class="mb-3 rounded-[2px] bg-[#e6f4dc] px-3 py-2 text-[12px] text-[#2e7d32]">{{ status }}</p>

            <input v-model="form.email" type="email" placeholder="Correo electrónico*" class="s-input h-[34px]" :readonly="mode === 'reset'" />
            <p v-if="form.errors.email" class="s-error">{{ form.errors.email }}</p>
            <template v-if="mode === 'reset'">
                <input v-model="form.password" type="password" autocomplete="new-password" placeholder="Nueva contraseña*" class="s-input mt-[12px] h-[34px]" />
                <input v-model="form.password_confirmation" type="password" autocomplete="new-password" placeholder="Confirmar contraseña*" class="s-input mt-[12px] h-[34px]" />
                <p v-if="form.errors.password" class="s-error">{{ form.errors.password }}</p>
            </template>

            <button type="submit" class="s-btn s-btn-primary mt-[16px] w-full font-bold" :disabled="form.processing">
                {{ mode === 'request' ? 'Enviar enlace' : 'Guardar contraseña' }}
            </button>
            <Link href="/login" class="mt-3 block text-center text-[12px] font-semibold text-primary">Volver al inicio de sesión</Link>
        </form>
    </div>
</template>
