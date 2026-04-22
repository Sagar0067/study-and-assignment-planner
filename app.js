let globalAssignments = [];
let globalSyllabus    = [];
let activeTopicId     = null; // null = show all tasks
let currentCalendarDate = new Date();
let assignmentChart = null;

// Immediately Apply Theme on Load to prevent flickering
const storedTheme = localStorage.getItem('study_planner_theme') || 'dark';
document.documentElement.setAttribute('data-theme', storedTheme);
if (document.body) document.body.setAttribute('data-theme', storedTheme);

document.addEventListener('DOMContentLoaded', () => {
    // Re-apply specifically to body safely
    document.body.setAttribute('data-theme', storedTheme);
    const themeIcon = document.getElementById('themeIcon');
    if (themeIcon) {
        themeIcon.className = storedTheme === 'dark' ? 'fas fa-moon fs-5' : 'fas fa-sun fs-5';
    }

    // Theme Toggle Handler
    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        themeBtn.addEventListener('click', () => {
            const currentTheme = document.body.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.body.setAttribute('data-theme', newTheme);
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('study_planner_theme', newTheme);
            
            const icon = document.getElementById('themeIcon');
            if (icon) icon.className = newTheme === 'dark' ? 'fas fa-moon fs-5' : 'fas fa-sun fs-5';
            
            // Re-render chart to adapt colors
            if (assignmentChart) {
                assignmentChart.destroy();
                assignmentChart = null;
                renderAnalytics();
            }
        });
    }

    // Attach Event Listeners for Dashboard
    const addTaskForm = document.getElementById('add-task-form');
    if (addTaskForm) addTaskForm.addEventListener('submit', addTask);
    
    const addTopicForm = document.getElementById('add-topic-form');
    if (addTopicForm) addTopicForm.addEventListener('submit', addTopic);

    // Initial Data Fetch
    if (document.getElementById('syllabus-list')) {
        fetchSyllabus();
        fetchTasks();
    }
});

function showAlert(msg, type) {
    const alertBox = document.getElementById('auth-alert');
    if (alertBox) {
        alertBox.className = `alert alert-${type} pb-2 pt-2 small`;
        alertBox.innerText = msg;
        alertBox.classList.remove('d-none');
    } else {
        alert(msg);
    }
}

// ---------------------------
// GLOBAL LOADER UTILITY
// ---------------------------
function toggleLoader(btn, isLoading) {
    if (!btn) return;
    const textSpan = btn.querySelector('.btn-text');
    const spinner = btn.querySelector('.spinner-border');
    if (!textSpan || !spinner) return;

    if (isLoading) {
        btn.disabled = true;
        textSpan.classList.add('opacity-0');
        spinner.classList.remove('d-none');
        spinner.style.position = 'absolute';
    } else {
        btn.disabled = false;
        textSpan.classList.remove('opacity-0');
        spinner.classList.add('d-none');
    }
}

// Utility to handle DB and Auth errors
function checkError(data) {
    if (data.status === "error" && data.message && data.message.includes("Unauthorized")) {
        window.location.href = 'login.php';
        throw new Error("Unauthorized");
    }
    if (data.error || data.status === "error") {
        const errBox = document.getElementById('db-error');
        if (errBox) {
            errBox.classList.remove('d-none');
            errBox.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${data.error || data.message}`;
        }
        throw new Error(data.error || data.message);
    }
}

// ---------------------------
// SYLLABUS LOGIC
// ---------------------------

async function fetchSyllabus() {
    try {
        const response = await fetch('api.php?action=get_syllabus');
        const data = await response.json();
        if (data && !data.error && !data.status) {
            globalSyllabus = data;
            renderSyllabus(data);
        } else {
            checkError(data);
        }
    } catch (e) { console.error(e); }
}

function renderSyllabus(items) {
    const list = document.getElementById('syllabus-list');
    if (!list) return;
    list.innerHTML = '';

    let completedCount = 0;

    items.forEach(item => {
        if (item.completed == 1) completedCount++;

        const isActive = activeTopicId === parseInt(item.id);

        const li = document.createElement('li');
        li.className = `list-group-item d-flex justify-content-between align-items-center syllabus-item ${item.completed == 1 ? 'completed' : ''} ${isActive ? 'topic-active' : ''}`;

        li.innerHTML = `
            <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                <input class="form-check-input cursor-pointer me-3 flex-shrink-0" type="checkbox" id="topic_${item.id}"
                    ${item.completed == 1 ? 'checked' : ''}
                    onchange="toggleSyllabus(${item.id}, this.checked); event.stopPropagation();">
                <!-- Clicking the label selects/deselects the topic filter -->
                <span class="topic-label flex-grow-1 cursor-pointer text-truncate ${isActive ? 'text-accent fw-semibold' : 'text-theme'}"
                      onclick="selectTopic(${item.id}, '${item.title.replace(/'/g, "\\'")}')"
                      title="Click to filter tasks for this topic">
                    ${item.title}
                    ${isActive ? '<i class="fas fa-filter ms-1" style="font-size:0.65rem;"></i>' : ''}
                </span>
            </div>
            <button class="btn btn-sm btn-link text-danger text-opacity-75 p-0 ms-2 flex-shrink-0" onclick="deleteTopic(${item.id})">
                <i class="fas fa-trash-alt"></i>
            </button>
        `;
        list.appendChild(li);
    });

    updateProgressBar(completedCount, items.length);
}

// ---------------------------
// TOPIC FILTER LOGIC
// ---------------------------

function selectTopic(topicId, topicName) {
    const numericId = parseInt(topicId);

    if (activeTopicId === numericId) {
        // Clicking the active topic again clears the filter
        clearTopicFilter();
        return;
    }

    activeTopicId = numericId;

    // Show banner in syllabus header
    const banner   = document.getElementById('active-topic-banner');
    const nameSpan = document.getElementById('active-topic-name');
    if (banner && nameSpan) {
        nameSpan.textContent = topicName;
        banner.classList.remove('d-none');
    }

    // Update modal topic hint
    updateModalTopicHint();

    // Re-render syllabus (highlights active row) and kanban (filtered)
    renderSyllabus(globalSyllabus);
    renderTasks(globalAssignments);
}

function clearTopicFilter() {
    activeTopicId = null;

    const banner = document.getElementById('active-topic-banner');
    if (banner) banner.classList.add('d-none');

    updateModalTopicHint();
    renderSyllabus(globalSyllabus);
    renderTasks(globalAssignments);
}

// Sync the "Filing under: X" hint inside the Add Task modal
function updateModalTopicHint() {
    const hint     = document.getElementById('task-modal-topic-hint');
    const nameSpan = document.getElementById('task-modal-topic-name');
    if (!hint || !nameSpan) return;

    if (activeTopicId !== null) {
        const topic = globalSyllabus.find(s => parseInt(s.id) === activeTopicId);
        if (topic) {
            nameSpan.textContent = topic.title;
            hint.classList.remove('d-none');
        }
    } else {
        hint.classList.add('d-none');
    }
}

function updateProgressBar(completed, total) {
    const progressText = document.getElementById('progress-text');
    const progressBar = document.getElementById('syllabus-progress');
    
    let percent = 0;
    if (total > 0) percent = Math.round((completed / total) * 100);
    
    if (progressText) progressText.innerText = `${percent}%`;
    if (progressBar) progressBar.style.width = `${percent}%`;
}

async function addTopic(e) {
    e.preventDefault();
    const input = document.getElementById('topic-input');
    const btn = document.getElementById('addTopicBtn');
    
    const title = input.value.trim();
    if (!title) return;

    toggleLoader(btn, true);
    try {
        const response = await fetch('api.php?action=add_syllabus', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title })
        });
        const result = await response.json();
        checkError(result);
        
        if (result.status === 'success') {
            input.value = '';
            fetchSyllabus();
        }
    } finally {
        toggleLoader(btn, false);
    }
}

async function toggleSyllabus(id, completed) {
    await fetch('api.php?action=toggle_syllabus', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, completed: completed ? 1 : 0 })
    });
    fetchSyllabus();
}

async function deleteTopic(id) {
    if (confirm("Delete this topic? Tasks linked to it will become general tasks.")) {
        await fetch('api.php?action=delete_syllabus', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });

        // If we were filtering by this topic, clear it
        if (activeTopicId === id) clearTopicFilter();

        fetchSyllabus();
        fetchTasks(); // refresh tasks (syllabus_title may have changed)
    }
}

// ---------------------------
// KANBAN TASKS LOGIC
// ---------------------------

async function fetchTasks() {
    try {
        const response = await fetch('api.php?action=get_tasks');
        const data = await response.json();
        if (data && !data.error && !data.status) {
            globalAssignments = data;
            renderTasks(data);
            renderCalendar();
            renderAnalytics();
        } else {
            checkError(data);
        }
    } catch(e) { console.error(e); }
}

function triggerFilter() {
    renderTasks(globalAssignments);
}

function renderTasks(tasks) {
    document.getElementById('todo-column').innerHTML = '';
    document.getElementById('inprogress-column').innerHTML = '';
    document.getElementById('done-column').innerHTML = '';

    const searchTerm    = document.getElementById('search-input')    ? document.getElementById('search-input').value.toLowerCase() : '';
    const filterPriority = document.getElementById('filter-priority') ? document.getElementById('filter-priority').value : 'all';
    const filterStatus  = document.getElementById('filter-status')   ? document.getElementById('filter-status').value  : 'all';

    let filteredTasks = tasks.filter(task => {
        if (searchTerm     && !task.title.toLowerCase().includes(searchTerm)) return false;
        if (filterPriority !== 'all' && task.priority !== filterPriority)     return false;
        if (filterStatus   !== 'all' && task.status   !== filterStatus)       return false;

        // Topic filter: if a topic is selected, only show tasks linked to it
        if (activeTopicId !== null) {
            // Convert to int for safe comparison (DB returns strings sometimes)
            if (parseInt(task.syllabus_id) !== activeTopicId) return false;
        }

        return true;
    });

    filteredTasks.forEach(task => {
        const card = document.createElement('div');
        card.className = 'kanban-task p-3 mb-3';
        card.draggable = true;
        card.id = `task_${task.id}`;
        card.dataset.id = task.id;
        card.ondragstart = drag;

        let deadlineHtml = '';
        let prioritySpan = `<span class="badge badge-priority-${task.priority} px-2 py-1 ms-1 text-capitalize">${task.priority}</span>`;
        let dueSoonSpan = '';

        // Topic tag chip — shown on the card
        let topicChip = '';
        if (task.syllabus_title) {
            topicChip = `<span class="badge badge-topic px-2 py-1 ms-1">
                <i class="fas fa-tag me-1" style="font-size:0.6rem;"></i>${task.syllabus_title}
            </span>`;
        }

        if (task.deadline) {
            const dateObj = new Date(task.deadline);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const diffTime = dateObj - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            let textClass = "text-muted";
            if (diffDays < 0 && task.status !== 'done') {
                textClass = "text-danger";
                card.classList.add('glow-danger');
            } else if (diffDays <= 3 && diffDays >= 0 && task.status !== 'done') {
                textClass = "text-warning";
                dueSoonSpan = `<span class="badge badge-due-soon ms-1 px-2 py-1"><i class="fas fa-fire me-1"></i>Due</span>`;
            }
            
            deadlineHtml = `<div class="mt-2 small ${textClass}"><i class="fas fa-clock me-1"></i>${dateObj.toLocaleDateString()}</div>`;
        }

        // Notes icon — only shown when the task has notes
        let notesIcon = '';
        if (task.notes && task.notes.trim() !== '') {
            notesIcon = `<span class="notes-icon" title="View / edit notes" onclick="event.stopPropagation(); viewTask(${task.id})">
                <i class="fas fa-sticky-note"></i>
            </span>`;
        }

        card.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1 cursor-pointer" onclick="viewTask(${task.id})" title="View notes">
                    <h6 class="fw-semibold mb-1">${task.title}</h6>
                    <div class="d-flex align-items-center mt-1 flex-wrap gap-1">
                        ${prioritySpan} ${dueSoonSpan} ${topicChip}
                    </div>
                </div>
                <div class="d-flex align-items-center ms-2 gap-1 flex-shrink-0">
                    ${notesIcon}
                    <button class="btn btn-sm btn-link text-muted p-0" onclick="deleteTask(${task.id})"><i class="fas fa-times"></i></button>
                </div>
            </div>
            ${deadlineHtml}
        `;

        const column = document.getElementById(`${task.status}-column`);
        if (column) column.appendChild(card);
    });
}

function prepareTaskModal(status) {
    document.getElementById('task-status').value = status;
    // Clear the notes field so it doesn't carry over from a previous open
    const notesEl = document.getElementById('task-notes');
    if (notesEl) notesEl.value = '';
    // Refresh the modal hint to show which topic tasks will be filed under
    updateModalTopicHint();
}

async function addTask(e) {
    e.preventDefault();
    const btn = document.getElementById('addTaskSubmitBtn');

    const titleInput    = document.getElementById('task-title');
    const deadlineInput = document.getElementById('task-deadline');
    const statusInput   = document.getElementById('task-status');
    const priorityInput = document.getElementById('task-priority');
    const notesInput    = document.getElementById('task-notes');

    const title    = titleInput.value.trim();
    const deadline = deadlineInput.value;
    const status   = statusInput.value;
    const priority = priorityInput ? priorityInput.value : 'medium';
    const notes    = notesInput    ? notesInput.value.trim() : '';

    // Auto-link to the currently active topic
    const syllabus_id = activeTopicId !== null ? activeTopicId : null;

    if (!title) return;

    toggleLoader(btn, true);
    try {
        const response = await fetch('api.php?action=add_task', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, deadline, status, priority, syllabus_id, notes })
        });

        const result = await response.json();
        checkError(result);

        if (result.status === 'success') {
            titleInput.value    = '';
            deadlineInput.value = '';
            if (notesInput) notesInput.value = '';
            const modal = bootstrap.Modal.getInstance(document.getElementById('addTaskModal'));
            modal.hide();
            fetchTasks();
        }
    } finally {
        toggleLoader(btn, false);
    }
}

// Open the Task Detail modal and populate it with the task's data
function viewTask(taskId) {
    const task = globalAssignments.find(t => parseInt(t.id) === parseInt(taskId));
    if (!task) return;

    // Populate title
    document.getElementById('detailTaskTitle').textContent = task.title;
    document.getElementById('detailTaskId').value = task.id;

    // Populate meta badges (priority, deadline, topic)
    const meta = document.getElementById('detailTaskMeta');
    let priorityColor = { high: 'danger', medium: 'info', low: 'success' }[task.priority] || 'secondary';
    let metaHtml = `<span class="badge badge-priority-${task.priority} px-2 py-1 text-capitalize">${task.priority}</span>`;

    if (task.deadline) {
        const d = new Date(task.deadline);
        metaHtml += `<span class="badge bg-secondary bg-opacity-50 px-2 py-1"><i class="fas fa-calendar me-1"></i>${d.toLocaleDateString()}</span>`;
    }
    if (task.syllabus_title) {
        metaHtml += `<span class="badge badge-topic px-2 py-1"><i class="fas fa-tag me-1" style="font-size:0.6rem;"></i>${task.syllabus_title}</span>`;
    }
    meta.innerHTML = metaHtml;

    // Populate notes textarea
    document.getElementById('detailTaskNotes').value = task.notes || '';

    // Hide any previous save confirmation
    document.getElementById('detailNotesSaveStatus').classList.add('d-none');

    const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
    modal.show();
}

// Save the notes for a task
async function saveTaskNotes() {
    const btn   = document.getElementById('saveNotesBtn');
    const id    = document.getElementById('detailTaskId').value;
    const notes = document.getElementById('detailTaskNotes').value;

    toggleLoader(btn, true);
    try {
        const response = await fetch('api.php?action=update_task_notes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, notes })
        });
        const result = await response.json();
        checkError(result);

        if (result.status === 'success') {
            // Update the in-memory task so the icon updates on re-render
            const task = globalAssignments.find(t => parseInt(t.id) === parseInt(id));
            if (task) task.notes = notes;

            // Show "Saved" confirmation
            const status = document.getElementById('detailNotesSaveStatus');
            status.classList.remove('d-none');
            setTimeout(() => status.classList.add('d-none'), 2500);

            // Re-render cards so the 📝 icon appears/disappears correctly
            renderTasks(globalAssignments);
        }
    } finally {
        toggleLoader(btn, false);
    }
}

async function deleteTask(id) {
    if (confirm("Delete this assignment?")) {
        await fetch('api.php?action=delete_task', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        fetchTasks();
    }
}

// ---------------------------
// DRAG AND DROP KANBAN LOGIC
// ---------------------------

function drag(ev) {
    const card = ev.target.closest('.kanban-task');
    if (!card) return;
    ev.dataTransfer.setData("text", card.dataset.id);
    card.classList.add('dragging');
}

function allowDrop(ev) {
    ev.preventDefault();
    const container = ev.target.closest('.column-container');
    if (container) container.classList.add('drag-over');
}

async function drop(ev) {
    ev.preventDefault();
    document.querySelectorAll('.column-container').forEach(col => col.classList.remove('drag-over'));
    
    const taskId = ev.dataTransfer.getData("text");
    if (!taskId) return;

    const taskElement = document.getElementById(`task_${taskId}`);
    if (!taskElement) return;
    
    taskElement.style.opacity = "1"; 
    taskElement.classList.remove('dragging');
    
    const container = ev.target.closest('.column-container');
    if (container) {
        const newStatus    = container.dataset.status;
        const kanbanColumn = container.querySelector('.kanban-column');
        kanbanColumn.appendChild(taskElement);

        const taskObj = globalAssignments.find(t => t.id == taskId);
        if (taskObj) taskObj.status = newStatus;
        renderTasks(globalAssignments);
        renderAnalytics();

        await fetch('api.php?action=update_task_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: taskId, status: newStatus })
        });
    }
}

document.addEventListener("dragleave", (ev) => {
    const container = ev.target.closest('.column-container');
    if (container && !container.contains(ev.relatedTarget)) {
        container.classList.remove('drag-over');
    }
});

document.addEventListener("dragend", (ev) => {
    ev.target.classList.remove('dragging');
    document.querySelectorAll('.column-container').forEach(c => c.classList.remove('drag-over'));
});

// ---------------------------
// VIEW CONTROLLER
// ---------------------------

function switchView(view) {
    const kanban   = document.getElementById('kanban-view');
    const calendar = document.getElementById('calendar-view');
    if (view === 'kanban') {
        kanban.classList.remove('d-none');
        kanban.classList.add('d-flex');
        calendar.classList.add('d-none');
        calendar.classList.remove('d-flex');
    } else {
        kanban.classList.add('d-none');
        kanban.classList.remove('d-flex');
        calendar.classList.remove('d-none');
        calendar.classList.add('d-flex');
        renderCalendar();
    }
}

// ---------------------------
// CALENDAR ALGORITHM
// ---------------------------

function changeMonth(delta) {
    currentCalendarDate.setMonth(currentCalendarDate.getMonth() + delta);
    renderCalendar();
}

function renderCalendar() {
    const monthYearStr = document.getElementById('calendar-month-year');
    const grid         = document.getElementById('calendar-days');
    if (!monthYearStr || !grid) return;

    const year  = currentCalendarDate.getFullYear();
    const month = currentCalendarDate.getMonth();
    
    const defaultLang = window.navigator.language || 'en-US';
    monthYearStr.innerText = new Date(year, month, 1).toLocaleDateString(defaultLang, { month: 'long', year: 'numeric' });

    const firstDayIndex = new Date(year, month, 1).getDay();
    const daysInMonth   = new Date(year, month + 1, 0).getDate();
    
    grid.innerHTML = '';
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let i = 0; i < firstDayIndex; i++) {
        const d = document.createElement('div');
        d.className = 'calendar-day empty-day';
        grid.appendChild(d);
    }

    for (let i = 1; i <= daysInMonth; i++) {
        const d = document.createElement('div');
        d.className = 'calendar-day';
        
        const cellDate = new Date(year, month, i);
        
        if (cellDate.getTime() === today.getTime()) d.classList.add('today');

        const mStr       = String(month + 1).padStart(2, '0');
        const dStr       = String(i).padStart(2, '0');
        const dateString = `${year}-${mStr}-${dStr}`;

        // Respect active topic filter in calendar too
        const pool = activeTopicId !== null
            ? globalAssignments.filter(t => parseInt(t.syllabus_id) === activeTopicId)
            : globalAssignments;

        const dayTasks = pool.filter(t => t.deadline === dateString);

        let indicatorsHtml = '';
        dayTasks.forEach(task => {
            let type = 'task-upcoming';
            if (task.status === 'done')            type = 'task-done';
            else if (task.status === 'inprogress') type = 'task-inprogress';
            else if (cellDate < today)             type = 'task-overdue';
            indicatorsHtml += `<span class="calendar-task-indicator ${type}"></span>`;
        });

        d.innerHTML = `
            <div class="day-number">${i}</div>
            <div class="calendar-indicators">${indicatorsHtml}</div>
        `;
        
        d.onclick = () => showDayTasks(dateString, cellDate);
        grid.appendChild(d);
    }
}

function showDayTasks(dateString, dateObj) {
    const list  = document.getElementById('dayDetailList');
    const title = document.getElementById('dayDetailTitle');
    
    const pool = activeTopicId !== null
        ? globalAssignments.filter(t => parseInt(t.syllabus_id) === activeTopicId)
        : globalAssignments;

    const tasks = pool.filter(t => t.deadline === dateString);
    title.innerText = `Assignments for ${dateObj.toLocaleDateString()}`;
    
    if (tasks.length === 0) {
        list.innerHTML = `<p class="text-center text-muted mb-0 small">No assignments due on this date.</p>`;
    } else {
        list.innerHTML = '';
        tasks.forEach(t => {
            let statSpan = '';
            if (t.status === 'done')            statSpan = `<span class="badge bg-success ms-2 small">Done</span>`;
            else if (t.status === 'inprogress') statSpan = `<span class="badge bg-warning ms-2 small">In Progress</span>`;
            else                                statSpan = `<span class="badge bg-danger ms-2 small">To Do</span>`;

            const topicTag = t.syllabus_title
                ? `<span class="badge badge-topic ms-1 small">${t.syllabus_title}</span>`
                : '';

            list.innerHTML += `
                <div class="glass-panel p-3 mb-2 rounded border border-theme d-flex justify-content-between align-items-center">
                    <div class="fw-semibold text-theme">${t.title} ${topicTag}</div>
                    <div>${statSpan}</div>
                </div>
            `;
        });
    }

    const modal = new bootstrap.Modal(document.getElementById('dayDetailModal'));
    modal.show();
}

// ---------------------------
// PROGRESS ANALYTICS
// ---------------------------

function renderAnalytics() {
    const total = globalAssignments.length;
    let completed = 0, pending = 0, inprogress = 0, todo = 0;

    globalAssignments.forEach(t => {
        if (t.status === 'done')            completed++;
        else if (t.status === 'inprogress') { inprogress++; pending++; }
        else                                { todo++;       pending++; }
    });

    const percent = total === 0 ? 0 : Math.round((completed / total) * 100);

    const statTotal     = document.getElementById('stat-total');
    if (statTotal)      statTotal.innerText = total;
    
    const statCompleted = document.getElementById('stat-completed');
    if (statCompleted)  statCompleted.innerText = completed;

    const statPending   = document.getElementById('stat-pending');
    if (statPending)    statPending.innerText = pending;

    const statPercent   = document.getElementById('stat-percent');
    if (statPercent)    statPercent.innerText = `${percent}%`;

    const ctx = document.getElementById('assignmentChart');
    if (!ctx) return;

    if (assignmentChart !== null) {
        assignmentChart.data.datasets[0].data = [completed, inprogress, todo];
        assignmentChart.update();
    } else {
        const style        = getComputedStyle(document.body);
        const colorSuccess = style.getPropertyValue('--success-color').trim() || '#10b981';
        const colorWarning = style.getPropertyValue('--warning-color').trim() || '#f59e0b';
        const colorDanger  = style.getPropertyValue('--danger-color').trim()  || '#ef4444';

        assignmentChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'To Do'],
                datasets: [{
                    data: [completed, inprogress, todo],
                    backgroundColor: [colorSuccess, colorWarning, colorDanger],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: style.getPropertyValue('--text-color').trim() || '#94a3b8', font: {family: "'Inter', sans-serif"} }
                    }
                }
            }
        });
    }
}

// ---------------------------
// DATA PURGE MECHANIC
// ---------------------------
async function triggerDataWipe() {
    if (!confirm("CRITICAL WARNING: This will permanently eradicate all Assignments and Syllabus Modules. Are you certain?")) return;
    
    const btn = document.getElementById('resetDataBtn');
    toggleLoader(btn, true);
    
    try {
        const response = await fetch('api.php?action=reset_data', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await response.json();
        if (data.status === 'success') {
            const modal = bootstrap.Modal.getInstance(document.getElementById('profileModal'));
            modal.hide();
            activeTopicId     = null;
            globalSyllabus    = [];
            globalAssignments = [];
            fetchSyllabus();
            fetchTasks();
        } else {
            alert(data.message || "Failed to wipe data.");
        }
    } catch(e) { console.error(e); } finally { toggleLoader(btn, false); }
}
