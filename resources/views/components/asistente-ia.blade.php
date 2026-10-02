<!-- Widget de Chat Flotante: Asistente Técnico Acuícola - El SAS Piscícola -->
<div x-data="asistenteIaWidget()"
     x-init="initWidget()"
     class="relative z-50">

    <!-- Botón Flotante (Floating Action Button - FAB) -->
    <button x-show="!isOpen"
            @click="toggleChat()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-75"
            x-transition:enter-end="opacity-100 scale-100"
            class="fixed bottom-20 md:bottom-6 right-4 md:right-6 z-50 flex items-center gap-3 rounded-full bg-slate-950 px-4 py-3 text-white shadow-xl hover:bg-slate-900 active:scale-95 transition border border-cyan-500/30 group"
            title="Abrir Asistente IA Piscícola"
            aria-label="Asistente IA Piscícola">
        <div class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-800 border border-slate-700 text-cyan-400 group-hover:text-cyan-300">
            <i class="fa-solid fa-microchip text-base"></i>
            <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
            </span>
        </div>
        <div class="text-left hidden sm:block">
            <div class="text-xs font-bold leading-none tracking-tight text-white">Asistente IA</div>
            <div class="text-[10px] text-cyan-400 font-medium mt-0.5">Consulta Técnica</div>
        </div>
    </button>

    <!-- Ventana del Chat Flotante (Drawer / Modal Flotante) -->
    <div x-show="isOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-8 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-8 scale-95"
         class="fixed bottom-3 right-3 sm:bottom-6 sm:right-6 z-50 flex flex-col w-[calc(100vw-1.5rem)] sm:w-[440px] h-[85vh] sm:h-[620px] max-h-[720px] rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden font-sans">

        <!-- Encabezado del Chat -->
        <div class="flex items-center justify-between px-4 py-3.5 bg-slate-950 text-white border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 border border-slate-800 text-cyan-400">
                    <i class="fa-solid fa-microchip text-base"></i>
                    <span class="absolute -bottom-0.5 -right-0.5 flex h-2.5 w-2.5">
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-xs sm:text-sm font-bold text-white tracking-tight">Asistente Técnico Acuícola - El SAS Piscícola</h3>
                        <span class="px-1.5 py-0.5 rounded bg-slate-800 text-[10px] font-medium text-cyan-300 border border-slate-700">Asistente Gemini</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-[11px] text-slate-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-emerald-400 font-medium">En Línea</span>
                        <span class="text-slate-600">•</span>
                        <span class="text-slate-400">Asistente IA Piscícola</span>
                    </div>
                </div>
            </div>

            <!-- Acciones del Encabezado -->
            <div class="flex items-center gap-1.5">
                <button @click="clearHistory()"
                        type="button"
                        class="px-2.5 py-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition text-[11px] font-medium flex items-center gap-1 border border-slate-800"
                        title="Limpiar conversación">
                    <i class="fa-solid fa-trash-can text-xs text-slate-500"></i>
                    <span class="hidden sm:inline">Limpiar</span>
                </button>
                <button @click="toggleChat()"
                        type="button"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-medium flex items-center gap-1 transition border border-slate-700"
                        title="Cerrar ventana">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        </div>

        <!-- Área de Conversación (Scrollable) -->
        <div x-ref="chatMessagesContainer"
             class="flex-1 overflow-y-auto p-4 space-y-4 bg-slate-50/70">

            <!-- Mensaje de Bienvenida Inicial -->
            <div class="flex items-start gap-2.5">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-cyan-400 text-xs shadow-xs border border-slate-800">
                    <i class="fa-solid fa-microchip"></i>
                </div>
                <div class="max-w-[88%] rounded-2xl rounded-tl-xs bg-white p-3.5 shadow-xs border border-slate-200 text-xs text-slate-700 space-y-2">
                    <p class="font-medium text-slate-900 leading-relaxed">
                        Hola. Soy tu asistente técnico acuícola. Puedo ayudarte con el estado de tus lagos, raciones de alimento, calidad de agua y alertas sanitarias.
                    </p>
                    <div class="text-[10px] text-slate-400 text-right font-mono" x-text="currentTime"></div>
                </div>
            </div>

            <!-- Chips de Consulta Rápida Clicables -->
            <div class="pt-1 pb-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 px-1 flex items-center gap-1.5">
                    <i class="fa-solid fa-bolt text-cyan-600 text-xs"></i>
                    <span>Consultas Rápidas</span>
                </div>
                <div class="flex flex-col gap-1.5">
                    <button @click="sendPredefined('¿Cómo está la biomasa y los lagos hoy?')"
                            type="button"
                            class="w-full text-left px-3 py-2 rounded-xl bg-white border border-slate-200 hover:border-cyan-500 hover:bg-cyan-50 text-slate-700 hover:text-cyan-900 transition text-xs font-medium shadow-2xs flex items-center justify-between group">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-chart-pie text-cyan-600 group-hover:scale-110 transition"></i>
                            <span>¿Cómo está la biomasa y los lagos hoy?</span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-400 group-hover:text-cyan-600 transition"></i>
                    </button>
                    <button @click="sendPredefined('¿Qué hacer si el oxígeno baja de 3.5 mg/L?')"
                            type="button"
                            class="w-full text-left px-3 py-2 rounded-xl bg-white border border-slate-200 hover:border-red-500 hover:bg-red-50 text-slate-700 hover:text-red-900 transition text-xs font-medium shadow-2xs flex items-center justify-between group">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-water text-red-600 group-hover:scale-110 transition"></i>
                            <span>¿Qué hacer si el oxígeno baja de 3.5 mg/L?</span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-400 group-hover:text-red-600 transition"></i>
                    </button>
                    <button @click="sendPredefined('Calcular ración recomendada según peso')"
                            type="button"
                            class="w-full text-left px-3 py-2 rounded-xl bg-white border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50 text-slate-700 hover:text-emerald-900 transition text-xs font-medium shadow-2xs flex items-center justify-between group">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-calculator text-emerald-600 group-hover:scale-110 transition"></i>
                            <span>Calcular ración recomendada según peso</span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-400 group-hover:text-emerald-600 transition"></i>
                    </button>
                    <button @click="sendPredefined('Inventario disponible en bodega')"
                            type="button"
                            class="w-full text-left px-3 py-2 rounded-xl bg-white border border-slate-200 hover:border-amber-500 hover:bg-amber-50 text-slate-700 hover:text-amber-900 transition text-xs font-medium shadow-2xs flex items-center justify-between group">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-warehouse text-amber-600 group-hover:scale-110 transition"></i>
                            <span>Inventario disponible en bodega</span>
                        </span>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-400 group-hover:text-amber-600 transition"></i>
                    </button>
                </div>
            </div>

            <!-- Historial de Mensajes -->
            <template x-for="(msg, index) in messages" :key="index">
                <div>
                    <!-- Mensaje del Usuario -->
                    <div x-show="msg.role === 'user'" class="flex justify-end items-end gap-2 my-2.5">
                        <div class="max-w-[85%] rounded-2xl rounded-br-xs bg-slate-900 p-3.5 text-white shadow-sm text-xs leading-relaxed break-words border border-slate-800">
                            <p x-text="msg.text" class="whitespace-pre-wrap"></p>
                            <div class="text-[9px] text-slate-400 text-right mt-1 font-mono" x-text="msg.time"></div>
                        </div>
                    </div>

                    <!-- Mensaje del Asistente Técnico -->
                    <div x-show="msg.role === 'model' || msg.role === 'assistant'" class="flex items-start gap-2.5 my-2.5">
                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-cyan-400 text-xs shadow-xs border border-slate-800">
                            <i class="fa-solid fa-microchip"></i>
                        </div>
                        <div class="max-w-[88%] rounded-2xl rounded-tl-xs bg-white p-3.5 shadow-sm border border-slate-200 text-xs text-slate-800 space-y-2 leading-relaxed break-words">
                            <!-- Contenido con renderizado de listas, negritas y saltos -->
                            <div class="font-sans text-slate-700 space-y-1.5" x-html="renderFormattedMessage(msg.text)"></div>

                            <div class="flex items-center justify-between pt-1.5 border-t border-slate-100 text-[10px] text-slate-400">
                                <span class="flex items-center gap-1 font-medium text-cyan-700">
                                    <i class="fa-solid fa-shield-check text-xs"></i>
                                    <span x-text="msg.model || 'Asistente Piscícola'"></span>
                                </span>
                                <span class="font-mono text-slate-400" x-text="msg.time"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Animación de Carga: Pensando... -->
            <div x-show="isLoading"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="flex items-start gap-2.5 pt-1">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-cyan-400 text-xs shadow-xs border border-slate-800">
                    <i class="fa-solid fa-microchip animate-pulse"></i>
                </div>
                <div class="rounded-2xl rounded-tl-xs bg-white px-4 py-3 shadow-xs border border-slate-200 text-xs text-slate-600 flex items-center gap-2">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-cyan-600 animate-bounce"></span>
                        <span class="w-2 h-2 rounded-full bg-teal-500 animate-bounce [animation-delay:0.15s]"></span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-bounce [animation-delay:0.3s]"></span>
                    </div>
                    <span class="text-xs font-medium text-slate-600">Pensando...</span>
                </div>
            </div>

        </div>

        <!-- Footer: Input de Texto y Botón Enviar -->
        <div class="p-3 bg-white border-t border-slate-200">
            <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input type="text"
                           x-ref="messageInput"
                           x-model="inputText"
                           :disabled="isLoading"
                           placeholder="Pregunta sobre lagos, oxígeno, alimento..."
                           class="w-full rounded-xl bg-slate-50 border border-slate-200 px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 disabled:opacity-50 transition" />
                </div>

                <button type="submit"
                        :disabled="isLoading || !inputText.trim()"
                        title="Enviar consulta"
                        class="flex h-10 px-4 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-cyan-600 hover:bg-cyan-500 active:scale-95 text-white shadow-sm disabled:opacity-40 disabled:hover:scale-100 transition text-xs font-bold">
                    <span class="hidden sm:inline">Enviar</span>
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                </button>
            </form>
            <div class="flex items-center justify-between mt-2 px-1 text-[10px] text-slate-400">
                <span>El SAS Piscícola • Tolima</span>
                <span class="font-medium text-cyan-700">Google Gemini API & Motor Local</span>
            </div>
        </div>

    </div>
</div>

<script>
    function asistenteIaWidget() {
        return {
            isOpen: false,
            isLoading: false,
            inputText: '',
            currentTime: '12:00 PM',
            messages: [],

            initWidget() {
                this.updateCurrentTime();
                setInterval(() => this.updateCurrentTime(), 15000);
            },

            toggleChat() {
                this.isOpen = !this.isOpen;
                if (this.isOpen) {
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        if (this.$refs.messageInput) {
                            this.$refs.messageInput.focus();
                        }
                    });
                }
            },

            updateCurrentTime() {
                const now = new Date();
                let hours = now.getHours();
                const period = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12 || 12;
                const m = String(now.getMinutes()).padStart(2, '0');
                this.currentTime = `${hours}:${m} ${period}`;
            },

            clearHistory() {
                this.messages = [];
            },

            sendPredefined(text) {
                this.inputText = text;
                this.sendMessage();
            },

            async sendMessage() {
                const text = this.inputText.trim();
                if (!text || this.isLoading) return;

                this.updateCurrentTime();

                // Agregar mensaje de usuario
                this.messages.push({
                    role: 'user',
                    text: text,
                    time: this.currentTime,
                });

                this.inputText = '';
                this.isLoading = true;
                this.$nextTick(() => this.scrollToBottom());

                // Historial reciente para contexto multi-turn (últimos 6 mensajes)
                const historyPayload = this.messages.slice(-6).map(m => ({
                    role: m.role,
                    text: m.text,
                }));

                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    const response = await fetch('/api/asistente-ia/chat', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                        },
                        body: JSON.stringify({
                            message: text,
                            history: historyPayload,
                        }),
                    });

                    const data = await response.json();

                    if (response.ok && data.status === 'success') {
                        this.messages.push({
                            role: 'model',
                            text: data.reply,
                            model: data.model,
                            time: data.timestamp || this.currentTime,
                        });
                    } else {
                        this.messages.push({
                            role: 'model',
                            text: 'No fue posible completar la consulta técnica en este momento. ' + (data.message || 'Por favor intenta de nuevo.'),
                            model: 'Alerta del Sistema',
                            time: this.currentTime,
                        });
                    }
                } catch (error) {
                    console.error('Error comunicando con Asistente IA:', error);
                    this.messages.push({
                        role: 'model',
                        text: 'Falla momentánea de comunicación con el servidor. Verifica la conectividad de la granja e intenta de nuevo.',
                        model: 'Desconectado',
                        time: this.currentTime,
                    });
                } finally {
                    this.isLoading = false;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        if (this.$refs.messageInput) {
                            this.$refs.messageInput.focus();
                        }
                    });
                }
            },

            scrollToBottom() {
                const container = this.$refs.chatMessagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            },

            renderFormattedMessage(text) {
                if (!text) return '';
                // Escapar HTML no seguro
                let escaped = text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                // Negritas **texto**
                escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                // Cursivas *texto*
                escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
                // Viñetas de lista
                escaped = escaped.replace(/^[\*\-]\s+(.*)$/gm, '<li class="ml-3 my-0.5 list-disc">$1</li>');

                return escaped;
            }
        };
    }

    // Alias para retrocompatibilidad total con pruebas existentes
    function geminiChatWidget() {
        return asistenteIaWidget();
    }
</script>
