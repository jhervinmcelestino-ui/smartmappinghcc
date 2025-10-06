<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: login.html');
    exit;
}
require 'db.php';

$username = htmlspecialchars($_SESSION['username']);
$loginTime = $_SESSION['login_time'] ?? date("F j, Y - g:i A");
$organization = $_SESSION['organization'] ?? '';

// Fetch ALL events, including event_time, for the calendar
$stmt = $pdo->prepare("
    SELECT title, organization, venue, event_date, event_time, status
    FROM events
    ORDER BY event_date DESC
");
$stmt->execute();
$allEvents = $stmt->fetchAll();

// Fetch only the next 5 upcoming events, including time, for the table
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT title, organization, venue, event_date, event_time, status
    FROM events
    WHERE event_date >= ?
    ORDER BY event_date
    LIMIT 5
");
$stmt->execute([$today]);
$upcomingForTable = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Mapping – Student Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <script>if(localStorage.getItem('theme')==='dark'){document.documentElement.classList.add('dark-mode');}</script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-body: #f0f2f6;
            --bg-sidebar: #ffffff;
            --bg-navbar: #0d6efd;
            --bg-card: #ffffff;
            --text-main: #212529;
            --text-muted: #6c757d;
            --text-invert: #ffffff;
            --border-color: #dee2e6;
            --hover-link: #e3edff;
        }
        .dark-mode {
            --bg-body: #1e2430;
            --bg-sidebar: #1b2230;
            --bg-navbar: #111827;
            --bg-card: #2c3344;
            --text-main: #f8f9fa;
            --text-muted: #a5b0c2;
            --text-invert: #f1f5f9;
            --border-color: #394151;
            --hover-link: #2c3a50;
        }

        body {
            background: var(--bg-body);
            color: var(--text-main);
            font-family: "Segoe UI", sans-serif;
        }

        .navbar {
            background: var(--bg-navbar);
            color: var(--text-invert);
        }

        .navbar-brand, .dark-toggle {
            color: var(--text-invert) !important;
        }

        .dark-toggle {
            background: transparent;
            border: none;
            font-size: 20px;
        }

        .offcanvas {
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border-color);
            width: 260px;
        }

        .offcanvas-header {
            border-bottom: 1px solid var(--border-color);
        }

        .offcanvas .nav-link {
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            border-radius: 10px;
            transition: 0.2s;
        }

        .offcanvas .nav-link i {
            width: 20px;
            text-align: center;
            color: #0d6efd;
        }

        .offcanvas .nav-link:hover {
            background: var(--hover-link);
            transform: translateX(5px);
        }

        .offcanvas .nav-link.active {
            background: #dceeff;
            font-weight: 600;
            border-left: 4px solid #0d6efd;
        }

        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            color: var(--text-muted);
            padding: 15px 18px 5px;
        }

        .dark-mode .offcanvas {
            background-color: #1b2230;
            color: var(--text-invert);
        }

        .dark-mode .offcanvas .nav-link {
            color: #d9e2f1;
        }

        .dark-mode .offcanvas .nav-link:hover {
            background-color: #324565;
            color: white;
        }

        .dark-mode .offcanvas .nav-link.active {
            background-color: #3a4f6c;
            border-left: 4px solid #0d6efd;
            font-weight: 600;
        }

        .dark-mode .section-title {
            color: #97a3b8;
            border-top: 1px solid #2f3847;
        }

        .card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }

        .card h4, .card h5, .card p {
            color: var(--text-main);
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .dark-mode .text-muted {
            color: #a5b0c2 !important;
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: var(--hover-link);
            color: var(--text-main);
            font-weight: 600;
            font-size: 15px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody td {
            background: var(--bg-card);
            color: var(--text-main);
            padding: 14px 16px;
            font-size: 14px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.01);
        }

        .table-striped tbody tr:hover {
            background-color: var(--hover-link);
            transition: background 0.2s ease-in-out;
        }

        .dark-mode .table thead th {
            background-color: #324565;
            color: var(--text-invert);
            border-bottom: 1px solid #46556e;
        }

        .dark-mode .table tbody td {
            background-color: #2c3344;
            color: var(--text-invert);
            border-bottom: 1px solid #3a4459;
        }

        .dark-mode .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(255, 255, 255, 0.03);
        }

        .dark-mode .table-striped tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        /* --- Year Grid Design --- */
        #yearGrid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            min-height: 700px;
            background: var(--bg-body);
            z-index: 2;
            padding: 22px;
        }
        
        #yearGrid .card {
            min-height: 340px;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(0,0,0,.07);
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            height: 100%;
        }
        #yearGrid .card-header {
            background: #0d6efd;
            color: #fff;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            font-size: 15px;
            padding: 8px 0;
        }
        .dark-mode #yearGrid .card-header {
            background: #0848a6;
        }
        #yearGrid .card-body {
            padding: 8px 6px 2px 6px;
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #yearGrid .table {
            margin-bottom: 0;
            font-size: 12px;
            table-layout: fixed;
            width: 100%;
            height: 100%;
            background: transparent;
            box-shadow: none;
        }
        #yearGrid th, #yearGrid td {
            text-align: center;
            padding: 2px 0;
            height: 32px;
            vertical-align: middle;
            width: 14.2%;
            overflow: hidden;
            border: none !important;
        }
        #yearGrid td {
            vertical-align: top;
            text-align: center;
            min-height: 32px;
            height: 32px;
        }
        #yearGrid td span {
            font-weight: 600;
            font-size: 12px;
            display: block;
            margin-bottom: 2px;
        }
        #yearGrid .badge {
            font-size: 10px;
            margin-top: 2px;
            margin-bottom: 1px;
            white-space: nowrap;
            max-width: 90px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }
        #yearGrid .card .table {
            height: 100%;
        }
        @media (min-width: 992px) {
            #yearGrid .row.g-3 {
                display: flex;
                flex-wrap: wrap;
            }
            #yearGrid .col-lg-3 {
                display: flex;
                flex-direction: column;
                height: 100%;
            }
        }
        @media (max-width: 991px) {
            #yearGrid .col-lg-3 { flex: 0 0 50%; max-width: 50%; }
        }
        @media (max-width: 767px) {
            #yearGrid .col-md-4, #yearGrid .col-lg-3 { flex: 0 0 100%; max-width: 100%; }
        }

        /* --- New CSS for Mobile View Fix --- */
        #calendarWrap {
            position: relative;
        }
        .calendar-header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            padding: 0 1rem;
        }
        .calendar-nav-buttons {
            display: flex;
            align-items: center;
            flex-grow: 1;
            margin-bottom: 10px;
        }
        #calendar-title {
            flex-grow: 1;
            text-align: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .fc-toolbar-chunk {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .fc-toolbar {
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-between;
        }

        .fc-toolbar-title {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .fc-button {
            background-color: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
            border-radius: 8px !important;
            box-shadow: 0 2px 5px rgba(0,0,0,.05) !important;
            transition: all 0.2s ease-in-out;
        }
        .fc-button:hover {
            background-color: var(--hover-link) !important;
        }
        .fc-button-primary:disabled {
            background-color: #e9ecef !important;
            border-color: #e9ecef !important;
            color: #6c757d !important;
            cursor: not-allowed;
        }
        
        .fc-dayGridMonth-button, .fc-timeGridWeek-button, .fc-timeGridDay-button, .fc-listMonth-button {
            display: none !important;
        }

        .fc-day-other {
            color: var(--text-muted) !important;
            opacity: 0.7;
        }
        .fc-col-header-cell {
            color: var(--text-muted) !important;
            font-weight: 600 !important;
        }
        .fc-daygrid-day-number {
            font-weight: 600;
        }
        .dark-mode .fc-button {
            background-color: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            color: var(--text-invert) !important;
        }
        .dark-mode .fc-button:hover {
            background-color: var(--hover-link) !important;
            color: var(--text-main) !important;
        }
        .dark-mode .fc-button-primary:disabled {
            background-color: #2c3344 !important;
            border-color: #394151 !important;
            color: #6c757d !important;
        }
        
        /* New CSS to fix dark mode event visibility */
        .fc-event {
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            padding: 2px 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #fff !important;
        }
        
        .dark-mode .fc-event {
            color: var(--text-main) !important;
            border-color: transparent !important;
        }

        #yearGrid .year-controls-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        #yearGrid .year-controls {
            display: flex;
            align-items: center;
        }
        @media (max-width: 767px) {
            #yearGrid .year-controls-wrapper {
                flex-direction: column;
                align-items: center;
            }
            #calendarViewDropdown {
                order: 1;
                margin-bottom: 1rem;
            }
            #yearGrid .year-controls {
                order: 2;
            }
        }
    </style>
</head>
<body>

<nav class="navbar px-3 fixed-top">
    <button class="btn btn-outline-light me-2" data-bs-toggle="offcanvas" data-bs-target="#sidebar">
        <i class="fas fa-bars"></i>
    </button>
    <span class="navbar-brand fw-bold">📍 Smart Mapping – Student Dashboard</span>
    <button class="ms-auto dark-toggle" id="modeToggle"><i class="fas fa-moon"></i></button>
</nav>

<div class="offcanvas offcanvas-start" id="sidebar">
    <div class="offcanvas-header">
        <h5 class="mb-0">Menu</h5>
        <button class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="nav flex-column">
            <li class="section-title">Main</li>
            <li><a class="nav-link active" href="#"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
            <li class="section-title">Student Panel</li>
            <li><a class="nav-link" href="settings.php"><i class="fas fa-gear"></i>Settings</a></li>
            <li class="section-title">Session</li>
            <li><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
        </ul>
    </div>
</div>

<div class="container py-5 mt-4">

    <div class="card p-4 mb-4">
        <h4 class="mb-2">👋 Welcome, <strong><?= $username ?></strong>!</h4>
        <p class="mb-0 text-muted">Logged in at: <em><?= $loginTime ?></em></p>
    </div>

   

    <h5 class="mb-3 mt-4"><i class="fas fa-calendar-alt text-primary me-2"></i>Event Calendar</h5>
    <div id="calendarWrap" style="position:relative;">
        <div id="calendar-controls" class="calendar-header-container">
            <div class="calendar-nav-buttons">
                <button id="prevBtn" class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-chevron-left"></i></button>
                <button id="nextBtn" class="btn btn-outline-primary btn-sm"><i class="fas fa-chevron-right"></i></button>
                <span id="calendar-title" class="ms-3 me-auto"></span>
            </div>
            <select id="calendarViewDropdown" class="form-select w-auto d-inline-block shadow-sm">
                <option value="dayGridMonth" selected>Month</option>
                <option value="timeGridWeek">Week</option>
                <option value="timeGridDay">Day</option>
                <option value="listMonth">List</option>
                <option value="customYear">Year</option>
            </select>
        </div>
        <div id="calendar" style="background: var(--bg-card); border-radius: 18px; box-shadow: 0 6px 18px rgba(0,0,0,.10); padding: 22px; min-height: 700px;"></div>
        
        <div id="yearGrid" class="d-none" style="position:relative;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="year-controls">
                    <button id="prevYearBtn" class="btn btn-outline-primary btn-sm me-2"><i class="fas fa-chevron-left"></i></button>
                    <span id="yearLabel" class="fw-bold fs-5"></span>
                    <button id="nextYearBtn" class="btn btn-outline-primary btn-sm ms-2"><i class="fas fa-chevron-right"></i></button>
                </div>
                <select id="yearViewDropdown" class="form-select w-auto d-inline-block shadow-sm">
                    <option value="dayGridMonth">Month</option>
                    <option value="timeGridWeek">Week</option>
                    <option value="timeGridDay">Day</option>
                    <option value="listMonth">List</option>
                    <option value="customYear" selected>Year</option>
                </select>
            </div>
            <div class="row g-3" id="yearGridMonths"></div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
    const tgl = document.getElementById('modeToggle');
    const icon = tgl.querySelector('i');

    if(localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-mode');
        icon.classList.replace('fa-moon', 'fa-sun');
    }

    tgl.addEventListener('click', () => {
        document.documentElement.classList.toggle('dark-mode');
        const dark = document.documentElement.classList.contains('dark-mode');
        localStorage.setItem('theme', dark ? 'dark' : 'light');
        icon.classList.toggle('fa-moon', !dark);
        icon.classList.toggle('fa-sun', dark);
    });

    const calendarEvents = [
        <?php foreach($allEvents as $ev): ?>
            <?php if($ev['status'] === 'Approved'): ?>
            {
                title: <?= json_encode($ev['title']) ?>,
                start: <?= json_encode($ev['event_date'] . 'T' . $ev['event_time']) ?>,
                color: '#198754',
                textColor: '#fff', 
                extendedProps: {
                    org: <?= json_encode($ev['organization']) ?>,
                    venue: <?= json_encode($ev['venue']) ?>,
                    time: <?= json_encode($ev['event_time']) ?>
                }
            },
            <?php endif; ?>
        <?php endforeach; ?>
    ];

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var yearGridEl = document.getElementById('yearGrid');
        var calendarTitleEl = document.getElementById('calendar-title');
        var prevBtn = document.getElementById('prevBtn');
        var nextBtn = document.getElementById('nextBtn');
        var currentYear = new Date().getFullYear();
        var prevYearBtn = document.getElementById('prevYearBtn');
        var nextYearBtn = document.getElementById('nextYearBtn');
        const calendarViewDropdown = document.getElementById('calendarViewDropdown');
        const yearViewDropdown = document.getElementById('yearViewDropdown');

        const allEvents = <?php echo json_encode($allEvents); ?>;

        function renderYearGrid(year) {
            document.getElementById('yearLabel').textContent = year;
            const monthsContainer = document.getElementById('yearGridMonths');
            monthsContainer.innerHTML = '';
            const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

            for (let m = 1; m <= 12; m++) {
                const monthName = monthNames[m-1];
                const daysInMonth = new Date(year, m, 0).getDate();
                const firstDayOfWeek = new Date(year, m-1, 1).getDay(); // 0 (Sun) - 6 (Sat)
                let table = `<div class="col-lg-3 col-md-4 col-sm-6 d-flex">
                    <div class="card w-100">
                        <div class="card-header text-center fw-bold">${monthName} ${year}</div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-0" style="font-size:12px;table-layout:fixed;">
                                <thead>
                                    <tr>
                                        <th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th>
                                    </tr>
                                </thead>
                                <tbody>`;
                let day = 1;
                while (day <= daysInMonth) {
                    table += '<tr>';
                    for (let w = 0; w < 7; w++) {
                        if (day === 1 && w < firstDayOfWeek) {
                            table += '<td></td>';
                        } else if (day <= daysInMonth) {
                            let eventHtml = '';
                            allEvents.forEach(ev => {
                                let evDate = new Date(ev.event_date);
                                if (evDate.getFullYear() === year && (evDate.getMonth()+1) === m && evDate.getDate() === day && ev.status === 'Approved') {
                                    eventHtml += `<span class="badge d-block" style="background:#198754;color:#fff;">${ev.title}</span>`;
                                }
                            });
                            table += `<td style="height:28px;vertical-align:top;"><span style="font-weight:600;">${day}</span>${eventHtml}</td>`;
                            day++;
                        } else {
                            table += '<td></td>';
                        }
                    }
                    table += '</tr>';
                }
                table += '</tbody></table></div></div></div>';
                monthsContainer.innerHTML += table;
            }
        }

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 700,
            events: calendarEvents,
            headerToolbar: {
                left: '',
                center: '',
                right: ''
            },
            eventClick: function(info) {
                // Formatting the time for the modal
                const eventTime = new Date(`2000-01-01T${info.event.extendedProps.time}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });

                let modalHtml = `
                    <div class="modal fade" id="eventModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content" style="border-radius:16px;">
                                <div class="modal-header" style="background:${info.event.color};color:#fff;border-top-left-radius:16px;border-top-right-radius:16px;">
                                    <h5 class="modal-title">${info.event.title}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p><strong>Title:</strong> ${info.event.title}</p>
                                    <p><strong>Date:</strong> ${info.event.start.toLocaleDateString()}</p>
                                    <p><strong>Time:</strong> ${eventTime}</p>
                                    <p><strong>Organization:</strong> ${info.event.extendedProps.org}</p>
                                    <p><strong>Venue:</strong> ${info.event.extendedProps.venue}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                let oldModal = document.getElementById('eventModal');
                if (oldModal) oldModal.remove();
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                let modal = new bootstrap.Modal(document.getElementById('eventModal'));
                modal.show();
            },
            dayMaxEvents: 3,
            dayMaxEventRows: true,
            themeSystem: 'bootstrap5'
        });
        
        function updateCalendarTitle(calendar) {
            calendarTitleEl.textContent = calendar.view.title;
        }

        // Handle dropdown change (Main Dropdown)
        calendarViewDropdown.addEventListener('change', function() {
            const view = this.value;
            if (view === 'customYear') {
                calendarEl.classList.add('d-none');
                document.getElementById('calendar-controls').classList.add('d-none');
                yearGridEl.classList.remove('d-none');
                renderYearGrid(currentYear);
            } else {
                yearGridEl.classList.add('d-none');
                calendarEl.classList.remove('d-none');
                document.getElementById('calendar-controls').classList.remove('d-none');
                calendar.changeView(view);
                updateCalendarTitle(calendar);
            }
        });

        // Handle dropdown change (Year View Dropdown)
        yearViewDropdown.addEventListener('change', function() {
            const view = this.value;
            if (view === 'customYear') {
                // Do nothing, already in year view
            } else {
                yearGridEl.classList.add('d-none');
                calendarEl.classList.remove('d-none');
                document.getElementById('calendar-controls').classList.remove('d-none');
                calendar.changeView(view);
                updateCalendarTitle(calendar);
            }
        });

        // Handle prev/next buttons for FullCalendar
        prevBtn.addEventListener('click', () => {
            calendar.prev();
            updateCalendarTitle(calendar);
        });
        nextBtn.addEventListener('click', () => {
            calendar.next();
            updateCalendarTitle(calendar);
        });

        // Handle next/prev year buttons
        prevYearBtn.onclick = function() {
            currentYear--;
            renderYearGrid(currentYear);
        };

        nextYearBtn.onclick = function() {
            currentYear++;
            renderYearGrid(currentYear);
        };
        
        // Initial setup
        calendar.render();
        yearGridEl.classList.add('d-none');
        updateCalendarTitle(calendar);
    });
</script>
</body>
</html>