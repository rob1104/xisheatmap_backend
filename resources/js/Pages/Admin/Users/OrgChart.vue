<template>
    <AdminLayout>
        <template #header>
            Organigrama de la Red
        </template>

        <div class="max-w-7xl mx-auto animate-fade-in-up">
            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h3 class="text-lg font-bold text-slate-100">Estructura Jerárquica</h3>
                    <p class="mt-1 text-sm text-slate-400">
                        Visualiza la red de administradores, coordinadores, gestores y comités.
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 flex gap-3">
                    <button @click="exportToPNG" :disabled="isExporting" class="inline-flex items-center justify-center px-4 py-2.5 border border-slate-700 text-sm font-bold rounded-xl shadow-sm text-slate-300 bg-slate-800 hover:bg-slate-700 focus:outline-none transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 mr-2 -ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        {{ isExporting ? 'Generando...' : 'Exportar a PNG' }}
                    </button>
                    <button @click="exportToPDF" :disabled="isExporting" class="inline-flex items-center justify-center px-4 py-2.5 border border-red-500/30 text-sm font-bold rounded-xl shadow-lg shadow-red-500/20 text-white bg-red-600/90 hover:bg-red-500 focus:outline-none transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5 mr-2 -ml-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        {{ isExporting ? 'Generando...' : 'Exportar a PDF' }}
                    </button>
                    <Link :href="route('usuarios.index')" class="inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none transition-all ml-2">
                        Volver a Usuarios
                    </Link>
                </div>
            </div>

            <!-- Contenedor que será exportado -->
            <div ref="chartContainer" class="bg-slate-950 shadow-2xl rounded-2xl border border-slate-800 p-12 overflow-x-auto min-h-[500px] relative custom-scrollbar">
                <!-- Fondo sutil de cuadrícula -->
                <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] opacity-50 rounded-2xl"></div>
                
                <div class="org-chart flex justify-center min-w-max relative z-10 pt-4 pb-8">
                    <OrgNode v-for="node in tree" :key="node.id" :node="node" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import OrgNode from './OrgNode.vue';
import html2canvas from 'html2canvas';
import { jsPDF } from 'jspdf';

const props = defineProps({
    users: Array
});

const isExporting = ref(false);
const chartContainer = ref(null);

// Construir el árbol a partir de la lista plana
const tree = computed(() => {
    const userMap = {};
    const roots = [];

    // Inicializar nodos
    props.users.forEach(user => {
        userMap[user.id] = { ...user, children: [] };
    });

    // Anidar hijos
    props.users.forEach(user => {
        if (user.parent_id && userMap[user.parent_id]) {
            userMap[user.parent_id].children.push(userMap[user.id]);
        } else {
            roots.push(userMap[user.id]);
        }
    });

    return roots;
});

const prepareCapture = () => {
    const target = chartContainer.value;
    // Guardar estilos originales
    const originalStyle = {
        overflow: target.style.overflow,
        width: target.style.width,
        maxWidth: target.style.maxWidth,
        position: target.style.position
    };
    
    // Forzar al contenedor a expandirse a su tamaño real (scrollWidth)
    target.style.overflow = 'visible';
    target.style.width = target.scrollWidth + 'px';
    target.style.maxWidth = 'none';
    
    return { target, originalStyle };
};

const restoreCapture = (target, originalStyle) => {
    target.style.overflow = originalStyle.overflow;
    target.style.width = originalStyle.width;
    target.style.maxWidth = originalStyle.maxWidth;
    target.style.position = originalStyle.position;
};

const exportToPNG = async () => {
    if (!chartContainer.value) return;
    isExporting.value = true;
    
    const { target, originalStyle } = prepareCapture();
    
    try {
        const canvas = await html2canvas(target, {
            backgroundColor: '#020617', // slate-950
            scale: 2,
            useCORS: true,
            logging: false,
            width: target.scrollWidth,
            height: target.scrollHeight
        });
        
        const link = document.createElement('a');
        link.download = 'organigrama.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    } catch (e) {
        console.error('Error al exportar PNG', e);
        alert('Hubo un error al exportar la imagen.');
    } finally {
        restoreCapture(target, originalStyle);
        isExporting.value = false;
    }
};

const exportToPDF = async () => {
    if (!chartContainer.value) return;
    isExporting.value = true;
    
    const { target, originalStyle } = prepareCapture();
    
    try {
        const canvas = await html2canvas(target, {
            backgroundColor: '#020617',
            scale: 2,
            useCORS: true,
            logging: false,
            width: target.scrollWidth,
            height: target.scrollHeight
        });
        
        const imgData = canvas.toDataURL('image/png');
        
        const pdf = new jsPDF({
            orientation: 'landscape',
            unit: 'mm',
            format: 'a4'
        });
        
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        
        // Convertir dimensiones de la imagen al PDF manteniendo la proporción
        const ratio = canvas.width / canvas.height;
        let targetWidth = pdfWidth;
        let targetHeight = pdfWidth / ratio;
        
        if (targetHeight > pdfHeight) {
            targetHeight = pdfHeight;
            targetWidth = pdfHeight * ratio;
        }
        
        // Centrar imagen en la página
        const x = (pdfWidth - targetWidth) / 2;
        const y = (pdfHeight - targetHeight) / 2;
        
        // Fondo oscuro para que combine con el tema del sistema
        pdf.setFillColor(2, 6, 23);
        pdf.rect(0, 0, pdfWidth, pdfHeight, 'F');

        pdf.addImage(imgData, 'PNG', x, y, targetWidth, targetHeight);
        pdf.save('organigrama.pdf');
    } catch (e) {
        console.error('Error al exportar PDF', e);
        alert('Hubo un error al exportar el PDF.');
    } finally {
        restoreCapture(target, originalStyle);
        isExporting.value = false;
    }
};
</script>

<style>
/* CSS styles are handled in OrgNode component or Tailwind */
</style>
