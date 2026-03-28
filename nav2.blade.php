@php
        $user = Auth::user();
         $collegeCode = $user->CollegeCode ?? null;
        $empType = $user->EmpType ?? null;
    @endphp 

     <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
            
               
      
    
             <li>
                <a href="{{ route('homeview') }}">
                <i class="fa fa-home"></i>
                <span>Home</span>
                 </a>
            </li>

            <li>
                <a href="{{ route('homeview') }}">
                <i class="fa fa-home"></i>
                <span>College Seats</span>
                 </a>
            </li>
    
            @if(!empty($tableData))
    <li class="nav-header">TablesExam: {{ $examName }}</li>
    @foreach($tableData as $table => $data)
        <li>
            <a href="#table-{{ $loop->index }}" class="table-nav-link">
                <i class="fa fa-table"></i> {{ $table }}
            </a>
        </li>
    @endforeach
@endif
                    
</div>
					
               
                    
                </ul>
             </div>
             
</nav>

<script>
    // Wait for page to load
    document.addEventListener('DOMContentLoaded', function () {
        const buttons = document.querySelectorAll('.table-btn');
        const containers = document.querySelectorAll('.table-container');

        buttons.forEach(btn => {
            btn.addEventListener('click', function () {
                // Hide all tables
                containers.forEach(container => container.style.display = 'none');

                // Show selected table
                const target = this.getAttribute('data-target');
                document.querySelector(target).style.display = 'block';
            });
        });

        // Optionally, show the first table by default
        if (containers.length > 0) {
            containers[0].style.display = 'block';
        }
    });
</script>