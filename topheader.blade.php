<style>

.mini-sidebar .sidebar-title {
    display: inline !important;
    opacity: 1 !important;
    visibility: visible !important;
    white-space: nowrap;
}
</style>


<div class="app-topstrip bg-dark py-6 px-3 w-100 d-lg-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center justify-content-center gap-5 mb-2 mb-lg-0">
        <a class="d-flex justify-content-center" href="#">
          
        </a>

        
      </div>

      <div class="d-flex justify-content-end align-items-center gap-2 w-100">
    <div class="dropdown">
    <a href="#"
   class="d-flex align-items-center justify-content-between w-100 dropdown-toggle"
   id="profileDropdown"
   data-bs-toggle="dropdown"
   aria-expanded="false">

    <!-- Welcome + Date/Time vertically stacked -->
    <div class="d-flex flex-column text-start me-2">
        <span class="sidebar-title text-nowrap fw-semibold text-white"
              style="font-size: 0.95rem; letter-spacing: 0.5px; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">
            Welcome, <span class="text-warning">{{ Auth::user()->EmployeeCd }}</span>
        </span>

        <span id="loginDateTime" 
      style="font-size: 0.75rem; letter-spacing: 0.2px; color: rgba(255,255,255,0.85);">
    <!-- JS will fill this -->
</span>

    </div>

    <!-- Stylish User Icon -->
    <span class="d-inline-flex align-items-center justify-content-center rounded-circle"
          style="width:40px; height:40px; background: linear-gradient(135deg, #6c5ce7, #a29bfe); 
                 box-shadow: 0 2px 6px rgba(0,0,0,0.25); color: #fff; font-size:1.2rem;">
        <i class="ti ti-user"></i>
    </span>

</a>



        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up bg-dark bg-opacity-75 text-white border-0 shadow-lg"
            aria-labelledby="profileDropdown"
            style="backdrop-filter: blur(5px);">
            <!-- Logout -->
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="dropdown-item d-flex align-items-center gap-2 text-white"
                            style="background: transparent; border: none; width: 100%; text-align: left;">
                        <i class="ti ti-logout fs-6"></i>
                        <span>Logout</span>
                        
                    </button>
                </form>
            </li>
        </ul>
    </div>
</div>


</div>

<script>
function updateDateTime() {
    const now = new Date();
    const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    const date = now.toLocaleDateString(undefined, options);
    const time = now.toLocaleTimeString(undefined, { hour: '2-digit', minute:'2-digit', second:'2-digit' });
    document.getElementById('loginDateTime').textContent = `${date} | ${time}`;
}

updateDateTime();
setInterval(updateDateTime, 1000);
</script>