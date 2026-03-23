<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Colleges</title>

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

    
  </style>
</head>

<body>
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
      <main class="content-area mt-4"> <!-- Add margin-top here -->
        
      <div class="container">

<h2>Add Exam</h2>
<form action="{{ route('exams.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label>Exam Name</label>
        <input type="text" name="exam_name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Year</label>
        <input type="number" name="year" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="Y">ACTIVE</option>
            <option value="N">INACTIVE</option>
        </select>
    </div>
    <button class="btn btn-primary">Add Exam</button>
</form>

<hr>

<h2>Exam List</h2>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Exam Name</th>
            <th>Year</th>
            <th>Status</th>
            <th width="250">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($exams as $exam)
        <tr id="row-{{ $exam->examid }}">
            <td>{{ $exam->examid }}</td>
            <td class="view">{{ $exam->exam_name }}</td>
            <td class="view">{{ $exam->year }}</td>
            <td class="view">
                <span class="badge {{ $exam->status == 'Y' ? 'bg-success' : 'bg-danger' }}">
                    {{ $exam->status == 'Y' ? 'ACTIVE' : 'INACTIVE' }}
                </span>
            </td>
            <td>
                <button class="btn btn-warning btn-sm" onclick="editRow({{ $exam->examid }})">Update</button>

                <form action="{{ route('exams.toggle', $exam->examid) }}" method="POST" style="display:inline">
                    @csrf
                    <button class="btn btn-secondary btn-sm">Toggle</button>
                </form>
            </td>
        </tr>

        <!-- Hidden edit row -->
        <tr id="edit-row-{{ $exam->examid }}" style="display:none;">
            <form action="{{ route('exams.update', $exam->examid) }}" method="POST">
                @csrf
                <td>{{ $exam->examid }}</td>
                <td><input type="text" name="exam_name" class="form-control" value="{{ $exam->exam_name }}" required></td>
                <td><input type="number" name="year" class="form-control" value="{{ $exam->year }}" required></td>
                <td>
                    <select name="status" class="form-control">
                        <option value="Y" {{ $exam->status=='Y'?'selected':'' }}>ACTIVE</option>
                        <option value="N" {{ $exam->status=='N'?'selected':'' }}>INACTIVE</option>
                    </select>
                </td>
                <td>
                    <button class="btn btn-success btn-sm">Save</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="cancelEdit({{ $exam->examid }})">Cancel</button>
                </td>
            </form>
        </tr>

        @endforeach
    </tbody>
</table>
</div>

        <footer class="footer mt-auto py-3 bg-light text-center text-muted border-top">
    @include('layout.footer')
  </footer>
      </main>

    </div> <!-- main-container -->
  </div> <!-- page-wrapper -->

  <!-- Scripts -->
  <script src="{{ asset('assets/libs/jquery/dist/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/sidebarmenu.js') }}"></script>
  <script src="{{ asset('assets/js/app.min.js') }}"></script>
  <script src="{{ asset('assets/libs/apexcharts/dist/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets/libs/simplebar/dist/simplebar.js') }}"></script>
  <script src="{{ asset('assets/js/dashboard.js') }}"></script>
  <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>

  <script>
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


    function editRow(id) {
    document.getElementById('row-' + id).style.display = 'none';
    document.getElementById('edit-row-' + id).style.display = '';
}

function cancelEdit(id) {
    document.getElementById('edit-row-' + id).style.display = 'none';
    document.getElementById('row-' + id).style.display = '';
}
  </script>
</body>


</html>
