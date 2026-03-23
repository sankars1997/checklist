<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checklist Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    body { background: #f5f7fa; }
    #pageTitle {
        font-size: 30px;
        font-weight: 800;
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }
    .exam-chip {
        cursor: pointer;
        padding: 10px 18px;
        margin: 4px;
        border-radius: 25px;
        background: #0069d9;
        color: white;
        font-weight: 600;
        transition: .3s ease;
    }
    .exam-chip:hover {
        background: #0053b3;
        transform: scale(1.05);
    }
    .section-title {
        font-size: 22px;
        font-weight: 700;
        margin-top: 15px;
        margin-bottom: 8px;
        text-decoration: underline #007bff 2px;
        color: #2d2d2d;
    }
    .badge-status {
        font-size: 14px;
        font-weight: 600;
        padding: 6px 12px;
    }



    
</style>
</head>
<body class="p-4">

<div class="container-fluid">

    <div id="pageTitle">
        Active Examinations • {{ date('Y') }}
    </div>
    <div style="position:absolute; right:20px; top:20px;">
    <button onclick="downloadPDF()" class="btn btn-danger">
        Download PDF
    </button>


    <button onclick="openLogin()" class="btn btn-primary">
        Login
    </button>
</div>
    <div class="text-center mb-3">
        @foreach($exams as $exam)
            <span class="exam-chip"
                  data-examid="{{ $exam->examid }}"
                  data-examname="{{ $exam->exam_name }}">
                {{ $exam->exam_name }}
            </span>
        @endforeach
    </div>

    <hr>

    <div class="d-flex gap-4">

        <div class="left-panel w-50">
        <div id="exam-header"></div>
            <div id="main-headings"></div>
            <div id="sections-table"></div>
            <div id="subitems-table"></div>
        </div>

        <div class="right-panel w-50 text-center">
            <canvas id="statusPieChart" width="350" height="350"></canvas>
        </div>

    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    Chart.register(ChartDataLabels);

    const pieColors = {
        // Main headings + Subitems
        'OK': '#28a745',
        'Pending': '#ffc107',
        'Not OK': '#dc3545',
        'Not Started': '#6c757d',

        // Sections
        'Completed': '#28a745',
        'On Going': '#fd7e14',
        'Not Completed': '#6c757d'
    };

    let activeExamId = null;
    let activeExamName = '';
    let pieChart = null;

    function getColor(status) {
        return pieColors[status] || '#6c757d';
    }

    function badgeHTML(status, updatedAt) {

let tooltip = updatedAt 
    ? `Updated: ${updatedAt}` 
    : ``;

return `<span class="badge badge-status text-white"
        title="${tooltip}"
        style="background:${getColor(status)}">
        ${status}
    </span>`;
}
    /* =========================
       PIE CHART WITH FULL TOOLTIP
    ========================= */
    function drawPie(title, groups) {

        const labels = Object.keys(groups).filter(l => groups[l].length > 0);
        const values = labels.map(l => groups[l].length);
        const colors = labels.map(l => getColor(l));

        const ctx = document.getElementById('statusPieChart').getContext('2d');
        if (pieChart) pieChart.destroy();

        pieChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: title,
                        font: { size: 18, weight: '700' }
                    },

                    tooltip: {
                        callbacks: {
                            label: function(context) {

                                const total = context.dataset.data.reduce((a,b)=>a+b,0);
                                const value = context.raw;
                                const percent = total ? ((value/total)*100).toFixed(1) : 0;

                                const status = context.label;
                                const items = groups[status] || [];

                                let lines = [];
                                lines.push(`${status} — ${percent}%`);

                                const maxItems = 10;

                                items.slice(0, maxItems).forEach(item => {
                                    lines.push("• " + item);
                                });

                                if (items.length > maxItems) {
                                    lines.push(`...and ${items.length - maxItems} more`);
                                }

                                return lines;
                            }
                        }
                    },

                    datalabels: {
                        color: '#222',
                        formatter: (value, ctx) => {
                            const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                            return total ? ((value/total)*100).toFixed(1)+'%' : '';
                        }
                    },

                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    /* =========================
       EXAM CLICK
    ========================= */
    document.querySelectorAll('.exam-chip').forEach(el => {
        el.onclick = function () {
            activeExamId = this.dataset.examid;
            activeExamName = this.dataset.examname;
            loadMainHeadings();
        };
    });

    /* =========================
       MAIN HEADINGS
    ========================= */
    function loadMainHeadings() {

fetch("{{ route('checklist.fetchMainHeadings') }}", {
    method:'POST',
    headers:{
        "Content-Type":"application/json",
        "X-CSRF-TOKEN":document.querySelector("[name=csrf-token]").content
    },
    body: JSON.stringify({ exam_id: activeExamId })
})
.then(r=>r.json())
.then(res => {

    const data = res.mainHeadings || [];
    const details = res.examDetails || {};

    let groups = {'OK':[], 'Pending':[], 'Not OK':[], 'Not Started':[]};

    data.forEach(m => {
        groups[m.overall_status].push(m.main_heading);
    });

    drawPie(`${activeExamName} Application Setup`, groups);

    /* =========================
       🔴 EXAM HEADER + DETAILS
    ========================= */

    let headerHTML = `
    <div style="color:red; font-weight:700; font-size:32px; margin-bottom:10px;">
       <u> ${activeExamName}</u>
    </div>

    <table class="table table-bordered table-sm mb-4">
    <tbody>
        <tr>
            <th style="width:30%">Employee Code</th>
            <td>${details.employee_cd || ''}</td>
        </tr>
        <tr>
            <th>Name</th>
            <td>${details.name || ''}</td>
        </tr>
        <tr>
            <th>Programmer Code</th>
            <td>${details.programmer_cd || ''}</td>
        </tr>
        <tr>
            <th>Exam ID</th>
            <td>${details.exam_id || ''}</td>
        </tr>
        <tr>
            <th>Year</th>
            <td>${details.year || ''}</td>
        </tr>
    </tbody>
</table>
`;


    document.getElementById('exam-header').innerHTML = headerHTML;

    /* =========================
       MAIN HEADINGS TABLE
    ========================= */

    let html = `<div class="section-title">${activeExamName} Application Setup</div>
                <table class="table table-hover"><tbody>`;

    data.forEach(m => {
        html += `<tr class="pointer main-row" data-mainid="${m.id}">
                    <td>${m.main_heading}</td>
                    <td>${badgeHTML(m.overall_status)}</td>
                 </tr>`;
    });

    html += `</tbody></table>`;

    document.getElementById('main-headings').innerHTML = html;
    document.getElementById('sections-table').innerHTML = '';
    document.getElementById('subitems-table').innerHTML = '';

    document.querySelectorAll('.main-row').forEach(r => {
        r.onclick = function() {
            loadSections(this.dataset.mainid, this.children[0].innerText);
        };
    });

});
}

    /* =========================
       SECTIONS
    ========================= */
    function loadSections(mainId, mainName) {

        fetch("{{ route('checklist.fetchSections') }}", {
            method:'POST',
            headers:{
                "Content-Type":"application/json",
                "X-CSRF-TOKEN":document.querySelector("[name=csrf-token]").content
            },
            body: JSON.stringify({ main_id: mainId, exam_id: activeExamId })
        })
        .then(r=>r.json())
        .then(res => {

            const data = res.sections || [];
            let groups = {'Completed':[], 'On Going':[], 'Not Completed':[]};

            data.forEach(s => {
                groups[s.status].push(s.section_name);
            });

            drawPie(`${mainName} — Sections`, groups);

            let html = `<div class="section-title">${mainName} — Sections</div>
                        <table class="table table-hover"><tbody>`;

            data.forEach(s => {
                html += `<tr class="pointer section-row" data-sectionid="${s.id}">
                            <td>${s.section_name}</td>
                            <td>${badgeHTML(s.status)}</td>
                         </tr>`;
            });

            html += `</tbody></table>`;

            document.getElementById('sections-table').innerHTML = html;
            document.getElementById('subitems-table').innerHTML = '';

            document.querySelectorAll('.section-row').forEach(r => {
                r.onclick = () =>
                    loadSubitems(r.dataset.sectionid, r.children[0].innerText);
            });
        });
    }

    /* =========================
       SUBITEMS
    ========================= */
    function loadSubitems(sectionId, sectionName) {

fetch("{{ route('checklist.fetchSubitems') }}", {
    method:'POST',
    headers:{
        "Content-Type":"application/json",
        "X-CSRF-TOKEN":document.querySelector("[name=csrf-token]").content
    },
    body: JSON.stringify({ section_id: sectionId, exam_id: activeExamId })
})
.then(r=>r.json())
.then(res => {

    const data = res.subitems || [];

    let groups = {
        'OK':[],
        'Pending':[],
        'Not OK':[],
        'Not Started':[]
    };

    data.forEach(si => {

        if(!groups[si.status]){
            groups[si.status] = [];
        }

        groups[si.status].push(si.subitem);
    });

    drawPie(`${sectionName} — Subitems`, groups);

    let html = `<div class="section-title">${sectionName} — Subitems</div>
                <table class="table table-hover">
                <tbody>`;

    data.forEach(si => {
        html += `<tr>
                    <td>${si.subitem}</td>
                    <td>${si.description || ''}</td>
                    <td>${badgeHTML(si.status, si.updated_at)}</td>
                 </tr>`;
    });

    html += `</tbody></table>`;

    document.getElementById('subitems-table').innerHTML = html;
});
}

});








function downloadPDF() {

let header = document.getElementById("exam-header").innerHTML;
let main = document.getElementById("main-headings").innerHTML;
let sections = document.getElementById("sections-table").innerHTML;
let subitems = document.getElementById("subitems-table").innerHTML;

let year = new Date().getFullYear();

let content = `
<html>
<head>
    <title>Checklist</title>

    <style>
        body{
            font-family: Arial;
            padding:30px;
        }

        .office-title{
            text-align:center;
            font-size:24px;
            font-weight:bold;
        }

        .office-contact{
            text-align:center;
            font-size:14px;
            margin-bottom:20px;
        }

        .report-title{
            text-align:center;
            font-size:20px;
            font-weight:bold;
            margin-bottom:20px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-bottom:20px;
        }

        table, th, td{
            border:1px solid #999;
        }

        th, td{
            padding:8px;
        }
    </style>

</head>

<body>

    <div class="office-title">
        Office of the Commissioner for Entrance Examinations
    </div>

    <div class="office-contact">
        Phone : 0471-2332120 | 0471-2338487 <br>
        E-mail: ceekinfo.cee@kerala.gov.in
    </div>

    <div class="report-title">
        Examination Checklist • ${year}
    </div>

    ${header}
    ${main}
    ${sections}
    ${subitems}

</body>
</html>
`;

let win = window.open("", "", "width=900,height=700");

win.document.write(content);
win.document.close();

win.focus();
win.print();
}




function openLogin() {
    window.open("http://192.192.192.105/checklist/public/checklist", "_blank");
}
</script>

</body>
</html>