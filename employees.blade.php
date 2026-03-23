<!DOCTYPE html>
<html>
<head>
    <title>Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        #formCard {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {opacity: 0;}
            to {opacity: 1;}
        }

        .form-label {
            font-weight: 500;
        }

        .card-header span {
            font-size: 1.2rem;
        }

        .table-hover tbody tr:hover {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<div class="container mt-5">

    <!-- HEADER -->
   <div class="d-flex justify-content-between mb-3 align-items-center">
    <h3>Employee List</h3>
    <div>
        <button class="btn btn-success me-2" onclick="showForm()">
            <i class="bi bi-plus-circle"></i> Add Employee
        </button>
        <button id="backBtn" class="btn btn-secondary">
            ← Back
        </button>
    </div>
</div>

    <!-- FORM CARD -->
    <div class="card mb-4 shadow" id="formCard">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span id="formTitle">Add Employee</span>
            <button class="btn btn-sm btn-light" onclick="hideForm()">X</button>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('employees.index') }}">
                @csrf
                <input type="hidden" name="hidden_id" id="hidden_id">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="CollegeGroup" class="form-label">College Group</label>
                        <input type="text" name="CollegeGroup" id="CollegeGroup" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="CollegeType" class="form-label">College Type</label>
                        <input type="text" name="CollegeType" id="CollegeType" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="CollegeCode" class="form-label">College Code</label>
                        <input type="text" name="CollegeCode" id="CollegeCode" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="EmployeeCd" class="form-label">Employee Code</label>
                        <input type="text" name="EmployeeCd" id="EmployeeCd" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="Name" class="form-label">Name</label>
                        <input type="text" name="Name" id="Name" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="Desig" class="form-label">Designation</label>
                        <input type="text" name="Desig" id="Desig" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="Password" class="form-label">Password</label>
                        <input type="password" name="Password" id="Password" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="Active" class="form-label">Active</label>
                        <input type="text" name="Active" id="Active" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="EmpType" class="form-label">Employee Type</label>
                        <input type="text" name="EmpType" id="EmpType" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="EmpRole" class="form-label">Role</label>
                        <input type="text" name="EmpRole" id="EmpRole" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label for="masked" class="form-label">Masked</label>
                        <input type="number" name="masked" id="masked" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-4" id="submitBtn">
                    Save
                </button>
            </form>
        </div>
    </div>

    <!-- TABLE -->
    <div class="card shadow">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Employee Code</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th width="120">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($employees as $emp)
                    <tr>
                        <td>{{ $emp->EmployeeCd }}</td>
                        <td>{{ $emp->Name }}</td>
                        <td>{{ $emp->Desig }}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick='editEmployee(@json($emp))'>
                                Edit
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- JS -->
<script>
function showForm() {
    document.getElementById('formCard').style.display = 'block';
    document.getElementById('formTitle').innerText = "Add Employee";
    document.getElementById('submitBtn').innerText = "Save";

    const form = document.querySelector("form");
    form.reset();

    // Set default values for new employee
    document.getElementById('CollegeGroup').value = "C";
    document.getElementById('CollegeType').value = "C";
    document.getElementById('CollegeCode').value = "CEE";
    document.getElementById('Active').value = "Y";
    document.getElementById('EmpType').value = "C";
    document.getElementById('masked').value = 0;

    document.getElementById('hidden_id').value = "";
    document.getElementById('EmployeeCd').readOnly = false;
}

function hideForm() {
    document.getElementById('formCard').style.display = 'none';
}

function editEmployee(emp) {
    showForm();
    document.getElementById('formTitle').innerText = "Edit Employee";
    document.getElementById('submitBtn').innerText = "Update";

    document.getElementById('hidden_id').value = emp.EmployeeCd;
    document.getElementById('EmployeeCd').value = emp.EmployeeCd;
    document.getElementById('EmployeeCd').readOnly = true;

    document.getElementById('CollegeGroup').value = emp.CollegeGroup;
    document.getElementById('CollegeType').value = emp.CollegeType;
    document.getElementById('CollegeCode').value = emp.CollegeCode;
    document.getElementById('Name').value = emp.Name;
    document.getElementById('Desig').value = emp.Desig;
    document.getElementById('Active').value = emp.Active;
    document.getElementById('EmpType').value = emp.EmpType;
    document.getElementById('EmpRole').value = emp.EmpRole;
    document.getElementById('masked').value = emp.masked;
}
$("#backBtn").click(function(){
    window.location.href = "{{ url('checklist') }}";
});
</script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){
    $("#backBtn").click(function(){
        window.location.href = "{{ url('checklist') }}";
    });
});
</script>
</body>
</html>