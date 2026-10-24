<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import PageHeader from '../../Components/PageHeader.vue';
import { confirmDialog } from '../../composables/dialog';

const props = defineProps({ backups: Array, busy: Boolean, retentionDays: Number, maxUpload: Number, canRestore: Boolean });
const page = usePage();
const make = useForm({ password: '', password_confirmation: '' });
const restore = useForm({ file: null, password: '', my_password: '', confirm: '' });
const fileInput = ref(null);
const STATUS = { pendiente: 'En cola', procesando: 'Procesando…', listo: 'Listo', error: 'Error' };
const size = (b) => (b ? (b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.ceil(b / 1024) + ' KB') : '');

let timer = null;
const poll = () => {
    clearInterval(timer);
    if (props.busy) timer = setInterval(() => router.reload({ only: ['backups', 'busy'] }), 4000);
};
onMounted(poll);
watch(() => props.busy, poll);
onBeforeUnmount(() => clearInterval(timer));

const generate = () => make.post('/configuracion/respaldos', { preserveScroll: true, onSuccess: () => make.reset() });
function upload() {
    restore.post('/configuracion/respaldos/restaurar', {
        preserveScroll: true, forceFormData: true,
        onSuccess: () => { restore.reset(); if (fileInput.value) fileInput.value.value = ''; },
    });
}
async function remove(b) {
    if (await confirmDialog('¿Eliminar este respaldo del servidor?')) router.delete(`/configuracion/respaldos/${b.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Copias de seguridad" />
    <PageHeader title="Copias de seguridad" back="/configuracion" />
    <p v-if="page.props.flash?.error" class="mt-[14px] rounded-[2px] bg-[#fdecec] px-3 py-2 text-[13px] text-[#c62828]" role="alert">{{ page.props.flash.error }}</p>

    <div class="mt-[18px] grid gap-[14px] lg:grid-cols-2">
        <form class="s-card p-[18px] text-[13px] text-[#34495e]" @submit.prevent="generate">
            <h2 class="text-[15px] font-bold">Descargar una copia</h2>
            <p class="mt-1">
                Incluye toda la información de la empresa: terceros, productos, documentos, contabilidad, nómina, configuración, usuarios y archivos
                (certificado digital, XML y adjuntos). Sirve para guardarla por tu cuenta o llevarla a otro servidor.
            </p>
            <p class="mt-2 text-[12px] text-muted">El archivo queda cifrado con la contraseña que pongas aquí; sin ella no se puede abrir ni restaurar. Guárdala bien.</p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                <input v-model="make.password" type="password" autocomplete="new-password" placeholder="Contraseña del respaldo (8+)" class="s-input h-[32px]" />
                <input v-model="make.password_confirmation" type="password" autocomplete="new-password" placeholder="Repite la contraseña" class="s-input h-[32px]" />
            </div>
            <p v-if="make.errors.password" class="s-error">{{ make.errors.password }}</p>
            <button type="submit" class="s-btn s-btn-primary mt-3" :disabled="make.processing || busy"><Icon icon="download" /> Generar respaldo</button>
        </form>

        <form v-if="canRestore" class="s-card p-[18px] text-[13px] text-[#34495e]" @submit.prevent="upload">
            <h2 class="text-[15px] font-bold">Restaurar una copia</h2>
            <p class="mt-1">
                <b class="text-[#c62828]">Reemplaza toda la información actual</b> por la del respaldo. Antes de empezar guardamos automáticamente una copia de lo que tienes ahora.
                Tú conservas tu correo y contraseña. Al terminar todos deben volver a ingresar.
            </p>
            <input ref="fileInput" type="file" accept=".zip" class="mt-3 block w-full text-[12px]" @change="restore.file = $event.target.files[0]" />
            <p class="text-[11px] text-muted">Máximo {{ maxUpload }} MB.</p>
            <p v-if="restore.errors.file" class="s-error">{{ restore.errors.file }}</p>
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                <input v-model="restore.password" type="password" autocomplete="off" placeholder="Contraseña del respaldo" class="s-input h-[32px]" />
                <input v-model="restore.my_password" type="password" autocomplete="current-password" placeholder="Tu contraseña" class="s-input h-[32px]" />
            </div>
            <p v-if="restore.errors.password" class="s-error">{{ restore.errors.password }}</p>
            <p v-if="restore.errors.my_password" class="s-error">{{ restore.errors.my_password }}</p>
            <input v-model="restore.confirm" placeholder="Escribe RESTAURAR" class="s-input mt-2 h-[32px] sm:w-[220px]" />
            <p v-if="restore.errors.confirm" class="s-error">{{ restore.errors.confirm }}</p>
            <button type="submit" class="s-btn s-btn-outline mt-3 border-[#c62828] text-[#c62828]" :disabled="restore.processing || busy || !restore.file">
                {{ restore.processing ? `Subiendo ${restore.progress?.percentage ?? 0}%…` : 'Restaurar' }}
            </button>
        </form>
        <div v-else class="s-card p-[18px] text-[13px] text-[#34495e]">
            <h2 class="text-[15px] font-bold">Restaurar una copia</h2>
            <p class="mt-1">Disponible cuando tu suscripción esté al día. Mientras tanto puedes descargar tus copias.</p>
        </div>
    </div>

    <section class="s-card mt-[14px]">
        <h2 class="p-[18px] pb-2 text-[15px] font-bold text-[#34495e]">Historial</h2>
        <p class="px-[18px] text-[12px] text-muted">Los respaldos se borran del servidor a los {{ retentionDays }} días; descárgalos y guárdalos.</p>
        <div class="s-table-wrap mt-2">
            <table class="s-table">
                <thead><tr><th>Fecha</th><th>Tipo</th><th>Detalle</th><th>Usuario</th><th>Estado</th><th>Tamaño</th><th>Acciones</th></tr></thead>
                <tbody>
                    <tr v-for="b in backups" :key="b.id">
                        <td class="whitespace-nowrap">{{ b.created_at }}</td>
                        <td>{{ b.kind === 'respaldo' ? 'Respaldo' : 'Restauración' }}</td>
                        <td>
                            {{ b.label }}
                            <div v-if="b.summary" class="text-[11px] text-muted">
                                <template v-if="b.summary.filas !== undefined">{{ Number(b.summary.filas).toLocaleString('es-CO') }} registros</template>
                                <template v-if="b.summary.archivos !== undefined"> · {{ b.summary.archivos }} archivos</template>
                            </div>
                            <div v-if="b.message" class="text-[11px] text-[#c62828]">{{ b.message }}</div>
                        </td>
                        <td>{{ b.user }}</td>
                        <td><span class="s-badge" :class="{ listo: 's-badge-ok', error: 's-badge-danger' }[b.status] ?? 's-badge-warn'">{{ STATUS[b.status] ?? b.status }}</span></td>
                        <td class="whitespace-nowrap">{{ size(b.size) }}</td>
                        <td class="whitespace-nowrap">
                            <a v-if="b.downloadable" :href="`/configuracion/respaldos/${b.id}/descargar`" class="s-btn-link">Descargar</a>
                            <button v-if="!['pendiente', 'procesando'].includes(b.status)" type="button" class="s-btn-link text-danger" @click="remove(b)">Eliminar</button>
                        </td>
                    </tr>
                    <tr v-if="!backups.length"><td colspan="7" class="text-center text-muted">Aún no hay respaldos.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
