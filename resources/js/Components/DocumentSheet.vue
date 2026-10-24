<script setup>
import { onMounted, ref } from 'vue';
import QRCode from 'qrcode';
import NoteSheet from './NoteSheet.vue';
import QuoteSheet from './QuoteSheet.vue';

/* Representacion grafica de un documento de venta (impresion individual, por lotes y PDF del correo). */
const props = defineProps({ doc: Object, company: Object, resolution: Object });
const d = props.doc;
const c = props.company ?? {};
const qr = ref('');
const money = (v) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const qty = (v) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 });
const dots = (v) => String(v ?? '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
const nit = (id, dv) => (id ? dots(id) + (dv !== null && dv !== undefined && dv !== '' ? `-${dv}` : '') : '');
const TITLES = { FV: 'Factura electrónica de venta', NC: 'Nota crédito electrónica', ND: 'Nota débito electrónica', CT: 'Cotización', RM: 'Remisión' };
const title = (d.dian ? TITLES[d.type] : TITLES[d.type]?.replace(' electrónica', '')) ?? d.type_name;
const paid = d.type === 'FV' && d.status !== 'annulled' && Number(d.totals.balance) === 0;
const r = props.resolution;
const ymd = (v) => String(v ?? '').slice(0, 10).replaceAll('-', '');
const months = (() => {
    if (!r?.valid_from || !r?.valid_to) return null;
    const [a, b] = [new Date(r.valid_from), new Date(r.valid_to)];
    return (b.getFullYear() - a.getFullYear()) * 12 + b.getMonth() - a.getMonth();
})();
const p = d.principal;
const head = p ? {
    logo: p.logo_url || c.logo, name: p.nombre, nit: p.identificacion, dv: p.dv, address: p.direccion, city: p.ciudad,
    email: p.correo, phone: p.telefono, phone2: p.whatsapp, site: p.sitio,
} : { logo: c.logo, name: c.trade_name || c.name, nit: c.nit_number, dv: c.dv, address: c.address, city: c.city, email: c.email, phone: c.phone, phone2: c.phone2, site: null };
const roleLabel = (i) => (i.role === 'mandante' ? 'Mandante' : (i.role === 'mandatario' ? 'Mandatario' : ''));
const software = d.source === 'siigo'
    ? 'Fabricante Software y Proveedor tecnológico: Siigo SAS - NIT 830.048.145-8, Nombre Software: Siigo Nube. Firma electrónica: ver en el XML'
    : `Software propio: OYARCEGROUP INC · Fabricante del software: ${c.name ?? ''} - NIT ${nit(c.nit_number, c.dv)} · Firma electrónica: ver en el XML`;

onMounted(async () => {
    if (d.dian?.qr) qr.value = await QRCode.toDataURL(d.dian.qr, { margin: 0, width: 140 });
});
</script>

<template>
    <NoteSheet v-if="d.type === 'NC'" :doc="d" :company="company" :software="software" />
    <QuoteSheet v-else-if="d.type === 'CT' || d.type === 'RM'" :doc="d" :company="company" />
    <article v-else class="sheet">
        <div v-if="paid" class="ribbon"><span>Pagada</span></div>
        <p v-if="d.dian" class="side">{{ software }}</p>

        <header class="top">
            <img v-if="head.logo" :src="head.logo" alt="" class="logo" />
            <div class="company">
                <b>{{ head.name }}</b>
                <p>Nit {{ nit(head.nit, head.dv) }}</p>
                <p>{{ head.address }}</p>
                <p>{{ head.city }}<span v-if="head.phone"> - Tel. ({{ head.phone }})</span><span v-if="head.phone2 && head.phone2 !== head.phone"> {{ head.phone2 }}</span></p>
                <p>{{ head.email }}<span v-if="head.site"> · {{ head.site }}</span></p>
            </div>
            <img v-if="qr" :src="qr" alt="QR DIAN" class="qr" />
        </header>

        <p v-if="p" class="mandate">Mandatario: {{ c.trade_name || c.name }} · NIT {{ nit(c.nit_number, c.dv) }}</p>

        <section class="title">
            <p>{{ title }}</p>
            <p>No. {{ d.number_label ?? d.name }}</p>
        </section>
        <section v-if="d.status === 'annulled'" class="annul">DOCUMENTO ANULADO</section>

        <table class="info">
            <tr><td>Fecha</td><td>{{ d.date }}</td></tr>
            <tr v-if="d.due_date && d.due_date !== d.date"><td>Vencimiento</td><td>{{ d.due_date }}</td></tr>
            <tr v-if="d.seller"><td>Vendedor</td><td>{{ d.seller }}</td></tr>
            <tr><td>Cliente</td><td>{{ d.contact?.name }}</td></tr>
            <tr><td>Nit / C.C.</td><td>{{ nit(d.contact?.identification, d.contact?.dv) }}</td></tr>
            <tr><td>Dirección</td><td>{{ d.contact?.address }}</td></tr>
            <tr v-if="d.related"><td>Documento referencia</td><td>{{ d.related }}</td></tr>
        </table>

        <table class="items">
            <thead>
                <tr><th>Descripción</th><th class="w-um">Unidad de medida</th><th class="r w-n">Cant.</th><th class="r w-vu">Vr. Unitario</th><th class="r w-n">Valor Impto.Cargo</th><th class="r w-n">Vr. Total</th></tr>
            </thead>
            <tbody>
                <tr v-for="(i, k) in d.items" :key="k">
                    <td>{{ i.description }}<div v-if="roleLabel(i)" class="role">{{ roleLabel(i) }}</div></td><td>{{ i.unit }}</td><td class="r">{{ qty(i.quantity) }}</td>
                    <td class="r">{{ money(i.price) }}</td><td class="r">{{ money(i.tax_total) }}</td><td class="r">{{ money(i.total) }}</td>
                </tr>
            </tbody>
        </table>
        <table class="sums">
            <tr v-if="d.totals.discount"><td></td><td>Descuentos</td><td class="r">{{ money(d.totals.discount) }}</td></tr>
            <tr v-for="t in d.tax_lines" :key="t.name"><td></td><td>{{ t.name }}</td><td class="r">{{ money(t.value) }}</td></tr>
            <tr v-for="t in d.retentions" :key="'r' + t.name"><td></td><td>{{ t.name }}</td><td class="r">-{{ money(t.value) }}</td></tr>
            <tr class="b"><td>Total</td><td></td><td class="r">{{ money(d.totals.total) }}</td></tr>
        </table>

        <section v-if="d.tax_summary?.length" class="block">
            <p class="h">Resumen Impuestos</p>
            <table class="summary">
                <tr><td>Tarifa</td><td>Vr. Base</td><td>Valor</td></tr>
                <tr v-for="t in d.tax_summary" :key="t.rate"><td class="r">{{ money(t.rate) }}%</td><td class="r">{{ money(t.base) }}</td><td class="r">{{ money(t.value) }}</td></tr>
            </table>
        </section>

        <section v-if="p?.atencion || p?.cuentas?.length" class="block">
            <p v-if="p.atencion"><b>Atención:</b> {{ p.atencion }}</p>
            <p v-for="(cta, k) in p.cuentas" :key="k">{{ [cta.banco, cta.tipo, cta.numero].filter(Boolean).join(' · ') }}</p>
        </section>

        <section v-if="d.payments?.length" class="block">
            <p class="h">Forma de Pago</p>
            <p v-for="(p, k) in d.payments" :key="k" class="pay"><span>{{ p.form }} - {{ p.name }}<template v-if="p.due_date"> - {{ String(p.due_date).slice(0, 10) }}</template></span><span>{{ money(p.value) }}</span></p>
        </section>

        <section class="block legal">
            <p v-if="d.observations" class="obs">{{ d.observations }}</p>
            <p>
                <template v-if="d.type === 'FV'">A esta factura de venta aplican las normas relativas a la letra de cambio (artículo 5 Ley 1231 de 2008). Con esta el Comprador declara haber recibido real y materialmente las mercancías o prestación de servicios descritos en este título - Valor. </template>
                <b v-if="r?.resolution_number && d.dian">Número Autorización Electrónica {{ r.resolution_number }} aprobado en {{ ymd(r.resolution_date) }} prefijo {{ r.prefix }} desde el número {{ r.range_from }} al {{ r.range_to }}<template v-if="months"> Vigencia: {{ months }} Meses</template></b>
            </p>
            <p v-if="d.dian">{{ c.iva_responsible ? 'Responsable de IVA' : 'No responsable de IVA' }}<template v-if="c.ciiu"> - Actividad Económica {{ c.ciiu }}&nbsp; {{ c.ciiu_name }}</template> Tarifa</p>
            <p v-if="d.dian?.cufe" class="cufe"><b>{{ d.type === 'FV' ? 'CUFE' : 'CUDE' }}:</b> {{ d.dian.cufe }}</p>
        </section>
    </article>
</template>

<style scoped>
.sheet { position: relative; width: 980px; margin: 0 auto 30px; background: #fff; padding: 18px 40px 30px 4px; font: 12px/1.45 Arial, Helvetica, sans-serif; color: #222; overflow: hidden; }
.ribbon { position: absolute; left: 0; top: 0; width: 80px; height: 80px; overflow: hidden; }
.ribbon span { position: absolute; left: -32px; top: 22px; width: 130px; transform: rotate(-45deg); background: #ec407a; color: #fff; text-align: center; font-size: 11px; padding: 3px 0; box-shadow: 0 1px 2px #0003; }
.side { position: absolute; right: 2px; top: 170px; writing-mode: vertical-rl; transform: rotate(180deg); font-size: 8px; color: #333; white-space: nowrap; }
.top { display: flex; align-items: flex-start; justify-content: center; position: relative; min-height: 110px; }
.logo { position: absolute; left: 70px; top: 10px; max-height: 80px; max-width: 160px; object-fit: contain; }
.company { text-align: center; padding-top: 22px; }
.company p { margin: 2px 0; }
.qr { position: absolute; right: 18px; top: 10px; width: 136px; height: 136px; }
.title { text-align: center; font-weight: bold; margin: 30px 0 18px; }
.title p { margin: 2px 0; }
.annul { text-align: center; color: #c62828; font-weight: bold; font-size: 16px; border: 2px solid #c62828; padding: 4px; margin-bottom: 10px; }
.info { width: 100%; border-top: 1px solid #999; border-collapse: collapse; margin-top: 4px; }
.info tr:first-child td { padding-top: 14px; }
.info td { padding: 1px 2px; }
.info td:first-child { width: 270px; }
.info tr:last-child td { padding-bottom: 14px; }
.items { width: 100%; border-collapse: collapse; border-top: 1px solid #999; }
.items th { font-weight: normal; text-align: left; padding: 18px 2px 6px; vertical-align: bottom; }
.items td { padding: 2px; vertical-align: top; }

.w-um { width: 70px; } .w-n { width: 90px; } .w-vu { width: 110px; }
.items th.r { text-align: right; }
.r { text-align: right; }
.sums { width: 100%; border-collapse: collapse; margin: 18px 0 30px; }
.sums td:first-child { width: 470px; }
.sums td { padding: 1px 2px; }
.sums .b td { font-weight: bold; }
.block { border-top: 1px solid #999; padding: 14px 0; }
.h { text-align: center; font-weight: bold; margin-bottom: 4px; }
.summary { width: 100%; border-collapse: collapse; }
.summary tr:first-child td { text-align: center; }
.summary td { width: 33%; padding: 1px 2px; }
.pay { display: flex; justify-content: space-between; margin: 4px 0; }
.legal { text-align: center; padding-bottom: 40px; border-bottom: 1px solid #999; }
.legal p { margin: 0 0 2px; }
.obs { text-align: left; margin-bottom: 10px !important; white-space: pre-line; }
.cufe { word-break: break-all; }
.mandate { text-align: center; margin: 8px 0 0; font-weight: bold; }
.role { font-size: 10px; color: #555; }
@media print { .sheet { margin: 0; width: auto; page-break-after: always; } }
/* Tirilla de impresora POS (80 mm): sin logo ni textos laterales, encabezado a la izquierda y QR a la derecha. */
@media (max-width: 480px) {
    .sheet { width: auto; margin: 0; padding: 2px 6px 10px 8px; font: 11px/1.3 Arial, Helvetica, sans-serif; color: #000; }
    .ribbon, .side, .logo { display: none; }
    .top { justify-content: space-between; min-height: 0; gap: 4px; }
    .company { flex: 1; padding-top: 4px; }
    .company b { font-size: 12px; }
    .company p { margin: 0; }
    .qr { position: static; width: 96px; height: 96px; flex: none; }
    .title { margin: 12px 0 6px; }
    .info td:first-child { width: 36%; }
    .info tr:first-child td { padding-top: 4px; }
    .info tr:last-child td { padding-bottom: 4px; }
    .items th { padding: 4px 1px 2px; font-size: 10px; line-height: 1.15; }
    .items td { padding: 1px; }
    .w-um { width: 38px; } .w-n { width: 48px; } .w-vu { width: 52px; }
    .sums { margin: 6px 0 8px; }
    .sums td:first-child { width: 30%; }
    .block { padding: 6px 0; }
    .legal { padding-bottom: 10px; }
}
</style>
