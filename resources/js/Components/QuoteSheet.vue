<script setup>
/* Formato de impresion de cotizaciones y remisiones de Siigo: logo y empresa a la izquierda, cuadro Para/Nit/Fecha a la derecha. */
const props = defineProps({ doc: Object, company: Object });
const d = props.doc;
const c = props.company ?? {};
const money = (v) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const qty = (v) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
const dots = (v) => String(v ?? '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
const nit = (id, dv) => (id ? dots(id) + (dv !== null && dv !== undefined && dv !== '' ? `-${dv}` : '') : '');
const title = { CT: 'Cotización', RM: 'Remisión' }[d.type] ?? d.type_name;
const sum = (rows) => (rows ?? []).reduce((a, t) => a + Number(t.value || 0), 0);
const subtotal = Math.round((Number(d.totals.total) - sum(d.tax_lines) + sum(d.retentions)) * 100) / 100;
const maker = d.source === 'siigo' ? 'Elaborado por Siigo S.A.S Nit: 830.048.145-8' : `Elaborado por ${c.name ?? ''} con OYARCEGROUP INC`;
</script>

<template>
    <article class="sheet">
        <p class="side">{{ maker }}</p>
        <header class="top">
            <div class="company">
                <img v-if="c.logo" :src="c.logo" alt="" class="logo" />
                <b>{{ c.trade_name || c.name }}</b>
                <p>NIT {{ nit(c.nit_number, c.dv) }}</p>
                <p>{{ c.address }}</p>
                <p v-if="c.phone">Tel: ({{ c.phone }}) {{ c.phone2 || c.phone }}</p>
                <p>{{ c.city }} - Colombia</p>
            </div>
            <div class="head">
                <p class="t">{{ title }}</p>
                <p>No. {{ d.name }}</p>
                <table class="box">
                    <tr><th>Para</th><td>{{ d.contact?.name }}</td></tr>
                    <tr><th>Nit</th><td>{{ nit(d.contact?.identification, d.contact?.dv) }}</td></tr>
                    <tr><th>Fecha</th><td>{{ d.date }}</td></tr>
                </table>
            </div>
        </header>
        <p v-if="d.status === 'annulled'" class="annul">DOCUMENTO ANULADO</p>

        <table class="items">
            <thead><tr><th class="w-i">Ítem</th><th>Descripción</th><th class="w-q">Cantidad</th><th class="w-t">Vr. Total</th></tr></thead>
            <tbody>
                <tr v-for="(i, k) in d.items" :key="k">
                    <td class="c">{{ k + 1 }}</td><td>{{ i.description }}</td><td class="r">{{ qty(i.quantity) }}</td><td class="r">{{ money(i.total) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="sums">
            <tr><th>Total Bruto</th><td>{{ money(subtotal + Number(d.totals.discount || 0)) }}</td></tr>
            <tr v-if="d.totals.discount"><th>Descuentos</th><td>{{ money(d.totals.discount) }}</td></tr>
            <tr><th>Subtotal</th><td>{{ money(subtotal) }}</td></tr>
            <tr v-for="t in d.tax_lines" :key="t.name"><th>{{ t.name }}</th><td>{{ money(t.value) }}</td></tr>
            <tr v-for="t in d.retentions" :key="'r' + t.name"><th>{{ t.name }}</th><td>-{{ money(t.value) }}</td></tr>
            <tr class="total"><th>Total a Pagar</th><td>{{ money(d.totals.total) }}</td></tr>
        </table>

        <p v-if="d.observations" class="obs">{{ d.observations }}</p>
        <footer v-if="d.seller_info" class="sign">
            <b>{{ d.seller_info.name }}</b>
            <p>{{ d.seller_info.email }}</p>
            <p>{{ d.seller_info.phone }}</p>
        </footer>
    </article>
</template>

<style scoped>
.sheet { position: relative; width: 980px; margin: 0 auto 30px; background: #fff; border: 1px solid #ccc; border-radius: 3px; padding: 22px 16px 14px 8px; font: 11px/1.45 Arial, Helvetica, sans-serif; color: #222; }
.side { position: absolute; right: 0; top: 150px; writing-mode: vertical-rl; transform: rotate(180deg); font-size: 9px; font-weight: bold; white-space: nowrap; }
.top { display: flex; justify-content: space-between; min-height: 230px; }
.company { width: 330px; text-align: center; font-size: 10px; }
.company b { display: block; margin-top: 4px; font-size: 11px; }
.company p { margin: 1px 0; }
.logo { display: block; margin: 0 auto; max-height: 90px; max-width: 160px; object-fit: contain; }
.head { width: 300px; text-align: right; }
.head .t { margin-top: 10px; font-size: 19px; font-weight: bold; }
.head p { margin: 0; font-size: 13px; }
.box { width: 100%; margin-top: 18px; border-collapse: collapse; border: 1px solid #ddd; font-size: 10px; }
.box th { width: 86px; background: #e8e8e8; text-align: center; font-weight: bold; padding: 3px 6px; }
.box td { text-align: right; padding: 3px 6px; }
.box tr + tr { border-top: 1px solid #ddd; }
.annul { text-align: center; color: #c62828; font-weight: bold; font-size: 16px; border: 2px solid #c62828; padding: 4px; margin: 0 0 10px; }
.items { width: 100%; border-collapse: collapse; border: 1px solid #ccc; font-size: 10px; }
.items th { background: #e8e8e8; font-weight: bold; text-align: center; padding: 5px 4px; border: 1px solid #ccc; }
.items td { padding: 4px; border-left: 1px solid #ccc; border-right: 1px solid #ccc; }
.items tbody tr:nth-child(even) { background: #f3f3f3; }
.w-i { width: 120px; } .w-q { width: 210px; } .w-t { width: 260px; }
.c { text-align: center; } .r { text-align: right; }
.sums { margin: 14px 0 0 auto; width: 300px; border-collapse: collapse; font-size: 10px; }
.sums th { text-align: right; font-weight: bold; padding: 5px 16px 5px 4px; width: 160px; }
.sums td { text-align: right; padding: 5px 4px; }
.sums .total { background: #e8e8e8; }
.obs { margin-top: 20px; white-space: pre-line; font-size: 10px; }
.sign { margin-top: 90px; font-size: 10px; }
.sign p { margin: 1px 0; }
@media print { .sheet { margin: 0; width: auto; border: 0; page-break-after: always; } }
/* Tirilla de impresora POS (80 mm): sin logo ni texto lateral, encabezado arriba del número. */
@media (max-width: 480px) {
    .sheet { width: auto; margin: 0; padding: 2px 6px 10px 8px; border: 0; font: 11px/1.3 Arial, Helvetica, sans-serif; color: #000; }
    .side, .logo { display: none; }
    .top { flex-direction: column; min-height: 0; }
    .company, .head { width: 100%; text-align: center; }
    .head .t { margin-top: 6px; font-size: 14px; }
    .box { margin-top: 6px; }
    .items, .box, .sums, .obs, .sign { font-size: 11px; }
    .items th, .items td { padding: 2px 3px; }
    .w-i { width: 28px; } .w-q { width: 52px; } .w-t { width: 76px; }
    .sums { width: 100%; margin-top: 6px; }
    .sums th { width: auto; padding-right: 8px; }
    .sign { margin-top: 30px; }
}
</style>
