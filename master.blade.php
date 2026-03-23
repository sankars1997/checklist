<!DOCTYPE html>
<html>
<head>
    <title>Checklist Manager</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-4">
<div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Checklist Manager</h2>

        <button id="backBtn" class="btn btn-secondary">
            ← Back
        </button>
    </div>
    <select id="type" class="form-select w-25 mb-3">
        <option value="">Select</option>
        <option value="main">Main Sections</option>
        <option value="sections">Sub Sections</option>
        <option value="subitems">Sub Items</option>
    </select>

    <div id="filters" class="mb-3"></div>
    <div id="table_area"></div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalForm" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="item_id">
        <input type="hidden" id="parent_id">
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" id="item_name">
        </div>
        <div class="mb-3" id="desc_div" style="display:none;">
            <label class="form-label">Description</label>
            <textarea class="form-control" id="item_desc"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="saveBtn" class="btn btn-primary">Save</button>
      </div>
    </div>
  </div>
</div>

<script>
$.ajaxSetup({
 headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
});

let currentType='', currentMain=0, currentSection=0;

$("#type").change(function(){

    currentType=$(this).val();

    $("#filters").html('');
    $("#table_area").html('');

    if(currentType=="main") loadMain();

    if(currentType=="sections") loadMainDropdown();

    if(currentType=="subitems") loadMainForSubitems();

});


/* ---------- MAIN ---------- */

function loadMain(){

    $.get("{{ url('get-main') }}", function(data){

        let html='<button class="btn btn-success mb-2" onclick="openModal(\'Add Main\',\'main\')">Add</button>';

        html+='<table class="table table-bordered">';
        html+='<tr><th>ID</th><th>Main Section</th><th>Status</th><th>Action</th></tr>';

        data.forEach(function(row){

            let name=row.main_heading.replace(/'/g,"\\'");

            html+='<tr>';

            html+='<td>'+row.id+'</td>';
            html+='<td>'+row.main_heading+'</td>';
            html+='<td>'+row.status+'</td>';

            html+='<td>';

            html+='<button class="btn btn-primary btn-sm me-1" onclick="openModal(\'Edit Main\',\'main\','+row.id+',\''+name+'\')">Update</button>';

            html+='<button class="btn btn-danger btn-sm me-1" onclick="deleteMain('+row.id+')">Delete</button>';

            html+='<button class="btn btn-warning btn-sm" onclick="toggleMain('+row.id+')">Toggle</button>';

            html+='</td></tr>';

        });

        html+='</table>';

        $("#table_area").html(html);

    });

}


/* ---------- MODAL ---------- */

function openModal(title,type,id=0,name='',desc=''){

    $("#modalTitle").text(title);

    $("#item_id").val(id);

    $("#item_name").val(name);

    $("#item_desc").val(desc);

    $("#desc_div").toggle(type=='subitems');

    if(type=='sections')
        $("#parent_id").val(currentMain);

    if(type=='subitems')
        $("#parent_id").val(currentSection);

    new bootstrap.Modal(document.getElementById('modalForm')).show();

}


/* ---------- SAVE ---------- */

$("#saveBtn").click(function(){

    let id=$("#item_id").val();

    let name=$("#item_name").val();

    let desc=$("#item_desc").val();

    let parent=$("#parent_id").val();

    if(!name){
        alert("Enter name");
        return;
    }

    /* MAIN */

    if(currentType=='main'){

        if(id==0){

            $.post("{{ url('add-main') }}",{name:name},function(){
                loadMain();
            });

        }else{

            $.post("{{ url('update-main') }}",{id:id,name:name},function(){
                loadMain();
            });

        }

    }


    /* SECTIONS */

    if(currentType=='sections'){

        if(id==0){

            $.post("{{ url('add-section') }}",{main_id:parent,name:name},function(){
                loadSections(parent);
            });

        }else{

            $.post("{{ url('update-section') }}",{id:id,name:name},function(){
                loadSections(parent);
            });

        }

    }


    /* SUBITEMS */

    if(currentType=='subitems'){

        if(id==0){

            $.post("{{ url('add-subitem') }}",{section_id:parent,name:name,description:desc},function(){
                loadSubitems(parent);
            });

        }else{

            $.post("{{ url('update-subitem') }}",{id:id,name:name,description:desc},function(){
                loadSubitems(parent);
            });

        }

    }

    bootstrap.Modal.getInstance(document.getElementById('modalForm')).hide();

});


/* ---------- DELETE & TOGGLE ---------- */

function deleteMain(id){

if(!confirm('Deletebn ?')) return;

$.ajax({
    url:"{{ url('delete-main') }}",
    type:"POST",
    data:{id:id},
    success:function(){
        loadMain();
    },
    error:function(e){
        console.log(e);
    }
});

}


function toggleMain(id){

    $.post("{{ url('toggle-main') }}",{id:id},function(){
        loadMain();
    });

}


function deleteSection(id,main_id){

if(!confirm('Delete?')) return;

$.ajax({
    url:"{{ url('delete-section') }}",
    type:"POST",
    data:{id:id},
    success:function(){
        loadSections(main_id);
    },
    error:function(e){
        console.log(e);
    }
});

}


function toggleSection(id,main_id){

    $.post("{{ url('toggle-section') }}",{id:id},function(){
        loadSections(main_id);
    });

}


function deleteSubitem(id,section_id){

if(!confirm('Delete?')) return;

$.ajax({
    url:"{{ url('delete-subitem') }}",
    type:"POST",
    data:{id:id},
    success:function(){
        loadSubitems(section_id);
    },
    error:function(e){
        console.log(e);
    }
});
}

function toggleSubitem(id,section_id){

    $.post("{{ url('toggle-subitem') }}",{id:id},function(){
        loadSubitems(section_id);
    });

}


/* ---------- SECTIONS ---------- */

function loadMainDropdown(){

    $.get("{{ url('get-main') }}", function(data){

        let html='Select Main Section: ';

        html+='<select id="main_select" class="form-select w-25">';

        html+='<option value="">Select</option>';

        data.forEach(row=>{
            html+='<option value="'+row.id+'">'+row.main_heading+'</option>';
        });

        html+='</select>';

        $("#filters").html(html);

    });

}


$(document).on('change','#main_select',function(){

    currentMain=$(this).val();

    loadSections(currentMain);

});


function loadSections(main_id){

    $.get("{{ url('get-sections') }}/"+main_id,function(data){

        let html='<button class="btn btn-success mb-2" onclick="openModal(\'Add Section\',\'sections\')">Add</button>';

        html+='<table class="table table-bordered">';

        html+='<tr><th>ID</th><th>Section</th><th>Status</th><th>Action</th></tr>';

        data.forEach(row=>{

            let name=row.section_name.replace(/'/g,"\\'");

            html+='<tr>';

            html+='<td>'+row.id+'</td>';

            html+='<td>'+row.section_name+'</td>';

            html+='<td>'+row.status+'</td>';

            html+='<td>';

            html+='<button class="btn btn-primary btn-sm me-1" onclick="openModal(\'Edit Section\',\'sections\','+row.id+',\''+name+'\')">Update</button>';

            html+='<button class="btn btn-danger btn-sm me-1" onclick="deleteSection('+row.id+','+main_id+')">Delete</button>';

            html+='<button class="btn btn-warning btn-sm" onclick="toggleSection('+row.id+','+main_id+')">Toggle</button>';

            html+='</td></tr>';

        });

        html+='</table>';

        $("#table_area").html(html);

    });

}


/* ---------- SUBITEMS ---------- */

function loadMainForSubitems(){

    $.get("{{ url('get-main') }}", function(data){

        let html='Select Main Section: ';

        html+='<select id="main_subitem" class="form-select w-25">';

        html+='<option value="">Select</option>';

        data.forEach(row=>{
            html+='<option value="'+row.id+'">'+row.main_heading+'</option>';
        });

        html+='</select>';

        $("#filters").html(html);

    });

}


$(document).on('change','#main_subitem',function(){

    currentMain=$(this).val();

    loadSubSectionDropdown(currentMain);

});


function loadSubSectionDropdown(main_id){

    $.get("{{ url('get-sections') }}/"+main_id,function(data){

        let html='Select Section: ';

        html+='<select id="section_select" class="form-select w-25">';

        html+='<option value="">Select</option>';

        data.forEach(row=>{
            html+='<option value="'+row.id+'">'+row.section_name+'</option>';
        });

        html+='</select>';

        $("#table_area").html(html);

    });

}


$(document).on('change','#section_select',function(){

    currentSection=$(this).val();

    loadSubitems(currentSection);

});


function loadSubitems(section_id){

    $.get("{{ url('get-subitems') }}/"+section_id,function(data){

        let html='<button class="btn btn-success mb-2" onclick="openModal(\'Add Subitem\',\'subitems\')">Add</button>';

        html+='<table class="table table-bordered">';

        html+='<tr><th>ID</th><th>Subitem</th><th>Description</th><th>Status</th><th>Action</th></tr>';

        data.forEach(row=>{

            let name=row.subitem.replace(/'/g,"\\'");
            let desc=row.description.replace(/'/g,"\\'");

            html+='<tr>';

            html+='<td>'+row.id+'</td>';

            html+='<td>'+row.subitem+'</td>';

            html+='<td>'+row.description+'</td>';

            html+='<td>'+row.status+'</td>';

            html+='<td>';

            html+='<button class="btn btn-primary btn-sm me-1" onclick="openModal(\'Edit Subitem\',\'subitems\','+row.id+',\''+name+'\',\''+desc+'\')">Update</button>';

            html+='<button class="btn btn-danger btn-sm me-1" onclick="deleteSubitem('+row.id+','+section_id+')">Delete</button>';

            html+='<button class="btn btn-warning btn-sm" onclick="toggleSubitem('+row.id+','+section_id+')">Toggle</button>';

            html+='</td></tr>';

        });

        html+='</table>';

        $("#table_area").html(html);

    });

}

$("#backBtn").click(function(){
    window.location.href = "{{ url('checklist') }}";
});
</script>
</body>
</html>