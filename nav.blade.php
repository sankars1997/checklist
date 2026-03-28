<style>
/* Sidebar background */
.sidebar-container {
    background-color: #111; /* black */
    color: #fff;
    width: 283px; /* optional width */
}

/* Main menu item */
.sidebar-item > a,
.sidebar-item > span {
    display: flex;
    align-items: center;
    color: #fff; /* main menu always white */
    padding: 10px 15px;
    text-decoration: none;
    transition: background 0.3s, color 0.3s;
}

/* Hover effect for main menu */
.sidebar-item > a:hover,
.sidebar-item > span:hover {
    background-color: #222;
    color: #1abc9c;
}

/* Submenu below parent */
.sidebar-submenu {
    display: none;
    margin-left: 15px; /* indent */
    background-color: #111; /* same as parent */
    list-style: none;
    padding-left: 0;
}

/* Show submenu */
.sidebar-item.has-children.open .sidebar-submenu {
    display: block;
}

/* Submenu items */
.sidebar-subitem a {
    display: block;
    padding: 8px 20px;
    color: #fff;
    text-decoration: none;
    transition: background 0.3s, color 0.3s;
}

/* Hover for submenu */
.sidebar-subitem a:hover {
    background-color: #222;
    color: #1abc9c;
}

/* Completed or pending status */
.sidebar-subitem a.text-pending {
    color: #ccc !important; /* grey for pending */
}

.sidebar-subitem a.text-complete {
    color: #28a745 !important; /* green for completed */
}

/* Optional: arrow rotation */
.menu-toggle .fa-angle-down {
    margin-left: auto;
    transition: transform 0.3s;
}

.sidebar-item.open .menu-toggle .fa-angle-down {
    transform: rotate(180deg);
}
</style>

<aside class="sidebar-container" id="sidebarContainer">
    <div class="sidebar-header">
        <span class="sidebar-title">CEE</span>
        <button class="sidebar-toggler" id="sidebarCollapse">
            <i class="fa fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-content">
        <ul class="sidebar-menu">
        @php
$user = Auth::user();
$collegeCode = $user->CollegeCode ?? null;
$empType = $user->EmpType ?? null;
$currentYear = date('Y');

// Fetch only active menus
$menus = DB::table('menus')
    ->where('status', 'Y') // only Y menus
    ->orderBy('ordering')
    ->get();

// Filter menus by allowed roles dynamically
$filteredMenus = $menus->filter(function($menu) use ($empType) {
    return $menu->allowed_roles === 'ALL' || in_array($empType, explode(',', $menu->allowed_roles));
});

// Get menu status for coloring
$menuStatus = DB::table('menu_status')
    ->where('CollegeCode', $collegeCode)
    ->pluck('Status', 'menu_id')
    ->toArray();


            $currentYear = date('Y');
        @endphp

        @foreach ($filteredMenus->whereNull('parent_id') as $menu)
            @php
                $children = $filteredMenus->where('parent_id', $menu->id);
                $hasChildren = $children->count() > 0;
                $menuTitle = str_replace('2024', $currentYear, $menu->title);
            @endphp

            <li class="sidebar-item {{ $hasChildren ? 'has-children' : '' }}">
                {{-- Parent menu --}}
                @if($hasChildren)
                    <a href="javascript:void(0)" class="menu-toggle">
                        @if($menu->icon)
                            <i class="{{ $menu->icon }}" style="margin-right: 8px;"></i>
                        @endif
                        <span>{{ $menuTitle }}</span>
                        <i class="fa fa-angle-down submenu-arrow"></i>
                    </a>

                    {{-- Submenu --}}
                    <ul class="sidebar-submenu">
                        @foreach($children as $child)
                            @php $childStatus = $menuStatus[$child->id] ?? 'N'; @endphp
                            <li class="sidebar-subitem">
                                <a href="{{ $child->route ? route($child->route) : '#' }}" 
                                   class="{{ $childStatus === 'Y' ? 'text-complete' : 'text-pending' }}">
                                   @if($child->icon)
                                       <i class="{{ $child->icon }}" style="margin-right: 8px;"></i>
                                   @endif
                                   {{ str_replace('2024', $currentYear, $child->title) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                @elseif($menu->route)
                    <a href="{{ route($menu->route) }}">
                        @if($menu->icon)
                            <i class="{{ $menu->icon }}" style="margin-right: 8px;"></i>
                        @endif
                        <span>{{ $menuTitle }}</span>
                    </a>
                @elseif($menu->url)
                    <a href="{{ url($menu->url) }}">
                        @if($menu->icon)
                            <i class="{{ $menu->icon }}" style="margin-right: 8px;"></i>
                        @endif
                        <span>{{ $menuTitle }}</span>
                    </a>
                @else
                    <span>
                        @if($menu->icon)
                            <i class="{{ $menu->icon }}" style="margin-right: 8px;"></i>
                        @endif
                        <span>{{ $menuTitle }}</span>
                    </span>
                @endif
            </li>
        @endforeach

        </ul>
    </nav>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Toggle submenu open/close
    document.querySelectorAll('.menu-toggle').forEach(toggle => {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const parent = this.closest('.sidebar-item');
            parent.classList.toggle('open');
        });
    });
});
</script>
