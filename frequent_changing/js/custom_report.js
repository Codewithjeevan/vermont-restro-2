//pdf,print,export datatable
let jqry = $.noConflict();
jqry(document).ready(function () {
  "use strict";

  //use for every report view
  let today = new Date();
  let dd = today.getDate();
  let mm = today.getMonth() + 1; //January is 0!
  let yyyy = today.getFullYear();

  if (dd < 10) {
    dd = "0" + dd;
  }

  if (mm < 10) {
    mm = "0" + mm;
  }
  today = yyyy + "-" + mm + "-" + dd;

  //get title and datatable id name from hidden input filed that is before in the table in view page for every datatable
  let datatable_name = $(".datatable_name").attr("data-id_name");
  let title = $(".datatable_name").attr("data-title");
  let TITLE = title + "" +
    "" + today;

  //keep page, page length, search and sort of the list when the user comes back
  //from a row action (edit, delete...) - opt-in per view with data-keep_state="yes"
  let keep_state = $(".datatable_name").attr("data-keep_state") === "yes";
  let state_key = "dt_keep_state:" + location.pathname.replace(/\.html$/, "") + ":" + datatable_name;
  let filter_form = $("#filterModal form");
  let current_filter = getFilterValues();
  let restore_options = keep_state ? getRestoreOptions() : {};

  function getFilterValues() {
    let values = {};
    filter_form
      .find("select[name], input[name]")
      .not("[type=hidden], [type=submit], [type=button]")
      .each(function () {
        values[this.name] = $(this).val() || "";
      });
    return values;
  }

  function readState() {
    try {
      return JSON.parse(sessionStorage.getItem(state_key));
    } catch (e) {
      return null;
    }
  }

  function writeState(state) {
    try {
      if (state) {
        sessionStorage.setItem(state_key, JSON.stringify(state));
      } else {
        sessionStorage.removeItem(state_key);
      }
    } catch (e) {}
  }

  function getRestoreOptions() {
    let state = readState();
    if (!state) {
      return {};
    }
    let saved_filter = state.filter || {};
    if (JSON.stringify(saved_filter) !== JSON.stringify(current_filter)) {
      //the list was filtered (Filter By) when the user left it: apply that filter
      //again first, the page is restored once the filtered list loads
      let had_filter = Object.keys(saved_filter).some((name) => saved_filter[name] !== "");
      let submit_button = filter_form.find("[type=submit]")[0];
      if (had_filter && submit_button && !state.filter_applied) {
        state.filter_applied = true;
        writeState(state);
        Object.keys(saved_filter).forEach(function (name) {
          filter_form.find(`[name="${name}"]`).val(saved_filter[name]);
        });
        submit_button.click();
      } else {
        writeState(null);
      }
      return {};
    }
    writeState(null);
    return {
      displayStart: parseInt(state.start) || 0,
      pageLength: parseInt(state.length) || 10,
      search: { search: state.search || "" },
      order: Array.isArray(state.order) ? state.order : [[0, "desc"]],
    };
  }

  if (keep_state) {
    jqry(document).on("click", `#${datatable_name} a[href]:not([href^="javascript"])`, function () {
      let table = jqry(`#${datatable_name}`).DataTable();
      let info = table.page.info();
      writeState({
        start: info.start,
        length: info.length,
        search: table.search(),
        order: table.order().map((column) => [column[0], column[1]]),
        filter: current_filter,
      });
    });
  }

  jqry(`#${datatable_name},#datatable2`).DataTable(jqry.extend({
    autoWidth: false,
    ordering: true,
    order: [[0, "desc"]],
    // dom: "Bfrtip",
    dom: '<"top-left-item col-sm-12 col-md-6"lf> <"top-right-item col-sm-12 col-md-6"B> t <"bottom-left-item col-sm-12 col-md-6 "i><"bottom-right-item col-sm-12 col-md-6 "p>',
    buttons: [
      {
        extend: "print",
        title: TITLE,
        text: '<i class="fa fa-print"></i> Print',
        titleAttr: "print",
      },
      {
        extend: "copyHtml5",
        title: TITLE,
        text: '<i class="fa fa-files-o"></i> Copy',
        titleAttr: "Copy",
      },
      {
        extend: "excelHtml5",
        title: TITLE,
        text: '<i class="fa fa-file-excel-o"></i> Excel',
        titleAttr: "Excel",
      },
      {
        extend: "csvHtml5",
        title: TITLE,
        text: '<i class="fa fa-file-text-o"></i> CSV',
        titleAttr: "CSV",
      },
      {
        extend: "pdfHtml5",
        title: TITLE,
        text: '<i class="fa fa-file-pdf-o"></i> PDF',
        titleAttr: "PDF",
      },
    ],
    language: {
      paginate: {
        previous: "Previous",
        next: "Next",
      },
    },
  }, restore_options));
});
