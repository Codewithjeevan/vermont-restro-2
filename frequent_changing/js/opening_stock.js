$(function () {
    "use strict";

    function isChanged(input) {
        let qty = $.trim($(input).val());
        let initial = parseFloat($(input).attr("data-initial")) || 0;
        return Math.abs((qty === "" ? 0 : parseFloat(qty)) - initial) > 0.0005;
    }

    function applyFilter() {
        let category = $("#opening_category").val();
        let search = $.trim($("#opening_search").val()).toLowerCase();
        $(".opening_row").each(function () {
            let show_row = (!category || $(this).attr("data-category") === category) &&
                (!search || $(this).attr("data-search").indexOf(search) > -1);
            $(this).toggle(show_row);
        });
    }

    $("#opening_category").on("change", applyFilter);
    $("#opening_search").on("input", applyFilter);

    $(document).on("input", ".opening_qty", function () {
        $(this).removeClass("opening_invalid");
        $(this).closest("tr").toggleClass("opening_changed", isChanged(this));
        $("#opening_error").text("");
    });

    //the table is not paged, so a row hidden by the filters is still collected here
    $("#opening_stock_form").on("submit", function (e) {
        let rows = [];
        let first_invalid = null;
        $(".opening_qty").each(function () {
            let value = $.trim($(this).val());
            let qty = value === "" ? 0 : Number(value);
            if (isNaN(qty) || qty < 0) {
                $(this).addClass("opening_invalid");
                first_invalid = first_invalid || $(this);
                return;
            }
            if (isChanged(this)) {
                rows.push({ id: $(this).attr("data-id"), qty: qty });
            }
        });

        if (first_invalid) {
            e.preventDefault();
            $("#opening_category").val("").trigger("change");
            $("#opening_search").val("");
            applyFilter();
            $("#opening_error").text($("#opening_stock_invalid").val());
            first_invalid.focus();
            return false;
        }
        if (!rows.length) {
            e.preventDefault();
            $("#opening_error").text($("#opening_stock_no_changes").val());
            return false;
        }
        $("#opening_rows").val(JSON.stringify(rows));
    });
});
