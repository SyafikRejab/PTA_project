<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Planner</title>
  <link rel="stylesheet" href="plannerDisp.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

<header class="header">
    <div class="logo">
      <img src="logo-placeholder.png" alt="EduTrack logo" />
      <h1>EduTrack</h1>
    </div>
    <nav class="nav">
      <a href="UserHome.php">HOME</a>
      <a href="Calculator.php">CALCULATOR</a>
      <a href="ConfessionDisp.php">CONFESSION</a>
      <a href="Resources.php">RESOURCES</a>
      <a href= "profile.php"><i class='bx bx-user-circle'></i></a>
    </nav>
  </header>

<div class="calendar-wrapper">
  <button id="prevBtn" class="nav-btn">❮</button>
  <div class="calendar-slider" id="calendarSlider"></div>
  <button id="nextBtn" class="nav-btn">❯</button>
</div>

<script>
const monthNames = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December"
];

const calendarSlider = document.getElementById('calendarSlider');
let currentMonthOffset = 0;
let tasksByDate = {};

function createCalendar(year, month) {
  const monthContainer = document.createElement('div');
  monthContainer.className = 'calendar-month';

  const header = document.createElement('div');
  header.className = 'calendar-header';
  header.innerHTML = `<h2>${monthNames[month]} ${year}</h2>`;
  monthContainer.appendChild(header);

  const dayLabels = document.createElement('div');
dayLabels.className = 'day-labels';
['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
  const dayDiv = document.createElement('div');
  dayDiv.textContent = day;
  dayLabels.appendChild(dayDiv);
});
monthContainer.appendChild(dayLabels);


  const grid = document.createElement('div');
  grid.className = 'calendar-grid';

  const firstDay = new Date(year, month, 1).getDay();
  const daysInMonth = new Date(year, month + 1, 0).getDate();

  for (let i = 0; i < firstDay; i++) {
    const emptyCell = document.createElement('div');
    emptyCell.className = 'date-cell';
    grid.appendChild(emptyCell);
  }

  for (let day = 1; day <= daysInMonth; day++) {
    const cell = document.createElement('div');
    cell.className = 'date-cell';
    const dateKey = `${year}-${(month+1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
    cell.innerHTML = `<strong>${day}</strong>`;

    // Add tasks preview
    if (tasksByDate[dateKey]) {
      tasksByDate[dateKey].forEach(({ id, content }) => {
  const taskEl = document.createElement('div');
  taskEl.className = 'task';
  taskEl.innerText = content;
  cell.appendChild(taskEl);
});

    }

    cell.addEventListener('click', () => {
  const isExpanded = cell.classList.contains('expand');
  document.querySelectorAll('.date-cell.expand').forEach(c => {
    c.classList.remove('expand');
    const detail = c.querySelector('.task-details');
    if (detail) detail.remove();
  });

  if (!isExpanded) {
    cell.classList.add('expand');
    const detail = document.createElement('div');
    detail.className = 'task-details';
    detail.innerHTML = `
      <strong>Details:</strong><br>
      ${
        tasksByDate[dateKey]
          ? tasksByDate[dateKey].map(task => `
              ${task.content} 
              <button class="delete-btn" data-id="${task.id}">🗑</button>
            `).join('<br>')
          : 'No tasks yet.'
      }

      <br><br>
      <a href="PlannerInput.php?date=${dateKey}" class="add-task-button">+ Add Task</a>
    `;
    cell.appendChild(detail);

    // Attach delete button listeners here
    detail.querySelectorAll('.delete-btn').forEach(btn => {
  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const taskId = btn.dataset.id;

    fetch('delete_task.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: taskId })
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        fetch('get_task.php')
          .then(r => r.json())
          .then(data => {
            tasksByDate = data;
            renderCalendars(currentMonthOffset);
          });
      } else {
        alert("Delete failed: " + (res.message || 'Unknown error'));
      }
    });
  });
});

  }
});


    grid.appendChild(cell);
  }

  monthContainer.appendChild(grid);
  return monthContainer;
}

function renderCalendars(offset = 0) {
  calendarSlider.innerHTML = '';
  const today = new Date();
  const baseMonth = new Date(today.getFullYear(), today.getMonth() + offset, 1);
  calendarSlider.appendChild(createCalendar(baseMonth.getFullYear(), baseMonth.getMonth()));
}



document.getElementById('prevBtn').addEventListener('click', () => {
  currentMonthOffset--;
  renderCalendars(currentMonthOffset);
});

document.getElementById('nextBtn').addEventListener('click', () => {
  currentMonthOffset++;
  renderCalendars(currentMonthOffset);
});

// Load tasks then render
fetch('get_task.php')
  .then(res => res.json())
  .then(data => {
    tasksByDate = data;
    console.log("Loaded tasks:", tasksByDate);
    renderCalendars(currentMonthOffset);
  });

</script>

</body>
</html>
