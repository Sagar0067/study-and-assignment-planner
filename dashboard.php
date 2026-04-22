<?php
require_once 'db.php';

// Protect Page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Study Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body data-theme="dark">

    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    <div class="bg-orb orb-3"></div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg glass-nav mb-4 sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-book-reader me-2 text-accent"></i>Planner
            </a>
            <div class="ms-auto d-flex align-items-center">
                <button class="btn btn-link text-theme me-3 text-decoration-none px-1" id="themeToggle"><i class="fas fa-moon fs-5" id="themeIcon"></i></button>
                <button class="btn btn-link text-theme me-3 text-decoration-none px-1" data-bs-toggle="modal" data-bs-target="#aboutModal" title="About Project"><i class="fas fa-info-circle fs-5"></i></button>
                <div class="dropdown">
                    <button class="btn btn-outline-theme btn-sm rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($username); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dark-dropdown position-absolute mt-2">
                        <li><a class="dropdown-item text-theme text-opacity-75" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i class="fas fa-id-badge me-2 text-accent"></i> My Profile</a></li>
                        <li><hr class="dropdown-divider border-theme"></li>
                        <li><a class="dropdown-item text-danger" href="login.php?action=logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container pb-5 position-relative z-1">

        <div id="db-error" class="alert alert-danger d-none glass-card border-danger text-theme" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> Database connection failed.
        </div>

        <!-- ANALYTICS ROW -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card glass-card h-100 p-3 flex-row align-items-center justify-content-between">
                    <div>
                        <div class="text-theme text-opacity-75 small text-uppercase tracking-wide">Total Tasks</div>
                        <h3 class="fw-bold text-theme mb-0" id="stat-total">0</h3>
                    </div>
                    <i class="fas fa-layer-group fa-2x text-theme text-opacity-25"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card glass-card h-100 p-3 flex-row align-items-center justify-content-between border-success border-opacity-25">
                    <div>
                        <div class="text-success small text-uppercase tracking-wide">Completed</div>
                        <h3 class="fw-bold text-success mb-0" id="stat-completed">0</h3>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success text-opacity-25"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card glass-card h-100 p-3 flex-row align-items-center justify-content-between border-warning border-opacity-25">
                    <div>
                        <div class="text-warning small text-uppercase tracking-wide">Pending</div>
                        <h3 class="fw-bold text-warning mb-0" id="stat-pending">0</h3>
                    </div>
                    <i class="fas fa-hourglass-half fa-2x text-warning text-opacity-25"></i>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card glass-card h-100 p-3 flex-row align-items-center justify-content-between border-accent border-opacity-25">
                    <div>
                        <div class="text-accent small text-uppercase tracking-wide">Completion</div>
                        <h3 class="fw-bold text-accent mb-0" id="stat-percent">0%</h3>
                    </div>
                    <i class="fas fa-chart-pie fa-2x text-accent text-opacity-25"></i>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Syllabus Section -->
            <div class="col-lg-4">
                <div class="card glass-card h-100 h-100-mobile">
                    <div class="card-header bg-transparent border-bottom border-theme pt-4 pb-3">
                        <h5 class="card-title fw-bold text-theme mb-0">
                            <i class="fas fa-list-check me-2 text-accent"></i>Syllabus Tracker
                        </h5>
                        <!-- Active topic filter indicator -->
                        <div id="active-topic-banner" class="d-none mt-2 px-2 py-1 rounded d-flex align-items-center justify-content-between topic-filter-banner">
                            <span class="small fw-semibold text-accent">
                                <i class="fas fa-filter me-1"></i>
                                Showing: <span id="active-topic-name"></span>
                            </span>
                            <button class="btn btn-sm btn-link text-theme p-0 ms-2" onclick="clearTopicFilter()" title="Show all tasks">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-theme text-opacity-75 small fw-semibold">Mastery</span>
                                <span class="text-accent small fw-bold" id="progress-text" style="text-shadow: 0 0 10px var(--accent-color);">0%</span>
                            </div>
                            <div class="progress custom-progress">
                                <div id="syllabus-progress" class="progress-bar custom-progress-bar" role="progressbar" style="width: 0%;"></div>
                            </div>
                        </div>

                        <!-- Add Topic Form -->
                        <form id="add-topic-form" class="mb-4">
                            <div class="input-group dark-input-group">
                                <input type="text" id="topic-input" class="form-control text-theme bg-transparent" placeholder="New module or topic..." required>
                                <button class="btn btn-accent d-flex align-items-center justify-content-center" type="submit" id="addTopicBtn" style="width: 45px;">
                                    <span class="btn-text"><i class="fas fa-plus"></i></span>
                                    <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                </button>
                            </div>
                        </form>

                        <!-- Topic List -->
                        <ul class="list-group list-group-flush bg-transparent" id="syllabus-list">
                            <!-- Dynamic Syllabus Items -->
                        </ul>
                    </div>
                </div>

                <!-- CHART VISUALIZATION SECTION -->
                <div class="card glass-card mt-4">
                    <div class="card-header bg-transparent border-bottom border-theme pt-4 pb-3">
                        <h5 class="card-title fw-bold text-theme mb-0"><i class="fas fa-chart-doughnut me-2 text-accent"></i>Progress Overview</h5>
                    </div>
                    <div class="card-body py-4 d-flex justify-content-center">
                        <div style="width: 200px; height: 200px; position: relative;">
                            <canvas id="assignmentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kanban Board Section -->
            <div class="col-lg-8">
                <div class="card glass-card h-100">
                    <div class="card-header bg-transparent border-bottom border-theme pt-4 pb-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold text-theme mb-0">
                            <i class="fas fa-tasks me-2 text-accent"></i>Assignments
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="viewToggle" id="btn-kanban" autocomplete="off" checked onchange="switchView('kanban')">
                            <label class="btn btn-outline-info" for="btn-kanban"><i class="fas fa-trello"></i> Kanban</label>
                            <input type="radio" class="btn-check" name="viewToggle" id="btn-calendar" autocomplete="off" onchange="switchView('calendar')">
                            <label class="btn btn-outline-info" for="btn-calendar"><i class="fas fa-calendar-alt"></i> Calendar</label>
                        </div>
                    </div>

                    <div class="card-body py-4">
                        <!-- Advanced Controls Pipeline -->
                        <div class="d-flex flex-column flex-md-row gap-3 mb-4 p-3 glass-panel rounded">
                            <div class="flex-grow-1">
                                <div class="input-group dark-input-group h-100">
                                    <span class="input-group-text border-end-0 text-theme text-opacity-50 bg-transparent"><i class="fas fa-search"></i></span>
                                    <input type="text" id="search-input" class="form-control border-start-0 text-theme bg-transparent" placeholder="Search tasks..." oninput="triggerFilter()">
                                </div>
                            </div>
                            <select id="filter-priority" class="form-select dark-input w-auto text-theme cursor-pointer" onchange="triggerFilter()">
                                <option value="all">All Priorities</option>
                                <option value="high">High Priority</option>
                                <option value="medium">Medium Priority</option>
                                <option value="low">Low Priority</option>
                            </select>
                            <select id="filter-status" class="form-select dark-input w-auto text-theme cursor-pointer" onchange="triggerFilter()">
                                <option value="all">All Statuses</option>
                                <option value="todo">To Do</option>
                                <option value="inprogress">In Progress</option>
                                <option value="done">Done</option>
                            </select>
                        </div>

                        <!-- Kanban View -->
                        <div id="kanban-view" class="row g-3">
                            <!-- To Do Column -->
                            <div class="col-md-4">
                                <div class="column-container h-100 glass-panel p-3" data-status="todo" ondrop="drop(event)" ondragover="allowDrop(event)">
                                    <h6 class="text-uppercase text-theme text-opacity-75 fw-bold mb-3 small d-flex justify-content-between align-items-center tracking-wide">
                                        <span class="d-flex align-items-center"><div class="status-indicator bg-danger"></div> To Do</span>
                                        <button class="btn btn-sm btn-link text-accent p-0" onclick="prepareTaskModal('todo')" data-bs-toggle="modal" data-bs-target="#addTaskModal"><i class="fas fa-plus"></i></button>
                                    </h6>
                                    <div id="todo-column" class="kanban-column min-vh-50"></div>
                                </div>
                            </div>

                            <!-- In Progress Column -->
                            <div class="col-md-4">
                                <div class="column-container h-100 glass-panel p-3" data-status="inprogress" ondrop="drop(event)" ondragover="allowDrop(event)">
                                    <h6 class="text-uppercase text-theme text-opacity-75 fw-bold mb-3 small d-flex align-items-center tracking-wide">
                                        <div class="status-indicator bg-warning shadow-warning"></div> In Progress
                                    </h6>
                                    <div id="inprogress-column" class="kanban-column min-vh-50"></div>
                                </div>
                            </div>

                            <!-- Done Column -->
                            <div class="col-md-4">
                                <div class="column-container h-100 glass-panel p-3" data-status="done" ondrop="drop(event)" ondragover="allowDrop(event)">
                                    <h6 class="text-uppercase text-theme text-opacity-75 fw-bold mb-3 small d-flex justify-content-between align-items-center tracking-wide">
                                        <span class="d-flex align-items-center"><div class="status-indicator bg-success shadow-success"></div> Done</span>
                                        <button class="btn btn-sm btn-link text-accent p-0" onclick="prepareTaskModal('done')" data-bs-toggle="modal" data-bs-target="#addTaskModal"><i class="fas fa-plus"></i></button>
                                    </h6>
                                    <div id="done-column" class="kanban-column min-vh-50"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar View -->
                        <div id="calendar-view" class="d-none flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <button class="btn btn-sm btn-outline-theme" onclick="changeMonth(-1)"><i class="fas fa-chevron-left"></i></button>
                                <h5 class="text-theme fw-bold mb-0" id="calendar-month-year">Month Year</h5>
                                <button class="btn btn-sm btn-outline-theme" onclick="changeMonth(1)"><i class="fas fa-chevron-right"></i></button>
                            </div>
                            <div class="calendar-grid calendar-header-row text-theme text-opacity-75 small fw-bold text-center mb-2">
                                <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                            </div>
                            <div id="calendar-days" class="calendar-grid grid-days flex-grow-1">
                                <!-- Rendered dynamically -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Task Modal -->
    <div class="modal fade dark-modal" id="addTaskModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 shadow-lg">
                <div class="modal-header border-bottom border-theme d-flex align-items-start">
                    <div>
                        <h5 class="modal-title text-theme fw-bold mb-1">Initiate Task</h5>
                        <!-- Shows which topic this task will be filed under -->
                        <div id="task-modal-topic-hint" class="d-none">
                            <span class="badge badge-topic px-2 py-1">
                                <i class="fas fa-tag me-1" style="font-size:0.6rem;"></i>
                                <span id="task-modal-topic-name"></span>
                            </span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="add-task-form">
                    <div class="modal-body py-4">
                        <div class="mb-3">
                            <label class="form-label text-theme text-opacity-75 small text-uppercase tracking-wide">Task Designation</label>
                            <input type="text" class="form-control dark-input h-auto py-2" id="task-title" required placeholder="e.g. Neural Network Lab">
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-theme text-opacity-75 small text-uppercase tracking-wide">Priority</label>
                            <select class="form-select dark-input h-auto py-2 text-theme" id="task-priority">
                                <option value="medium">Medium</option>
                                <option value="high">High (Urgent)</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-theme text-opacity-75 small text-uppercase tracking-wide">Deadline (Optional)</label>
                            <input type="date" class="form-control dark-input h-auto py-2" id="task-deadline">
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-theme text-opacity-75 small text-uppercase tracking-wide">
                                <i class="fas fa-sticky-note me-1 text-accent"></i>Notes / Description
                                <span class="text-muted fw-normal ms-1">(optional)</span>
                            </label>
                            <textarea class="form-control dark-input" id="task-notes" rows="3"
                                placeholder="Add extra context, links, or reminders..."
                                style="resize:vertical; min-height:80px;"></textarea>
                        </div>
                        <input type="hidden" id="task-status" value="todo">
                    </div>
                    <div class="modal-footer border-top border-theme">
                        <button type="button" class="btn btn-link text-theme text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="addTaskSubmitBtn" class="btn btn-accent px-4 rounded-pill">
                            <span class="btn-text">Engage</span>
                            <span class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== Task Detail / Notes Modal ===== -->
    <div class="modal fade dark-modal" id="taskDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 shadow-lg">
                <div class="modal-header border-bottom border-theme">
                    <div class="flex-grow-1 me-3" style="min-width:0;">
                        <h5 class="modal-title text-theme fw-bold mb-1 text-truncate" id="detailTaskTitle">Task</h5>
                        <div class="d-flex align-items-center gap-2 flex-wrap" id="detailTaskMeta"></div>
                    </div>
                    <button type="button" class="btn-close btn-close flex-shrink-0" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <label class="form-label text-theme text-opacity-75 small text-uppercase tracking-wide mb-2">
                        <i class="fas fa-sticky-note me-1 text-accent"></i>Notes / Description
                    </label>
                    <textarea id="detailTaskNotes" class="form-control dark-input w-100" rows="6"
                        placeholder="No notes yet. Type here to add..."
                        style="resize:vertical; min-height:120px; line-height:1.6;"></textarea>
                    <div id="detailNotesSaveStatus" class="mt-2 small text-muted d-none">
                        <i class="fas fa-check-circle text-success me-1"></i>Saved
                    </div>
                </div>
                <div class="modal-footer border-top border-theme d-flex justify-content-between align-items-center">
                    <span class="small text-theme text-opacity-50">Changes are saved when you click Save</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-link text-theme text-decoration-none" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="saveNotesBtn" class="btn btn-accent px-4 rounded-pill" onclick="saveTaskNotes()">
                            <span class="btn-text"><i class="fas fa-save me-1"></i>Save</span>
                            <span class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                        </button>
                    </div>
                </div>
                <input type="hidden" id="detailTaskId">
            </div>
        </div>
    </div>

    <!-- Day Detail Modal -->
    <div class="modal fade dark-modal" id="dayDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 shadow-lg">
                <div class="modal-header border-bottom border-theme">
                    <h5 class="modal-title text-theme fw-bold" id="dayDetailTitle">Tasks for Date</h5>
                    <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3" id="dayDetailList"></div>
                <div class="modal-footer border-top border-theme">
                    <button type="button" class="btn btn-accent px-4 rounded-pill" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- About Modal -->
    <div class="modal fade dark-modal" id="aboutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 shadow-lg">
                <div class="modal-header border-bottom border-theme">
                    <h5 class="modal-title text-theme fw-bold"><i class="fas fa-book-reader me-2 text-accent"></i> About Study Planner</h5>
                    <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4 text-theme text-opacity-75">
                    <h6 class="text-theme fw-bold">README &amp; Instructions</h6>
                    <p class="small mb-3">Welcome to your secure, ultra-fast productivity environment. Everything is mathematically engineered to keep you focused exclusively on mastery.</p>
                    <ul class="small mb-0 pb-0">
                        <li class="mb-2"><strong>Topic Filter:</strong> Click any topic in the Syllabus Tracker to switch the Kanban board to show only that topic's tasks. Click it again (or press ✕) to go back to all tasks.</li>
                        <li class="mb-2"><strong>Auto-linking:</strong> Any task you add while a topic is selected is automatically filed under that topic.</li>
                        <li class="mb-2"><strong>Smart Due Dates:</strong> Assignments automatically inject fiery tags when their deadline shrinks under 3 days!</li>
                        <li class="mb-2"><strong>Kinetic Workflow:</strong> Drag any task card across columns to update its status instantly!</li>
                    </ul>
                </div>
                <div class="modal-footer border-top border-theme">
                    <button type="button" class="btn btn-accent px-4 rounded-pill" data-bs-dismiss="modal">Acknowledge</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade dark-modal" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-card border-0 shadow-lg">
                <div class="modal-header border-bottom border-danger border-opacity-25">
                    <h5 class="modal-title text-theme fw-bold"><i class="fas fa-user-circle me-2 text-danger"></i> Account Profile</h5>
                    <button type="button" class="btn-close btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4 text-center">
                    <h3 class="text-theme fw-bold mb-1"><?php echo htmlspecialchars($username); ?></h3>
                    <p class="small text-muted mb-4">Study Planner Architect</p>
                    <hr class="border-theme mb-4">
                    <h6 class="text-danger fw-bold text-start mb-3">Danger Zone</h6>
                    <p class="small text-start text-muted">Completely erase all Tasks, Assignments, and Syllabus documentation linked to your precise account. This bypasses structural logic and permanently purges.</p>
                    <button id="resetDataBtn" class="btn btn-danger w-100 fw-bold py-2 mt-2" onclick="triggerDataWipe()">
                        <span class="btn-text"><i class="fas fa-radiation me-2"></i> Wipe All Data</span>
                        <span class="spinner-border spinner-border-sm d-none ms-2" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="app.js"></script>
</body>
</html>
