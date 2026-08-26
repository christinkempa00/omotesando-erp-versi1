<x-app-layout sidebar="it">
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Papan Kerja — {{ $board->name }}
            </h2>
            <a href="{{ route('it.labels.index') }}" class="text-sm text-gold-600 hover:text-gold-700 font-medium">
                Kelola Label
            </a>
        </div>
    </x-slot>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <div class="font-it py-6"
         x-data="kanbanBoard(
            @js($board->columns->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'order' => $c->order,
                'tasks' => $c->tasks->map(fn ($t) => [
                    'id' => $t->id,
                    'board_column_id' => $t->board_column_id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'priority' => $t->priority,
                    'due_date' => $t->due_date?->format('Y-m-d'),
                    'order' => $t->order,
                    'assignee' => $t->assignee ? ['id' => $t->assignee->id, 'name' => $t->assignee->name] : null,
                    'assignee_id' => $t->assignee_id,
                    'related_module' => $t->relatedModule ? ['id' => $t->relatedModule->id, 'name' => $t->relatedModule->name] : null,
                    'related_module_id' => $t->related_module_id,
                    'labels' => $t->labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color])->values(),
                    'checklist_items' => $t->checklistItems->map(fn ($i) => ['id' => $i->id, 'content' => $i->content, 'is_done' => $i->is_done, 'order' => $i->order])->values(),
                    'comments' => $t->comments->map(fn ($cm) => ['id' => $cm->id, 'content' => $cm->content, 'created_at' => $cm->created_at->toIso8601String(), 'user' => $cm->user ? ['id' => $cm->user->id, 'name' => $cm->user->name] : null])->values(),
                ])->values(),
            ])->values()),
            @js($users),
            @js($labels->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'color' => $l->color])),
            @js($systemModules),
            @js($priorityLabels)
         )"
         x-init="$nextTick(() => initSortable())"
    >
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex gap-4 overflow-x-auto pb-4 items-start">
                <template x-for="column in columns" :key="column.id">
                    <div class="flex-shrink-0 w-72 glass-panel p-3">
                        <div class="flex items-center justify-between mb-3 px-1">
                            <h3 class="font-semibold text-sm text-gray-700" x-text="column.name"></h3>
                            <span class="text-xs text-gold-700 bg-gold-100 rounded-full px-2 py-0.5 font-medium" x-text="column.tasks.length"></span>
                        </div>

                        <ul class="kanban-column-list space-y-2 min-h-[40px]" :data-column-id="column.id">
                            <template x-for="task in column.tasks" :key="task.id">
                                <li :data-task-id="task.id" @click="openTask(task)"
                                    class="glass-panel p-3 cursor-pointer hover:shadow-[0_4px_14px_-2px_rgba(0,0,0,0.12)] hover:-translate-y-px transition space-y-2">

                                    <template x-if="task.related_module">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400" x-text="task.related_module.name"></p>
                                    </template>

                                    <p class="text-sm font-medium text-gray-800" x-text="task.title"></p>

                                    <div class="flex flex-wrap gap-1" x-show="task.labels.length">
                                        <template x-for="label in task.labels" :key="label.id">
                                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium"
                                                  :class="labelColorClasses[label.color] ?? labelColorClasses.gray"
                                                  x-text="label.name"></span>
                                        </template>
                                    </div>

                                    <div class="flex items-center justify-between text-xs">
                                        <span class="px-2 py-0.5 rounded-full font-medium" :class="priorityClasses[task.priority] ?? priorityClasses.medium" x-text="priorityLabels[task.priority] ?? task.priority"></span>

                                        <span class="w-6 h-6 rounded-full bg-gradient-to-br from-gold-400 to-gold-600 text-white flex items-center justify-center text-[10px] font-semibold"
                                              x-show="task.assignee" :title="task.assignee?.name" x-text="initials(task.assignee?.name)"></span>
                                    </div>

                                    <div class="flex items-center justify-between text-[11px] text-gray-400">
                                        <span x-show="task.due_date"
                                              :class="isOverdue(task) ? 'text-red-600 font-semibold' : (isDueSoon(task) ? 'text-amber-600 font-medium' : '')"
                                              x-text="formatDate(task.due_date)"></span>
                                        <span x-show="task.checklist_items.length" class="inline-flex items-center gap-1">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4" /><circle cx="12" cy="12" r="9" /></svg>
                                            <span x-text="checklistProgress(task)"></span>
                                        </span>
                                    </div>

                                    <div x-show="task.checklist_items.length" class="h-1 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-gold-500 transition-all" :style="`width: ${checklistPercent(task)}%`"></div>
                                    </div>
                                </li>
                            </template>
                        </ul>

                        {{-- Ditaruh di luar <ul> (bukan jadi <li>) supaya tidak
                             ikut dihitung SortableJS sebagai item yang bisa
                             di-drag/drop — murni teks kosong, tidak menyentuh
                             kontrak DOM milik Sortable. --}}
                        <p x-show="!column.tasks.length" x-cloak class="text-center text-xs text-gray-400 italic py-3">
                            Belum ada task.
                        </p>

                        <div class="mt-2" x-data="{ adding: false, title: '' }">
                            <button type="button" x-show="!adding" @click="adding = true; $nextTick(() => $refs.newTaskInput?.focus())"
                                    class="w-full text-left text-xs text-gray-500 hover:text-gold-700 hover:bg-gold-500/10 rounded-md px-2 py-1.5 transition">
                                + Tambah Task
                            </button>
                            <form x-show="adding" x-cloak
                                  @submit.prevent="createTask(column.id, title); title = ''; adding = false"
                                  class="space-y-1">
                                <textarea x-ref="newTaskInput" x-model="title" rows="2" placeholder="Judul task..."
                                          @keydown.enter.prevent="$el.form.requestSubmit()"
                                          @keydown.escape="adding = false; title = ''"
                                          class="w-full text-xs rounded-md border-gray-300 focus:border-gold-500 focus:ring-gold-500"></textarea>
                                <div class="flex items-center gap-2">
                                    <button type="submit" class="px-2.5 py-1 text-xs font-medium rounded-md bg-gradient-to-r from-gold-500 to-gold-600 text-white hover:shadow-[0_2px_8px_-1px_rgba(200,155,44,0.5)] transition">Tambah</button>
                                    <button type="button" @click="adding = false; title = ''" class="text-xs text-gray-500 hover:text-gray-700">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Modal detail task --}}
        <div x-show="modalOpen" x-cloak
             class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-4 overflow-y-auto"
             style="display: none;">
            <div class="fixed inset-0 bg-gray-900/50" @click="closeTask()"></div>

            <div class="relative bg-white rounded-xl shadow-xl border border-gold-500/20 w-full max-w-2xl my-8" @click.stop x-show="activeTask" x-cloak>
                <template x-if="activeTask">
                    <div class="p-6 space-y-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 flex items-center gap-2 min-w-0">
                                <input type="text" x-model="activeTask.title" @change="saveField('title', activeTask.title)"
                                       class="flex-1 min-w-0 text-lg font-semibold text-gray-800 border-0 border-b border-transparent hover:border-gray-200 focus:border-gold-500 focus:ring-0 px-0">
                                <span class="shrink-0 px-2.5 py-1 rounded-full text-[11px] font-medium bg-gold-100 text-gold-700" x-text="columnName(activeTask)"></span>
                            </div>
                            <button type="button" @click="closeTask()" class="text-gray-400 hover:text-gray-600 shrink-0">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M6 6l12 12M18 6 6 18" />
                                </svg>
                            </button>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1">Deskripsi</label>
                            <textarea x-model="activeTask.description" @change="saveField('description', activeTask.description)"
                                      rows="3" placeholder="Deskripsi..."
                                      class="w-full text-sm rounded-md border-gray-200 focus:border-gold-500 focus:ring-gold-500"></textarea>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Prioritas</label>
                                <select x-model="activeTask.priority" @change="saveField('priority', activeTask.priority)"
                                        class="w-full text-xs rounded-md border-gray-300 focus:border-gold-500 focus:ring-gold-500">
                                    <template x-for="[value, label] in Object.entries(priorityLabels)" :key="value">
                                        <option :value="value" x-text="label"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Assignee</label>
                                <select x-model="activeTask.assignee_id" @change="saveField('assignee_id', activeTask.assignee_id || null)"
                                        class="w-full text-xs rounded-md border-gray-300 focus:border-gold-500 focus:ring-gold-500">
                                    <option value="">Belum ditugaskan</option>
                                    <template x-for="u in users" :key="u.id">
                                        <option :value="u.id" x-text="u.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Due Date</label>
                                <input type="date" x-model="activeTask.due_date" @change="saveField('due_date', activeTask.due_date || null)"
                                       class="w-full text-xs rounded-md border-gray-300 focus:border-gold-500 focus:ring-gold-500">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-gray-500 mb-1">Modul Terkait</label>
                                <select x-model="activeTask.related_module_id" @change="saveField('related_module_id', activeTask.related_module_id || null)"
                                        class="w-full text-xs rounded-md border-gray-300 focus:border-gold-500 focus:ring-gold-500">
                                    <option value="">- Tidak ada -</option>
                                    <template x-for="m in systemModules" :key="m.id">
                                        <option :value="m.id" x-text="m.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1.5">Label</label>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="label in labels" :key="label.id">
                                    <button type="button" @click="toggleLabel(label.id)"
                                            class="px-2 py-0.5 rounded-full text-[11px] font-medium border transition"
                                            :class="hasLabel(label.id) ? (labelColorClasses[label.color] ?? labelColorClasses.gray) + ' border-transparent' : 'bg-white text-gray-400 border-gray-200 hover:border-gray-300'"
                                            x-text="label.name"></button>
                                </template>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-[11px] font-medium text-gray-500">
                                    Checklist <span x-show="activeTask.checklist_items.length" x-text="'(' + checklistProgress(activeTask) + ')'"></span>
                                </label>
                            </div>
                            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden mb-2" x-show="activeTask.checklist_items.length">
                                <div class="h-full bg-gold-500 transition-all" :style="`width: ${checklistPercent(activeTask)}%`"></div>
                            </div>
                            <ul class="space-y-1 mb-2">
                                <template x-for="item in activeTask.checklist_items" :key="item.id">
                                    <li class="flex items-center gap-2 text-sm group">
                                        <input type="checkbox" :checked="item.is_done" @change="toggleChecklistItem(item)"
                                               class="rounded border-gray-300 text-gold-600 focus:ring-gold-500">
                                        <span class="flex-1" :class="item.is_done ? 'line-through text-gray-400' : 'text-gray-700'" x-text="item.content"></span>
                                        <button type="button" @click="deleteChecklistItem(item)" class="text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M6 6l12 12M18 6 6 18" />
                                            </svg>
                                        </button>
                                    </li>
                                </template>
                            </ul>
                            <form @submit.prevent="addChecklistItem($refs.checklistInput.value); $refs.checklistInput.value = ''" class="flex gap-2">
                                <input type="text" x-ref="checklistInput" placeholder="Tambah item checklist..."
                                       class="flex-1 text-xs rounded-md border-gray-300 focus:border-gold-500 focus:ring-gold-500">
                                <button type="submit" class="px-2.5 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200">+</button>
                            </form>
                        </div>

                        <div>
                            <label class="block text-[11px] font-medium text-gray-500 mb-1.5">Komentar</label>
                            <p x-show="!activeTask.comments.length" class="text-xs text-gray-400 italic mb-2">Belum ada komentar.</p>
                            <ul class="space-y-2 mb-2 max-h-48 overflow-y-auto">
                                <template x-for="comment in activeTask.comments" :key="comment.id">
                                    <li class="bg-gray-50 rounded-md p-2 text-xs">
                                        <div class="flex items-center justify-between mb-0.5">
                                            <span class="font-medium text-gray-700" x-text="comment.user?.name ?? 'Sistem'"></span>
                                            <span class="text-gray-400" x-text="formatDateTime(comment.created_at)"></span>
                                        </div>
                                        <p class="text-gray-600" x-text="comment.content"></p>
                                    </li>
                                </template>
                            </ul>
                            <form @submit.prevent="addComment($refs.commentInput.value); $refs.commentInput.value = ''" class="flex gap-2">
                                <input type="text" x-ref="commentInput" placeholder="Tulis komentar..."
                                       class="flex-1 text-xs rounded-md border-gray-300 focus:border-gold-500 focus:ring-gold-500">
                                <button type="submit" class="px-2.5 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-600 hover:bg-gray-200">Kirim</button>
                            </form>
                        </div>

                        <div class="pt-3 border-t border-gray-100 flex justify-end">
                            <button type="button" @click="deleteTask(activeTask)"
                                    class="inline-flex items-center gap-1.5 text-xs text-red-500 hover:text-red-700 font-medium">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6h16Z" />
                                </svg>
                                Hapus Task
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        function kanbanBoard(initialColumns, users, labels, systemModules, priorityLabels) {
            return {
                columns: initialColumns,
                users: users,
                labels: labels,
                systemModules: systemModules,
                priorityLabels: priorityLabels,
                activeTask: null,
                modalOpen: false,
                isDragging: false,

                priorityClasses: {
                    low: 'bg-slate-100 text-slate-600',
                    medium: 'bg-blue-100 text-blue-700',
                    high: 'bg-orange-100 text-orange-700',
                    urgent: 'bg-red-100 text-red-700',
                },
                labelColorClasses: {
                    red: 'bg-red-100 text-red-700',
                    orange: 'bg-orange-100 text-orange-700',
                    yellow: 'bg-yellow-100 text-yellow-700',
                    green: 'bg-green-100 text-green-700',
                    blue: 'bg-blue-100 text-blue-700',
                    purple: 'bg-purple-100 text-purple-700',
                    pink: 'bg-pink-100 text-pink-700',
                    gray: 'bg-gray-100 text-gray-700',
                },

                csrfToken() {
                    return document.querySelector('meta[name=csrf-token]').content;
                },

                async api(url, method, body) {
                    const res = await fetch(url, {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                        body: body ? JSON.stringify(body) : undefined,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) {
                        throw new Error(data.message ?? 'Terjadi kesalahan.');
                    }
                    return data;
                },

                initSortable() {
                    document.querySelectorAll('.kanban-column-list').forEach((el) => {
                        Sortable.create(el, {
                            group: 'kanban',
                            animation: 150,
                            onStart: () => { this.isDragging = true; },
                            onEnd: (evt) => {
                                setTimeout(() => { this.isDragging = false; }, 50);

                                const fromColumn = this.columns.find((c) => String(c.id) === evt.from.dataset.columnId);
                                const toColumn = this.columns.find((c) => String(c.id) === evt.to.dataset.columnId);
                                if (!fromColumn || !toColumn) return;

                                const [moved] = fromColumn.tasks.splice(evt.oldIndex, 1);
                                toColumn.tasks.splice(evt.newIndex, 0, moved);
                                moved.board_column_id = toColumn.id;

                                this.persistColumnOrder(fromColumn);
                                if (toColumn.id !== fromColumn.id) {
                                    this.persistColumnOrder(toColumn);
                                }
                            },
                        });
                    });
                },

                persistColumnOrder(column) {
                    column.tasks.forEach((task, index) => {
                        this.api(`/it/tasks/${task.id}`, 'PATCH', {
                            board_column_id: column.id,
                            order: index,
                        }).catch(() => {});
                    });
                },

                async createTask(columnId, title) {
                    if (!title || !title.trim()) return;
                    const data = await this.api('/it/tasks', 'POST', {
                        board_column_id: columnId,
                        title: title.trim(),
                    });
                    const column = this.columns.find((c) => c.id === columnId);
                    column?.tasks.push(data.task);
                },

                openTask(task) {
                    if (this.isDragging) return;
                    this.activeTask = task;
                    this.modalOpen = true;
                },

                closeTask() {
                    this.modalOpen = false;
                    this.activeTask = null;
                },

                async saveField(field, value) {
                    if (!this.activeTask) return;
                    const data = await this.api(`/it/tasks/${this.activeTask.id}`, 'PATCH', { [field]: value });
                    Object.assign(this.activeTask, data.task);
                },

                hasLabel(labelId) {
                    return this.activeTask?.labels.some((l) => l.id === labelId) ?? false;
                },

                async toggleLabel(labelId) {
                    if (!this.activeTask) return;
                    const current = this.activeTask.labels.map((l) => l.id);
                    const next = current.includes(labelId)
                        ? current.filter((id) => id !== labelId)
                        : [...current, labelId];
                    const data = await this.api(`/it/tasks/${this.activeTask.id}`, 'PATCH', { label_ids: next });
                    Object.assign(this.activeTask, data.task);
                },

                async addChecklistItem(content) {
                    if (!this.activeTask || !content || !content.trim()) return;
                    const data = await this.api(`/it/tasks/${this.activeTask.id}/checklist`, 'POST', { content: content.trim() });
                    this.activeTask.checklist_items.push(data.item);
                },

                async toggleChecklistItem(item) {
                    if (!this.activeTask) return;
                    const data = await this.api(`/it/tasks/${this.activeTask.id}/checklist/${item.id}`, 'PATCH', { is_done: !item.is_done });
                    Object.assign(item, data.item);
                },

                async deleteChecklistItem(item) {
                    if (!this.activeTask) return;
                    await this.api(`/it/tasks/${this.activeTask.id}/checklist/${item.id}`, 'DELETE');
                    this.activeTask.checklist_items = this.activeTask.checklist_items.filter((i) => i.id !== item.id);
                },

                async addComment(content) {
                    if (!this.activeTask || !content || !content.trim()) return;
                    const data = await this.api(`/it/tasks/${this.activeTask.id}/comments`, 'POST', { content: content.trim() });
                    this.activeTask.comments.push(data.comment);
                },

                async deleteTask(task) {
                    if (!confirm(`Hapus task "${task.title}"?`)) return;
                    await this.api(`/it/tasks/${task.id}`, 'DELETE');
                    const column = this.columns.find((c) => c.id === task.board_column_id);
                    if (column) {
                        column.tasks = column.tasks.filter((t) => t.id !== task.id);
                    }
                    this.closeTask();
                },

                initials(name) {
                    if (!name) return '';
                    return name.split(' ').filter(Boolean).map((n) => n[0].toUpperCase()).slice(0, 2).join('');
                },

                checklistProgress(task) {
                    const done = task.checklist_items.filter((i) => i.is_done).length;
                    return `${done}/${task.checklist_items.length}`;
                },

                checklistPercent(task) {
                    if (!task.checklist_items.length) return 0;
                    const done = task.checklist_items.filter((i) => i.is_done).length;
                    return Math.round((done / task.checklist_items.length) * 100);
                },

                columnName(task) {
                    return this.columns.find((c) => c.id === task.board_column_id)?.name ?? '';
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    return new Date(dateStr + 'T00:00:00').toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
                },

                formatDateTime(isoStr) {
                    if (!isoStr) return '';
                    return new Date(isoStr).toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
                },

                isOverdue(task) {
                    if (!task.due_date) return false;
                    return new Date(task.due_date + 'T23:59:59') < new Date();
                },

                isDueSoon(task) {
                    if (!task.due_date || this.isOverdue(task)) return false;
                    const due = new Date(task.due_date + 'T23:59:59');
                    const twoDaysFromNow = new Date();
                    twoDaysFromNow.setDate(twoDaysFromNow.getDate() + 2);
                    return due <= twoDaysFromNow;
                },
            };
        }
    </script>
</x-app-layout>
