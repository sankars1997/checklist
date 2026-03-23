<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Checklist</title>

  <link rel="stylesheet" href="{{ asset('assets/css/styles.min.css') }}" />
  
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" />

  <style>
    /* Ensure base layout takes full height */
html, body {
  height: 100%;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
}

/* Page wrapper stretches full height */
.page-wrapper {
  padding-top: 15px;
  display: flex;
  flex-direction: column;
}

/* Main container holds sidebar + content */
.main-container {
  flex: 1;
  display: flex;
  min-height: 0;
}

/* ---------------- Sidebar Styles ---------------- */

.sidebar-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 270px;
  height: 100vh;
  background-color: #000; /* Black background */
  border-right: 1px solid #333;
  display: flex;
  flex-direction: column;
  overflow: hidden; /* Prevent sidebar scroll */
  z-index: 999;
}

/* Sidebar Header */
.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12.5px;
  font-size: 1.4rem;
  font-weight: bold;
  background-color: rgb(27, 27, 41);
  color: #fff;
  border-bottom: 1px solid #333;
}

.sidebar-title {
  text-transform: uppercase;
  color: #fff;
}

.sidebar-toggler {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #fff;
  cursor: pointer;
}

/* Sidebar Scrollable Content */
.sidebar-content {
  flex: 1;
  overflow-y: auto;
  padding: 10px 0;
}

/* Sidebar Menu */
.sidebar-menu {
  list-style: none;
  margin: 0;
  padding: 0;
}

.sidebar-item {
  padding: 10px 20px;
  white-space: nowrap;
}

.sidebar-item a {
  display: flex;
  align-items: center;
  text-decoration: none;
  color: #ccc;
  font-size: 0.85rem;
  transition: background 0.2s ease, color 0.2s ease;
  padding: 8px 12px;
  border-radius: 4px;
}

.sidebar-item i {
  margin-right: 10px;
  min-width: 18px;
  color: #ccc;
}

.sidebar-item a:hover {
  background-color: #222;
  color: #fff;
}

.sidebar-item a.active {
  background-color: #333;
  color: #fff;
}

/* ---------------- Shrunk Sidebar ---------------- */

body.mini-sidebar .sidebar-container {
  width: 80px;
}

body.mini-sidebar .sidebar-title,
body.mini-sidebar .sidebar-item span {
  display: none;
}

body.mini-sidebar .sidebar-item a {
  justify-content: center;
}

body.mini-sidebar .content-area {
  margin-left: 80px;
}

/* ---------------- Main Content Area ---------------- */

.content-area {
  flex: 1;
  margin-left: 270px; /* default sidebar width */
  padding: 40px;
  background: #f8f9fa;
  transition: margin-left 0.3s ease;
  display: flex;
  flex-direction: column;
  overflow: auto;
}

/* ---------------- Footer ---------------- */

.footer {
  background-color:rgb(232, 240, 248);
  text-align: center;
  padding: 10px 20px;
  font-size: 1rem;
  border-top: 1px solid #dee2e6;
  margin-top: auto;
}




body {
    font-family: 'Roboto', sans-serif;
    background-color: #f0f4f8;
    color: #333;
}

h4 {
    font-family: 'Montserrat', sans-serif;
    font-size: 28px;
    color: #1976d2;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

label {
    font-weight: 600;
    color: #495057;
}

/* Form Controls */
.form-control, .form-select {
    border-radius: 10px;
    border: 1.5px solid #ced4da;
    padding: 10px 12px;
    font-size: 14px;
    font-weight: 600;
    color: #222;
    transition: all 0.3s;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
}

.form-control:focus, .form-select:focus {
    border-color: #1976d2;
    box-shadow: 0 0 8px rgba(25,118,210,0.3);
}

/* Card Sections */
.card-section {
    background: linear-gradient(145deg, #ffffff, #e9f1ff);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

/* Subitem Rows */
.subitem-row {
    background-color: #ffffff;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: all 0.3s;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.subitem-row b {
    font-weight: 700;
}

/* Inputs in Subitem */
/* Fix select dropdown text overlapping the arrow */
.subitem-input select.form-select {
    padding-right: 2.5rem; /* ensure space for dropdown arrow */
    font-weight: 600;
    font-size: 14px;
    color: #222;
    border-radius: 8px;
    border: 1px solid #ced4da;
    transition: all 0.3s;
}

/* Focus style */
.subitem-input select.form-select:focus {
    border-color: #1976d2;
    box-shadow: 0 0 6px rgba(25,118,210,0.3);
}

/* Optional: make the arrow more visible */
.subitem-input select.form-select::-ms-expand {
    display: none; /* for IE */
}


/* Button */
.btn-custom {
    background: linear-gradient(90deg, #ff9800, #ff5722);
    border: none;
    color: #fff;
    font-weight: 700;
    padding: 10px 25px;
    border-radius: 50px;
    font-size: 16px;
    transition: all 0.3s;
}

.btn-custom:hover {
    background: linear-gradient(90deg, #ff5722, #ff9800);
    transform: scale(1.05);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
/* ---------- Progress ---------- */
.progress {
    height: 30px;
    border-radius: 50px;
    overflow: hidden;
    background-color: #e0e0e0;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
}

.progress-bar-custom {
    background: linear-gradient(90deg, #ff9800, #ff5722); /* same as submit button */
    font-weight: 700;
    color: #fff;
    text-align: center;
    line-height: 30px; /* vertical center text */
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
    transition: width 0.5s, background 0.5s;
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    border-radius: 50px;
}
/* ---------- Section Heading Only ---------- */
.card-section .mb-2 strong {
    font-size: 18px;
    font-weight: 800;
    color: #FF0000; /* bright red color for section_name */
    text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
}
.overall-title {
    font-weight: 800;
    font-size: 22px;
    color: #0d47a1; /* dark blue */
    text-shadow: 2px 2px 5px rgba(0,0,0,0.3);
    margin-bottom: 10px;
}

/* ---------- Progress Container ---------- */
.overall-progress-container {
    height: 30px; /* thicker */
    border-radius: 25px;
    overflow: hidden;
    background-color: #e0e0e0;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
    position: relative;
}

/* ---------- Progress Bar with Wave ---------- */
.overall-progress-bar {
    font-weight: 700;
    font-size: 16px;
    color: #fff;
    text-align: center;
    line-height: 30px; /* vertical center */
    background-color: #0d47a1;
    position: relative;
    overflow: hidden;
    transition: width 0.5s ease-in-out;
}

/* ---------- Wave Animation ---------- */
.overall-title {
    font-weight: 800;
    font-size: 22px;
    color: #0d47a1; /* dark blue */
    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
    margin-bottom: 10px;
}

/* ---------- Progress Container ---------- */
.overall-progress-container {
    height: 30px; /* thicker */
    border-radius: 25px;
    overflow: hidden;
    background-color: #cfd8dc;
    box-shadow: inset 0 2px 5px rgba(0,0,0,0.1);
}

/* ---------- Animated Progress Bar ---------- */
.overall-progress-bar {
    font-weight: 700;
    font-size: 16px;
    color: #fff;
    text-align: center;
    line-height: 30px;
    border-radius: 25px;
    background: linear-gradient(-45deg, #0d47a1, #1976d2, #0d47a1, #1976d2);
    background-size: 400% 100%;
    animation: gradientShift 3s linear infinite;
    transition: width 0.5s ease-in-out;
}

/* ---------- Gradient Animation ---------- */
@keyframes gradientShift {
    0% { background-position: 0% 0%; }
    50% { background-position: 100% 0%; }
    100% { background-position: 0% 0%; }
}




  </style>
</head>

<body>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Page Wrapper -->
  <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
    data-sidebar-position="fixed" data-header-position="fixed">
    @include('layout.topheader')
    <!-- Top Header -->
    </div>
    <!-- Main Container -->
    <div class="main-container">
    
      <!-- Sidebar -->
      @include('layout.nav')
      
      
      <!-- Main Content with Top Gap -->
      <main class="content-area mt-4">
    <div class="body-wrapper-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                <h4 style="color:#1976d2; text-shadow:1px 1px 2px rgba(0,0,0,0.1); font-weight:800;">Checklist Audit</h4>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form method="POST" action="{{ route('checklist.save') }}" id="checklistForm">
    @csrf

    <!-- Employee Info -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <label>Employee Code</label>
            <input class="form-control" value="{{ $employee->EmployeeCd }}" readonly>
            <input type="hidden" name="employee_cd" value="{{ $employee->EmployeeCd }}">
        </div>
        <div class="col-md-4">
            <label>Employee Name</label>
            <input class="form-control" value="{{ $employee->Name }}"  name="Name" readonly>
        </div>
        <div class="col-md-5">
    <label>Programmer Code</label>
    <input 
        class="form-control" 
        name="programmer_cd" 
        placeholder="Enter Programmer Code" 
        id="programmerCode" 
        required
    >
</div>

    </div>

    <!-- Exam and Main Heading -->
    <div class="row mb-4 g-3">
        <div class="col-md-6">
            <label>Exam</label>
            <select class="form-select" id="examSelect" name="exam_id" required>
                <option value="">-- Select Exam --</option>
                @foreach($exams as $exam)
                    <option value="{{ $exam->examid }}">{{ $exam->exam_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label>Main Heading</label>
            <select class="form-select" id="mainHeadingSelect" name="main_id">
                <option value="">-- Select Main Heading --</option>
                @foreach($mainHeadings as $m)
                    <option value="{{ $m->id }}">{{ $m->main_heading }}</option>
                @endforeach
            </select>
            <input type="hidden" name="main_id" id="main_id">
        </div>
    </div>

    <!-- Checklist Sections -->
    <div id="checklistContainer"></div>

    <!-- Overall Progress -->
    <div class="mt-4">
    <h5 style="font-weight:700">Overall Completion</h5>
    <div class="progress">
        <div id="overallProgress" class="progress-bar progress-bar-custom" style="width:0%">0%</div>
    </div>
</div>


    <button type="submit" class="btn btn-custom mt-4" id="submitBtn" disabled>Submit Checklist</button>
    
</form>
</div>

<div id="sectionCompletion" class="mt-4"></div>
              </div>
            </div>
          </div>
      
       
    @include('layout.footer')
  
      </main>

  

  <!-- Scripts -->
  <script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/sidebarmenu.js') }}"></script>
  <script src="{{ asset('assets/js/app.min.js') }}"></script>
  <script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets/libs/simplebar/dist/simplebar.js') }}"></script>
  <script src="{{ asset('assets/js/dashboard.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
  <script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/sidebarmenu.js') }}"></script>
  <script src="{{ asset('assets/js/app.min.js') }}"></script>
 
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mainSelect = document.getElementById('mainHeadingSelect');
    const examSelect = document.getElementById('examSelect');
    const checklistContainer = document.getElementById('checklistContainer');
    const overallProgressBar = document.getElementById('overallProgress');
    const submitBtn = document.getElementById('submitBtn');
    const main_id_input = document.getElementById('main_id');
    const programmerInput = document.getElementById('programmerCode');
    const sectionCompletion = document.getElementById('sectionCompletion');

    let statusColors = {};

    function getStatusPercent(status) {
        switch(status) {
            case 'OK': return 100;
            case 'Pending': return 50;
            case 'Not OK':
            case 'NA':
            default: return 0;
        }
    }

    function updateProgress() {
    let totalItems = 0, totalCompleted = 0;
    const sectionData = {};

    document.querySelectorAll('.subitem-row').forEach(row => {
        const sectionId = row.dataset.sectionId;
        const status = row.querySelector('select')?.value;
        const subitemName = row.querySelector('b')?.innerText ?? 'Subitem';
        const percent = getStatusPercent(status);

        if (!sectionData[sectionId]) {
            const sectionName = row.closest('.card-section').querySelector('strong')?.innerText ?? 'Section';
            sectionData[sectionId] = { sum: 0, count: 0, name: sectionName, subitems: [] };
        }

        sectionData[sectionId].sum += percent;
        sectionData[sectionId].count++;
        sectionData[sectionId].subitems.push({ name: subitemName, status: status });

        totalItems++;
        if (status && status !== '') totalCompleted++;
    });

    // Overall progress
    const overallPercent = totalItems ? Math.round((totalCompleted / totalItems) * 100) : 0;
    overallProgressBar.style.width = overallPercent + '%';
    overallProgressBar.innerText = overallPercent + '%';

    // Section-wise completion
    let html = '<h5 class="overall-title">Section-wise Completion</h5>';
    for (const sectionId in sectionData) {
        const data = sectionData[sectionId];
        const percent = Math.round(data.sum / data.count);

        // Build subitem tooltip HTML with badges
        let tooltipHtml = '<div style="display:flex; flex-direction:column; gap:6px;">';
        data.subitems.forEach(sub => {
            let bgColor = '#1976d2', textColor = '#fff';
            if(sub.status?.toUpperCase() === 'OK') { bgColor = '#4caf50'; textColor = '#fff'; }
            else if(sub.status?.toUpperCase() === 'NOT OK') { bgColor = '#f44336'; textColor = '#fff'; }
            else if(sub.status?.toUpperCase() === 'PENDING') { bgColor = '#ff9800'; textColor = '#fff'; }
            else if(sub.status?.toUpperCase() === 'NA') { bgColor = '#9e9e9e'; textColor = '#fff'; }

            tooltipHtml += `
                <div style="display:flex; justify-content:space-between; align-items:center; padding:6px 10px; border-radius:6px; background:${bgColor}; color:${textColor}; font-weight:600; font-size:14px;">
                    <span>${sub.name}</span>
                    <span>${sub.status || 'Pending'}</span>
                </div>
            `;
        });
        tooltipHtml += '</div>';

        html += `
            <div class="card-section section-hover" 
                 style="padding:14px 18px; margin-bottom:12px; background-color:#f9f9f9; border:1px solid #ddd; border-radius:12px; position:relative; transition: transform 0.2s, box-shadow 0.2s;">
                <div><strong>${data.name}</strong></div>
                <div class="progress mt-2" style="height:22px; border-radius:22px; overflow:hidden;">
                    <div class="progress-bar" style="width:${percent}%; background:linear-gradient(90deg,#1976d2,#0d47a1); color:#fff; font-weight:700; text-align:center;">
                        ${percent}%
                    </div>
                </div>
                <div class="tooltip-subitems" style="
                        display:none;
                        position:absolute;
                        top:110%;
                        left:0;
                        background:#fff;
                        border:1px solid #ccc;
                        padding:12px;
                        border-radius:10px;
                        box-shadow:0 6px 20px rgba(0,0,0,0.15);
                        z-index:10;
                        width:320px;
                        opacity:0;
                        transform: translateY(-8px);
                        transition: opacity 0.3s ease, transform 0.3s ease;
                    ">
                    ${tooltipHtml}
                </div>
            </div>
        `;
    }

    sectionCompletion.innerHTML = html;

    // Hover functionality with smooth animation
    document.querySelectorAll('.section-hover').forEach(section => {
        const tooltip = section.querySelector('.tooltip-subitems');
        section.addEventListener('mouseenter', () => {
            tooltip.style.display = 'block';
            setTimeout(() => {
                tooltip.style.opacity = '1';
                tooltip.style.transform = 'translateY(0)';
            }, 10);
        });
        section.addEventListener('mouseleave', () => {
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'translateY(-8px)';
            setTimeout(() => {
                tooltip.style.display = 'none';
            }, 300);
        });
    });
}

// Status percentage helper
function getStatusPercent(status){
    if(!status || status.trim() === '') return 0; // EMPTY = 0%
    if(status.toUpperCase() === 'OK') return 100;
    if(status.toUpperCase() === 'PENDING') return 50;
    if(status.toUpperCase() === 'NOT OK') return 0;
    if(status.toUpperCase() === 'NA') return 0;
    return 0;
}




    function applyRowColor(row, status) {
        if(status && statusColors[status]){
            const colors = statusColors[status];
            row.style.backgroundColor = colors.bg;
            row.style.border = `2px solid ${colors.border}`;
            row.querySelectorAll('b, input, select, div').forEach(el => {
                el.style.color = colors.text;
                el.style.fontWeight = '700';
            });
        } else {
            row.style.backgroundColor = '#fff';
            row.style.border = '1px solid #ced4da';
            row.querySelectorAll('b, input, select, div').forEach(el => {
                el.style.color = '#222';
            });
        }
    }

    function loadSections() {
        const mainId = mainSelect.value;
        const examId = examSelect.value;
        main_id_input.value = mainId;
        if(!mainId || !examId) return;

        checklistContainer.innerHTML = 'Loading sections...';
        submitBtn.disabled = true;

        fetch("{{ route('checklist.sections') }}", {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
            body: JSON.stringify({main_id: mainId, exam_id: examId})
        })
        .then(res => res.json())
        .then(res => {
            if(res.status !== 'success'){
                checklistContainer.innerHTML = `<div class="text-danger">Failed to load sections.</div>`;
                return;
            }

            statusColors = res.status_colors;
            if(res.programmer_cd){
                programmerInput.value = res.programmer_cd;
            }

            let html = '';
            res.sections.forEach(section => {
                html += `<div class="card-section" data-section-id="${section.id}">
                    <div class="mb-2"><strong>${section.section_name}</strong></div>`;

                section.subitems.forEach(sub => {
                    const readonly = sub.submit_status === 'Y' ? 'disabled' : '';
                    html += `<div class="row mb-2 subitem-row" data-section-id="${section.id}">
                        <div class="col-md-4"><b>${sub.subitem}</b></div>
                        <div class="col-md-4">${sub.description ?? ''}</div>
                        <div class="col-md-2 subitem-input">
                            <select class="form-select subitem-status" name="items[${sub.id}][status]" ${readonly} required>
    <option value="">-Select Status-</option>
    ${Object.keys(statusColors).map(s => `<option value="${s}" ${sub.status===s?'selected':''}>${s}</option>`).join('')}
</select>
                        </div>
                        <div class="col-md-2 subitem-input">
                            <input type="text" class="form-control" name="items[${sub.id}][remarks]" value="${sub.remarks??''}" placeholder="Remarks" ${readonly}>
                        </div>
                        <input type="hidden" name="items[${sub.id}][subitem_id]" value="${sub.id}">
                        <input type="hidden" name="items[${sub.id}][section_id]" value="${section.id}">
                    </div>`;
                });

                html += `</div>`;
            });

            checklistContainer.innerHTML = html;

            document.querySelectorAll('.subitem-row').forEach(row => {
                const select = row.querySelector('select');
                applyRowColor(row, select.value);
                select.addEventListener('change', () => {
                    applyRowColor(row, select.value);
                    updateProgress();
                });
            });

            updateProgress();
            submitBtn.disabled = false;
        })
        .catch(err => {
            console.error(err);
            checklistContainer.innerHTML = `<div class="text-danger">Failed to load sections.</div>`;
        });
    }

    mainSelect.addEventListener('change', loadSections);
    examSelect.addEventListener('change', loadSections);
});



document.getElementById('checklistForm').addEventListener('submit', function(e){
    let valid = true;
    document.querySelectorAll('.subitem-status').forEach(select => {
        if(!select.value){
            valid = false;
            select.focus();
            alert('Please select a status for all subitems.');
            return false;
        }
    });
    if(!valid) e.preventDefault();
});






document.addEventListener('DOMContentLoaded', function () {
      const toggleBtn = document.getElementById('sidebarCollapse');
      const body = document.body;

      if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
          body.classList.toggle('mini-sidebar');
        });
      } else {
        console.warn('#sidebarCollapse not found');
      }
    });
</script>
<script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/sidebarmenu.js') }}"></script>
  <script src="{{ asset('assets/js/app.min.js') }}"></script>
  <script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets/libs/simplebar/dist/simplebar.js') }}"></script>
  <script src="{{ asset('assets/js/dashboard.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
</body>


</html>
