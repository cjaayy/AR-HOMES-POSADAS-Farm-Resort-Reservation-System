<?php
/**
 * Staff Tasks Management - Track daily tasks and assignments
 */
session_start();
// Accept both admin session (with staff role) OR staff-specific session
$isAdminAsStaff = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && ($_SESSION['admin_role'] ?? '') === 'staff';
$isStaffSession = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] === true;

if (!$isAdminAsStaff && !$isStaffSession) {
    header('Location: ../index.html');
    exit;
}
$staffName = $isStaffSession ? ($_SESSION['staff_full_name'] ?? 'Staff Member') : ($_SESSION['admin_full_name'] ?? 'Staff Member');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Task Management - Staff</title>
  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="../logo/ar-homes-logo.png" />
  <link rel="icon" type="image/png" sizes="16x16" href="../logo/ar-homes-logo.png" />
  <link rel="apple-touch-icon" sizes="180x180" href="../logo/ar-homes-logo.png" />
  <link rel="stylesheet" href="../css/admin-styles.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&family=Bungee+Spice&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
  <script>
    window.logout = window.logout || function(){
      try{
        fetch('staff_logout.php', { method: 'POST', credentials: 'include' })
          .then(res => res.json().catch(() => null))
          .then(() => window.location.href = '../index.html')
          .catch(() => window.location.href = '../index.html');
      }catch(e){ window.location.href = 'staff_logout.php'; }
    };
  </script>
  <style>
    .tasks-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 24px;
    }
    
    .task-card {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      border-left: 4px solid #667eea;
      cursor: pointer;
    }
    
    .task-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
    
    .task-card.priority-high {
      border-left-color: #ef4444;
    }
    
    .task-card.priority-medium {
      border-left-color: #f59e0b;
    }
    
    .task-card.priority-low {
      border-left-color: #10b981;
    }
    
    .task-card.completed {
      opacity: 0.6;
      background: #f8fafc;
    }
    
    .task-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 12px;
    }
    
    .task-title {
      font-weight: 600;
      color: #1e293b;
      font-size: 16px;
      margin-bottom: 8px;
    }
    
    .task-description {
      color: #64748b;
      font-size: 14px;
      line-height: 1.5;
      margin-bottom: 12px;
    }
    
    .task-meta {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      font-size: 13px;
      color: #94a3b8;
    }
    
    .task-meta-item {
      display: flex;
      align-items: center;
      gap: 4px;
    }
    
    .task-actions {
      display: flex;
      gap: 8px;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid #f1f5f9;
    }
    
    .priority-badge {
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
    }
    
    .priority-high {
      background: #fee2e2;
      color: #ef4444;
    }
    
    .priority-medium {
      background: #fef3c7;
      color: #f59e0b;
    }
    
    .priority-low {
      background: #d1fae5;
      color: #10b981;
    }
    
    .task-filter {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }
    
    .filter-btn {
      padding: 8px 16px;
      border-radius: 8px;
      border: 2px solid #e2e8f0;
      background: #fff;
      cursor: pointer;
      transition: all 0.2s;
      font-weight: 500;
    }
    
    .filter-btn.active {
      background: #667eea;
      color: #fff;
      border-color: #667eea;
    }
    
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    
    .modal-content {
      background: #fff;
      border-radius: 16px;
      padding: 24px;
      width: 90%;
      max-width: 500px;
      max-height: 90vh;
      overflow-y: auto;
    }
    
    .form-group {
      margin-bottom: 16px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 6px;
      font-weight: 500;
      color: #1e293b;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      font-size: 14px;
    }
    
    .form-group textarea {
      resize: vertical;
      min-height: 100px;
    }
  </style>
</head>
<body>
  <div class="admin-container">
    <?php include 'staff_header.php'; ?>

    <main class="main-content" style="padding-top:100px;">
      <section class="content-section active">
        <div class="section-header" style="margin-bottom:24px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
              <h2 style="font-size:28px; font-weight:700; color:#1e293b; margin-bottom:8px;">Task Management</h2>
              <p style="color:#64748b;">Organize and track your daily tasks and assignments</p>
            </div>
            <button onclick="openCreateTask()" class="btn-primary" style="display:flex; align-items:center; gap:8px;">
              <i class="fas fa-plus"></i> New Task
            </button>
          </div>
        </div>

        <!-- Task Stats -->
        <div class="staff-quick-grid" style="margin-bottom:24px;">
          <div class="staff-card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div>
                <h3 style="color:#64748b; font-size:14px; margin-bottom:8px;">TOTAL TASKS</h3>
                <div style="font-size:32px; font-weight:700; color:#1e293b;" id="totalTasks">0</div>
              </div>
              <i class="fas fa-tasks" style="font-size:32px; color:#667eea; opacity:0.2;"></i>
            </div>
          </div>
          
          <div class="staff-card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div>
                <h3 style="color:#64748b; font-size:14px; margin-bottom:8px;">IN PROGRESS</h3>
                <div style="font-size:32px; font-weight:700; color:#f59e0b;" id="inProgressTasks">0</div>
              </div>
              <i class="fas fa-spinner" style="font-size:32px; color:#f59e0b; opacity:0.2;"></i>
            </div>
          </div>
          
          <div class="staff-card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div>
                <h3 style="color:#64748b; font-size:14px; margin-bottom:8px;">COMPLETED</h3>
                <div style="font-size:32px; font-weight:700; color:#10b981;" id="completedTasks">0</div>
              </div>
              <i class="fas fa-check-circle" style="font-size:32px; color:#10b981; opacity:0.2;"></i>
            </div>
          </div>
          
          <div class="staff-card">
            <div style="display:flex; justify-content:space-between; align-items:center;">
              <div>
                <h3 style="color:#64748b; font-size:14px; margin-bottom:8px;">HIGH PRIORITY</h3>
                <div style="font-size:32px; font-weight:700; color:#ef4444;" id="highPriorityTasks">0</div>
              </div>
              <i class="fas fa-exclamation-circle" style="font-size:32px; color:#ef4444; opacity:0.2;"></i>
            </div>
          </div>
        </div>

        <!-- Task Filters -->
        <div class="task-filter">
          <button class="filter-btn active" onclick="filterTasks('all')">All Tasks</button>
          <button class="filter-btn" onclick="filterTasks('todo')">To Do</button>
          <button class="filter-btn" onclick="filterTasks('in-progress')">In Progress</button>
          <button class="filter-btn" onclick="filterTasks('completed')">Completed</button>
          <button class="filter-btn" onclick="filterTasks('high')">High Priority</button>
        </div>

        <!-- Tasks Grid -->
        <div class="tasks-grid" id="tasksGrid">
          <!-- Tasks will be loaded here -->
        </div>
      </section>
    </main>
  </div>

  <!-- Create/Edit Task Modal -->
  <div class="modal-overlay" id="taskModal">
    <div class="modal-content">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <h3 style="margin:0; font-size:20px; font-weight:700;" id="modalTitle">Create New Task</h3>
        <button onclick="closeTaskModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#94a3b8;">&times;</button>
      </div>
      
      <form id="taskForm" onsubmit="saveTask(event)">
        <input type="hidden" id="taskId" name="taskId">
        
        <div class="form-group">
          <label>Task Title</label>
          <input type="text" id="taskTitle" name="title" required placeholder="Enter task title">
        </div>
        
        <div class="form-group">
          <label>Description</label>
          <textarea id="taskDescription" name="description" placeholder="Enter task description"></textarea>
        </div>
        
        <div class="form-group">
          <label>Priority</label>
          <select id="taskPriority" name="priority">
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Status</label>
          <select id="taskStatus" name="status">
            <option value="todo">To Do</option>
            <option value="in-progress">In Progress</option>
            <option value="completed">Completed</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Due Date</label>
          <input type="date" id="taskDueDate" name="dueDate">
        </div>
        
        <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:24px;">
          <button type="button" onclick="closeTaskModal()" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary">Save Task</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    let tasks = [];
    let currentFilter = 'all';

    // Load tasks from database
    async function initializeTasks() {
      await loadTasks();
    }
    
    async function loadTasks() {
      try {
        const res = await fetch(`staff_get_tasks.php?filter=${currentFilter}`);
        const data = await res.json();
        
        if (!data.success) {
          showToast(data.message || 'Failed to load tasks', 'error');
          return;
        }
        
        tasks = data.tasks.map(t => ({
          id: t.task_id,
          title: t.title,
          description: t.description || '',
          priority: t.priority,
          status: t.status,
          dueDate: t.due_date,
          createdAt: t.created_at
        }));
        
        renderTasks();
        updateStats();
      } catch (err) {
        console.error('Error loading tasks:', err);
        showToast('Failed to load tasks', 'error');
      }
    }

    function renderTasks() {
      const grid = document.getElementById('tasksGrid');
      let filteredTasks = tasks;
      
      if (currentFilter !== 'all') {
        if (currentFilter === 'high') {
          filteredTasks = tasks.filter(t => t.priority === 'high');
        } else {
          filteredTasks = tasks.filter(t => t.status === currentFilter);
        }
      }
      
      if (filteredTasks.length === 0) {
        grid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:40px; color:#94a3b8;"><i class="fas fa-tasks" style="font-size:48px; margin-bottom:16px; display:block;"></i><p>No tasks found</p></div>';
        return;
      }
      
      grid.innerHTML = filteredTasks.map(task => `
        <div class="task-card priority-${task.priority} ${task.status === 'completed' ? 'completed' : ''}" onclick="editTask(${task.id})">
          <div class="task-header">
            <div class="task-title">${task.title}</div>
            <span class="priority-badge priority-${task.priority}">${task.priority}</span>
          </div>
          
          <div class="task-description">${task.description}</div>
          
          <div class="task-meta">
            <div class="task-meta-item">
              <i class="fas fa-calendar"></i>
              <span>${formatDate(task.dueDate)}</span>
            </div>
            <div class="task-meta-item">
              <i class="fas fa-circle" style="color:${getStatusColor(task.status)}"></i>
              <span>${formatStatus(task.status)}</span>
            </div>
          </div>
          
          <div class="task-actions" onclick="event.stopPropagation()">
            ${task.status !== 'completed' ? `<button onclick="completeTask(${task.id})" class="btn-action btn-approve" title="Mark as complete"><i class="fas fa-check"></i></button>` : ''}
            <button onclick="editTask(${task.id})" class="btn-action btn-view" title="Edit"><i class="fas fa-edit"></i></button>
            <button onclick="deleteTask(${task.id})" class="btn-action btn-cancel" title="Delete"><i class="fas fa-trash"></i></button>
          </div>
        </div>
      `).join('');
    }

    function updateStats() {
      document.getElementById('totalTasks').textContent = tasks.length;
      document.getElementById('inProgressTasks').textContent = tasks.filter(t => t.status === 'in-progress').length;
      document.getElementById('completedTasks').textContent = tasks.filter(t => t.status === 'completed').length;
      document.getElementById('highPriorityTasks').textContent = tasks.filter(t => t.priority === 'high').length;
    }

    async function filterTasks(filter) {
      currentFilter = filter;
      document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
      await loadTasks();
    }

    function openCreateTask() {
      document.getElementById('modalTitle').textContent = 'Create New Task';
      document.getElementById('taskForm').reset();
      document.getElementById('taskId').value = '';
      document.getElementById('taskModal').style.display = 'flex';
    }

    function closeTaskModal() {
      document.getElementById('taskModal').style.display = 'none';
    }

    function editTask(id) {
      const task = tasks.find(t => t.id === id);
      if (!task) return;
      
      document.getElementById('modalTitle').textContent = 'Edit Task';
      document.getElementById('taskId').value = task.id;
      document.getElementById('taskTitle').value = task.title;
      document.getElementById('taskDescription').value = task.description;
      document.getElementById('taskPriority').value = task.priority;
      document.getElementById('taskStatus').value = task.status;
      document.getElementById('taskDueDate').value = task.dueDate;
      document.getElementById('taskModal').style.display = 'flex';
    }

    async function saveTask(e) {
      e.preventDefault();
      const formData = new FormData(e.target);
      const id = formData.get('taskId');
      
      formData.append('action', id ? 'update' : 'create');
      
      try {
        const res = await fetch('staff_task_actions.php', {
          method: 'POST',
          body: formData
        });
        
        const data = await res.json();
        
        if (!data.success) {
          showToast(data.message || 'Failed to save task', 'error');
          return;
        }
        
        showToast(data.message || 'Task saved successfully!', 'success');
        closeTaskModal();
        await loadTasks();
      } catch (err) {
        console.error('Error saving task:', err);
        showToast('Failed to save task', 'error');
      }
    }

    async function completeTask(id) {
      const formData = new FormData();
      formData.append('action', 'complete');
      formData.append('taskId', id);
      
      try {
        const res = await fetch('staff_task_actions.php', {
          method: 'POST',
          body: formData
        });
        
        const data = await res.json();
        
        if (!data.success) {
          showToast(data.message || 'Failed to complete task', 'error');
          return;
        }
        
        showToast('Task marked as completed!', 'success');
        await loadTasks();
      } catch (err) {
        console.error('Error completing task:', err);
        showToast('Failed to complete task', 'error');
      }
    }

    async function deleteTask(id) {
      if (!confirm('Are you sure you want to delete this task?')) return;
      
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('taskId', id);
      
      try {
        const res = await fetch('staff_task_actions.php', {
          method: 'POST',
          body: formData
        });
        
        const data = await res.json();
        
        if (!data.success) {
          showToast(data.message || 'Failed to delete task', 'error');
          return;
        }
        
        showToast('Task deleted successfully!', 'success');
        await loadTasks();
      } catch (err) {
        console.error('Error deleting task:', err);
        showToast('Failed to delete task', 'error');
      }
    }

    function formatDate(dateString) {
      const date = new Date(dateString);
      const today = new Date();
      const diff = Math.floor((date - today) / (1000 * 60 * 60 * 24));
      
      if (diff === 0) return 'Today';
      if (diff === 1) return 'Tomorrow';
      if (diff === -1) return 'Yesterday';
      return date.toLocaleDateString();
    }

    function formatStatus(status) {
      return status.replace('-', ' ').split(' ').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
      ).join(' ');
    }

    function getStatusColor(status) {
      const colors = {
        'todo': '#94a3b8',
        'in-progress': '#f59e0b',
        'completed': '#10b981'
      };
      return colors[status] || '#94a3b8';
    }

    function showToast(message, type = 'info') {
      const colors = {
        success: '#10b981',
        error: '#ef4444',
        info: '#3b82f6',
        warning: '#f59e0b'
      };
      
      const toast = document.createElement('div');
      toast.style.cssText = `
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: ${colors[type]};
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        z-index: 10000;
        font-weight: 500;
      `;
      toast.textContent = message;
      document.body.appendChild(toast);
      
      setTimeout(() => toast.remove(), 3000);
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initializeTasks);
  </script>
  <script src="../js/admin-script.js"></script>
</body>
</html>
