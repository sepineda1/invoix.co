<script setup>
import { computed, nextTick, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import CreateMenu from './CreateMenu.vue';
import ConfigMenu from './ConfigMenu.vue';
import { onClickOutside } from '../../composables/clickOutside';

defineProps({ createMenu: { type: Object, required: true }, configMenu: { type: Object, default: () => ({}) } });
const emit = defineEmits(['toggle-menu']);
const showConfig = ref(false);

const page = usePage();
const user = computed(() => page.props.auth?.user);
const company = computed(() => page.props.company);

const showCreate = ref(false);
const showUser = ref(false);
const userMenu = ref(null);
onClickOutside(userMenu, () => (showUser.value = false));

const showHelp = ref(false);
const helpMenu = ref(null);
onClickOutside(helpMenu, () => (showHelp.value = false));
const HELP = [
    ['Centro de ayuda', '/ayuda'], ['Ventas', '/ayuda#ventas'], ['Compras y gastos', '/ayuda#compras'], ['Productos e inventario', '/ayuda#productos'],
    ['Cajas y bancos', '/ayuda#bancos'], ['Nómina', '/ayuda#nomina'], ['Contabilidad', '/ayuda#contabilidad'], ['Reportes', '/ayuda#reportes'],
];

const showBell = ref(false);
const bell = ref(null);
const alerts = ref(null);
onClickOutside(bell, () => (showBell.value = false));
const LEVEL = { danger: 'text-[#c62828]', warn: 'text-[#a86b00]', info: 'text-primary' };
async function loadAlerts() {
    const res = await fetch('/api/notificaciones', { headers: { Accept: 'application/json' } });
    alerts.value = res.ok ? await res.json() : [];
}
function toggleBell() {
    showBell.value = !showBell.value;
    if (showBell.value) loadAlerts();
}
loadAlerts();

/* Buscador de Siigo: se elige que buscar y los resultados salen en tabla; "Todos" agrupa terceros, productos, documentos y pantallas. */
const SEARCH_KINDS = [
    'Todos', 'Facturas', 'Factura de Compra / Gasto', 'Documento soporte', 'Orden de Compra', 'Cotizaciones', 'Recibos de caja', 'Remisiones',
    'Recibo de Pago / Egreso', 'Notas crédito', 'Nota débito', 'Nota débito (Ventas)', 'Comprobantes contables', 'Terceros', 'Ajuste de cartera / proveedores',
    'Ajuste / Saldo inicial de inventario', 'Nota de traslado entre bodegas', 'Notas de ensamble', 'Traslado de dinero', 'Tareas', 'Documentos', 'Usuarios',
    'Productos / Servicios', 'Activos fijos', 'Empleados',
];
const PAGE = 9;
const showSearch = ref(false);
const showKinds = ref(false);
const kind = ref('Facturas');
const term = ref('');
const results = ref([]);
const table = ref(null);
const searched = ref('');
const limit = ref(PAGE);
const searchBox = ref(null);
const active = ref(0);
let timer;
let seq = 0;
onClickOutside(searchBox, () => { showKinds.value = false; searched.value = ''; });
const searchInput = ref(null);
function openSearch() {
    showSearch.value = true;
    nextTick(() => searchInput.value?.focus());
}
function clearSearch() {
    clearTimeout(timer);
    term.value = '';
    searched.value = '';
    results.value = [];
    table.value = null;
    searchInput.value?.focus();
}
function closeSearch() {
    clearSearch();
    showKinds.value = false;
    showSearch.value = false;
}
async function search(more = false) {
    clearTimeout(timer);
    const q = term.value.trim();
    if (q.length < (kind.value === 'Todos' ? 2 : 1)) return (searched.value = '');
    limit.value = more ? limit.value + PAGE * 2 : PAGE;
    const id = ++seq;
    const res = await fetch(`/api/buscar-global?${new URLSearchParams({ tipo: kind.value, q, limite: limit.value })}`, { headers: { Accept: 'application/json' } });
    const data = res.ok ? await res.json() : null;
    if (id !== seq) return;
    if (kind.value === 'Todos') {
        results.value = data ?? [];
        table.value = null;
    } else {
        table.value = data ?? { columns: [], rows: [], more: false };
        results.value = [];
    }
    searched.value = q;
    if (!more) active.value = 0;
}
function onTerm() {
    clearTimeout(timer);
    timer = setTimeout(search, 300);
}
function pickKind(k) {
    kind.value = k;
    showKinds.value = false;
    results.value = [];
    table.value = null;
    searched.value = '';
    searchInput.value?.focus();
    if (term.value.trim()) search();
}
const visible = computed(() => (table.value ? table.value.rows : results.value));
function go(r) {
    if (!r?.href) return;
    closeSearch();
    router.visit(r.href);
}
const grouped = computed(() => results.value.reduce((acc, r, i) => ((acc[r.group] ??= []).push({ ...r, i }), acc), {}));

function logout() {
    router.post('/logout');
}
</script>

<template>
    <header class="sticky top-0 z-30 flex h-[56px] items-center bg-white px-3 text-ink md:px-[22px]">
        <button type="button" class="mr-2 flex h-8 w-8 shrink-0 items-center justify-center text-[18px] text-primary md:hidden" aria-label="Menú" @click="emit('toggle-menu')">
            <Icon icon="bars" />
        </button>
        <Link href="/" class="mr-3 flex shrink-0 items-center md:mr-[36px]">
            <img src="/brand/invoix.png" alt="invoix.co" class="h-[42px] w-auto max-w-[220px] object-contain object-left" />
        </Link>
        <span class="hidden truncate text-[15px] font-semibold sm:inline">{{ user?.name }}</span>

        <div class="ml-auto flex items-center gap-[6px] md:gap-[10px]">
            <div ref="searchBox" :class="showSearch ? 'absolute inset-x-0 top-0 z-10 flex h-[56px] items-center bg-white px-3 md:relative md:inset-auto md:h-auto md:px-0' : 'relative'">
                <button v-if="!showSearch" type="button" class="flex h-8 w-8 items-center justify-center text-[17px] text-primary" title="Buscar" @click="openSearch">
                    <Icon icon="magnifying-glass" />
                </button>
                <div v-else class="flex w-full items-center gap-[10px] md:gap-[14px]">
                    <div class="flex h-[30px] min-w-0 flex-1 items-center rounded-[3px] border border-[#ccc] bg-white md:w-[460px] md:flex-none">
                        <div class="relative h-full shrink-0">
                            <button type="button" class="flex h-full items-center gap-[6px] border-r border-[#ccc] px-[10px] text-[11.5px] text-primary" @click="showKinds = !showKinds">
                                <span class="max-w-[80px] truncate sm:max-w-[150px]">{{ kind }}</span> <Icon icon="caret-down" class="text-[11px]" />
                            </button>
                            <ul v-if="showKinds" class="s-card absolute left-0 top-[32px] z-50 max-h-[290px] w-[200px] overflow-y-auto py-[6px] shadow-lg" role="menu">
                                <li v-for="k in SEARCH_KINDS" :key="k" role="menuitem">
                                    <button type="button" class="block w-full truncate px-[14px] py-[7px] text-left text-[12px] text-ink hover:bg-[#f2f2f2]" :class="k === kind && 'bg-[#e0f2fe]'" @click="pickKind(k)">{{ k }}</button>
                                </li>
                            </ul>
                        </div>
                        <div class="relative h-full min-w-0 flex-1">
                            <input ref="searchInput" v-model="term" class="h-full w-full bg-transparent px-[8px] text-[12.5px] outline-none" :placeholder="kind === 'Todos' ? 'Tercero, producto, documento, cuenta o pantalla' : 'Buscar'"
                                @input="onTerm" @keydown.down.prevent="active = Math.min(active + 1, visible.length - 1)" @keydown.up.prevent="active = Math.max(active - 1, 0)"
                                @keydown.enter.prevent="searched ? go(visible[active]) : search()" @keydown.esc="closeSearch" />
                            <div v-if="searched" class="s-card absolute left-0 top-[31px] z-50 max-h-[460px] min-w-full max-w-[calc(100vw-24px)] overflow-auto shadow-lg" :class="table ? 'w-[345px]' : 'w-[420px] py-2'">
                                <template v-if="table">
                                    <table v-if="table.rows.length" class="w-full text-left text-[12px]">
                                        <thead class="sticky top-0 bg-[#f2f2f2] text-[11.5px] font-semibold text-ink">
                                            <tr><th v-for="col in table.columns" :key="col" class="px-[12px] py-[8px]">{{ col }}</th></tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(r, i) in table.rows" :key="i" class="border-t border-[#eee] text-[#555]" :class="[r.href && 'cursor-pointer hover:bg-[#e0f2fe]', i === active && 'bg-[#e0f2fe]']" @click="go(r)">
                                                <td v-for="(cell, j) in r.cells" :key="j" class="max-w-[220px] truncate px-[12px] py-[7px]">{{ cell }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p v-else class="px-[12px] py-[10px] text-[12px] text-muted">Sin resultados.</p>
                                    <button v-if="table.more" type="button" class="flex w-full items-center gap-[6px] border-t border-[#eee] px-[12px] py-[8px] text-[12px] text-primary hover:bg-[#f5f9fc]" @click="search(true)">
                                        <Icon icon="plus" class="text-[11px]" /> Ver más resultados
                                    </button>
                                </template>
                                <template v-else>
                                    <template v-for="(list, g) in grouped" :key="g">
                                        <div class="px-3 pb-1 pt-2 text-[11px] font-bold uppercase text-muted">{{ g }}</div>
                                        <button v-for="r in list" :key="r.i" type="button" class="block w-full px-3 py-[6px] text-left hover:bg-[#e0f2fe]" :class="r.i === active && 'bg-[#e0f2fe]'" @click="go(r)">
                                            <div class="truncate text-[13px] text-ink">{{ r.label }}</div>
                                            <div v-if="r.sub" class="truncate text-[11px] text-muted">{{ r.sub }}</div>
                                        </button>
                                    </template>
                                    <p v-if="!results.length" class="px-3 py-2 text-[12px] text-muted">Sin resultados.</p>
                                </template>
                            </div>
                        </div>
                        <button v-if="searched" type="button" class="flex h-full w-[30px] shrink-0 items-center justify-center text-[14px] text-primary" title="Limpiar" @click="clearSearch"><Icon icon="xmark" /></button>
                        <button v-else type="button" class="flex h-full w-[30px] shrink-0 items-center justify-center rounded-r-[2px] bg-primary text-[13px] text-white" title="Buscar" @click="search()"><Icon icon="magnifying-glass" /></button>
                    </div>
                    <button type="button" class="text-[12px] text-primary hover:underline" @click="closeSearch">Cancelar</button>
                </div>
            </div>

            <div class="relative">
                <button type="button" class="s-btn s-btn-primary px-[10px]" title="Crear" @click="showCreate = !showCreate">
                    <Icon icon="plus" class="text-[15px]" /> <span class="hidden sm:inline">Crear</span>
                </button>
                <CreateMenu v-if="showCreate" :menu="createMenu" @close="showCreate = false" />
            </div>

            <div ref="helpMenu" class="relative">
                <button type="button" class="hidden s-btn s-btn-outline px-[10px] md:inline-flex" @click="showHelp = !showHelp">
                    Ayuda <Icon icon="chevron-down" class="text-[10px]" />
                </button>
                <button type="button" class="flex h-8 w-8 items-center justify-center text-[18px] text-primary md:hidden" title="Ayuda" @click="showHelp = !showHelp">
                    <Icon icon="circle-question" />
                </button>
                <div v-if="showHelp" class="s-card fixed inset-x-2 top-[56px] z-50 py-2 shadow-lg md:absolute md:inset-x-auto md:right-0 md:top-[42px] md:w-[230px]">
                    <Link v-for="[label, href] in HELP" :key="href" :href="href" class="block px-4 py-[6px] text-[13px] text-ink hover:bg-[#e0f2fe]" :class="href === '/ayuda' && 'font-semibold'" @click="showHelp = false">
                        {{ label }}
                    </Link>
                    <hr class="my-1 border-line" />
                    <a v-if="company?.email" :href="`mailto:${company.email}?subject=Soporte ERP`" class="block px-4 py-[6px] text-[13px] text-ink hover:bg-[#e0f2fe]"><Icon icon="envelope" /> Escribir a soporte</a>
                </div>
            </div>

            <div ref="bell" class="relative">
                <button type="button" class="relative flex h-8 w-8 items-center justify-center text-[18px] text-primary" title="Notificaciones" @click="toggleBell">
                    <Icon icon="bell" />
                    <span v-if="alerts?.length" class="absolute right-0 top-0 flex h-[15px] min-w-[15px] items-center justify-center rounded-full bg-[#e53935] px-[3px] text-[9px] font-bold text-white">{{ alerts.length }}</span>
                </button>
                <div v-if="showBell" class="s-card fixed inset-x-2 top-[56px] z-50 max-h-[calc(100vh-70px)] overflow-y-auto py-2 shadow-lg md:absolute md:inset-x-auto md:right-0 md:top-[42px] md:w-[340px]">
                    <div class="px-4 pb-2 text-[13px] font-bold text-ink">Notificaciones</div>
                    <p v-if="alerts === null" class="px-4 py-2 text-[12px] text-muted">Cargando...</p>
                    <p v-else-if="!alerts.length" class="px-4 py-2 text-[12px] text-muted">No tienes alertas pendientes.</p>
                    <Link v-for="a in alerts" :key="a.title" :href="a.href" class="flex gap-3 px-4 py-[8px] hover:bg-[#f5f9fc]" @click="showBell = false">
                        <Icon :icon="a.icon" class="mt-[2px]" :class="LEVEL[a.level]" />
                        <span><span class="block text-[13px] font-semibold text-ink">{{ a.title }}</span><span class="text-[12px] text-muted">{{ a.text }}</span></span>
                    </Link>
                </div>
            </div>

            <div class="relative">
                <button type="button" class="flex h-8 w-8 items-center justify-center text-[18px] text-primary" title="Configuración" @click="showConfig = !showConfig">
                    <Icon icon="gear" />
                </button>
                <ConfigMenu v-if="showConfig" :menu="configMenu" @close="showConfig = false" />
            </div>

            <div ref="userMenu" class="relative">
                <button
                    type="button"
                    class="flex h-[32px] w-[32px] items-center justify-center rounded-full bg-primary text-[11px] font-extrabold text-white"
                    @click="showUser = !showUser"
                >
                    {{ user?.initials }}
                </button>
                <div v-if="showUser" class="s-card fixed inset-x-2 top-[56px] z-50 p-4 shadow-lg md:absolute md:inset-x-auto md:right-0 md:top-[42px] md:w-[260px]">
                    <p class="font-bold text-ink">{{ user?.name }}</p>
                    <p class="text-muted">{{ user?.email }}</p>
                    <p v-if="company" class="mt-2 text-[12px] text-muted">{{ company.name }} · NIT {{ company.nit }}</p>
                    <hr class="my-3 border-line" />
                    <button type="button" class="flex items-center gap-2 text-primary" @click="logout">
                        <Icon icon="right-from-bracket" /> Cerrar sesión
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>
