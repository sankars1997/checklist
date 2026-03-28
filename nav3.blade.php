@php
        $user = Auth::user();
         $collegeCode = $user->CollegeCode ?? null;
        $empType = $user->EmpType ?? null;
    @endphp 

     <nav class="navbar-default navbar-side" role="navigation">
            <div class="sidebar-collapse">
                <ul class="nav" id="main-menu">
            
               
      
    
             <li>
                <a href="{{ route('home') }}">
                <i class="fa fa-home"></i>
                <span>Home</span>
                 </a>
            </li>
    

                    <li>
                    @if($empType !== 'C' && $empType !== 'D')
        
            <a href="index.html">
                <i class="fa fa-folder-open-o"></i>
                <span>OAMS</span>
            </a>
        </li>
        @endif
                    </li>               
        
                    <li>
                    @if(in_array($empType, ['A', 'P']))
        
                    <a href="{{ route('clg_details') }}">
            <i class="fa fa-user"></i>
                <span>Basic Details for CAP 2024</span>
            </a>
        </li>
        @endif
                    </li>               
        
        
       
      
        <li class="modern-menu-item active">
            <a href="index.html">
            <i class="fa fa-institution"></i> 
                <span>Community /Society /Trust Quota</span>
            </a>
        </li>

        @php
    $hasFeeTransfer = DB::table('fee_transfer')
        ->where('Year', 2024)
        ->where('College', $collegeCode)
        ->whereIn('ExamName', ['KEA'])
        ->exists();
@endphp


    <li class="modern-menu-item active">
        <a href="index.html">
        <i class="fa fa-book"></i>  
            <span>Vacant Seat confirmation</span>
        </a>
    </li> 
    
    <li class="modern-menu-item active">
        <a href="index.html">
        <i class="fa fa-book"></i>  
            <span>2022 LIG Verification</span>
        </a>
    </li> 

    <li class="modern-menu-item active">
        <a href="index.html">
        <i class="fa fa-book"></i>  
            <span>Spot Admission Upload</span>
        </a>
    </li> 


    <li class="modern-menu-item active">
        <a href="index.html">
        <i class="fa fa-book"></i>  
            <span>Data Collection</span>
        </a>
    </li> 

    <li class="modern-menu-item active">
        <a href="index.html">
        <i class="fa fa-book"></i>  
            <span>Various List</span>
        </a>
    </li> 

    <li class="modern-menu-item active">
        <a href="index.html">
        <i class="fa fa-book"></i>  
            <span>Seat Details confirmation</span>
        </a>
    </li> 


{{-- More menu items go here as needed --}}
<li class="modern-menu-item active">
    <a href="index.html">
    <i class="fa fa-user"></i>   
        <span>Change Password</span>
    </a>
</li>


@if($empType === 'C')
                
<li class="modern-menu-item active">
    <a href="index.html">
    <i class="fa fa-user"></i>    
        <span>User Management</span>
    </a>
</li>
@endif

</div>
					
               
                    
                </ul>
             </div>
             
</nav>