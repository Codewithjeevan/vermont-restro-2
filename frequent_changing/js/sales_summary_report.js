//pdf,print,export datatable for sales summary reports (every table keeps its own row order, total row is in tfoot)
(function () {
    "use strict";
    let jqry = $.noConflict();
    //tables are already above this script, init now so forTable.js (300ms later) can put the buttons in its Export dropdown
    (function () {
        jqry(".ssr_table").each(function () {
            let table = jqry(this);
            let title = table.attr("data-title");
            let searching = table.attr("data-search") === "yes";
            let paging = table.attr("data-paging") === "yes";
            table.DataTable({
                autoWidth: false,
                ordering: false,
                paging: paging,
                pageLength: 100,
                lengthMenu: [[50, 100, 500, -1], [50, 100, 500, "All"]],
                info: paging,
                searching: searching,
                dom: '<"top-left-item col-sm-12 col-md-6"lf> <"top-right-item col-sm-12 col-md-6"B> t <"bottom-left-item col-sm-12 col-md-6 "i><"bottom-right-item col-sm-12 col-md-6 "p>',
                buttons: [
                    {
                        extend: "print",
                        title: title,
                        footer: true,
                        text: '<i class="fa fa-print"></i> Print',
                        titleAttr: "print",
                    },
                    {
                        extend: "copyHtml5",
                        title: title,
                        footer: true,
                        text: '<i class="fa fa-files-o"></i> Copy',
                        titleAttr: "Copy",
                    },
                    {
                        extend: "excelHtml5",
                        title: title,
                        footer: true,
                        text: '<i class="fa fa-file-excel-o"></i> Excel',
                        titleAttr: "Excel",
                    },
                    {
                        extend: "csvHtml5",
                        title: title,
                        footer: true,
                        text: '<i class="fa fa-file-text-o"></i> CSV',
                        titleAttr: "CSV",
                    },
                    {
                        extend: "pdfHtml5",
                        title: title,
                        footer: true,
                        orientation: table.find("thead th").length > 6 ? "landscape" : "portrait",
                        pageSize: table.find("thead th").length > 14 ? "A3" : "A4",
                        text: '<i class="fa fa-file-pdf-o"></i> PDF',
                        titleAttr: "PDF",
                    },
                ],
                language: {
                    paginate: {
                        previous: "<i class='fa fa-chevron-left'></i>",
                        next: "<i class='fa fa-chevron-right'></i>",
                    },
                },
            });
        });
    })();
})();
