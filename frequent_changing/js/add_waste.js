"use strict";
let warning = $("#warning").val();
let a_error = $("#a_error").val();
let ok = $("#ok").val();
let cancel = $("#cancel").val();
let are_you_sure = $("#are_you_sure").val();
let base_url_ = $("#base_url_").val();
let ingredient_already_remain = $("#ingredient_already_remain").val();
let responsible_person_field_required = $("#responsible_person_field_required").val();
let date_field_required = $("#date_field_required").val();
let at_least_ingredient = $("#at_least_ingredient").val();
let note_field_cannot = $("#note_field_cannot").val();
let wast_amt = $("#wast_amt").val();
let loss_amt = $("#loss_amt").val();
//the edit page fills this with the ingredients already on the waste
let ingredient_id_container = window.waste_ingredient_ids || [];

//recipe x quantity without float noise like 0.30000000000000004
function roundWasteQty(qty) {
    return parseFloat(qty.toFixed(4));
}

$(function() {

    //Initialize Select2 Elements
    $('.select2').select2();
    let suffix = parseInt($('#suffix_hidden_field').val(), 10) || 0;

    //a waste is either one food menu or hand picked ingredients, so one picker locks the other
    if ($('#food_menu_id').val()) {
        $('#ingredient_id').prop('disabled', true);
    } else if ($('#waste_cart tbody tr').length) {
        $('#food_menu_id').prop('disabled', true);
    }

    function resetWasteCart() {
        $('#waste_cart tbody tr').remove();
        $('#waste_quantity').val('');
        $('#total_loss').val('');
        $('#suffix_hidden_field').val('');
        ingredient_id_container = [];
        suffix = 0;
    }
    function leaveFoodMenuWaste() {
        resetWasteCart();
        //this namespace only refreshes select2, the change handler below stays out of it
        $('#food_menu_id').val('').trigger('change.select2').prop('disabled', false);
        $('#ingredient_id').prop('disabled', false);
        $('#food_menu_waste_quantity_row').addClass('ir_display_none');
    }

    $(document).on('change', '#food_menu_id', function() {
        let f_menu_id = $('#food_menu_id').val();
        $('#food_menu_waste_quantity').val('');
        resetWasteCart();
        if (f_menu_id != '') {
            $('#ingredient_id').prop('disabled', true);
            $(".food_menu_waste_quantity_err_msg_contnr").hide();
            $('#food_menu_waste_quantity_row').removeClass('ir_display_none');
            $('#food_menu_waste').modal('show');
        } else {
            leaveFoodMenuWaste();
        }
    });
    //popup closed without a quantity: nothing was picked
    $('#food_menu_waste').on('hidden.bs.modal', function() {
        if ($('#waste_quantity').val() == '') {
            leaveFoodMenuWaste();
        }
    });
    $(document).on('click', '#delete_all_ingredient_list', function() {
        leaveFoodMenuWaste();
    });
    //Enter inside the popup must not post its form and reload the page
    $(document).on('submit', '#loan_form', function(e) {
        e.preventDefault();
        $('#food_menu_waste_button').trigger('click');
    });

    $(document).on('click', '#food_menu_waste_button', function() {
        let f_menu_id = $('#food_menu_id').val();
        let quantity = parseFloat($('#food_menu_waste_quantity').val());
        if (isNaN(quantity) || quantity <= 0) {
            $("#food_menu_waste_quantity_err_msg").text($("#waste_quantity_invalid").val());
            $(".food_menu_waste_quantity_err_msg_contnr").show(200);
            return false;
        }
        $(".food_menu_waste_quantity_err_msg_contnr").hide();
        let currency = $("#currency").val();

        if (f_menu_id != '') {
            $('#waste_quantity').val(quantity);
            $('#food_menu_waste').modal('hide');
            let csrf_name_= $("#csrf_name_").val();
            let csrf_value_= $("#csrf_value_").val();
            let options = '';
            $.ajax({
                type: "get",
                url: base_url_+'Waste/food_menus_ingredients',
                data: {
                    id: f_menu_id,
                    csrf_name_: csrf_value_
                },
                dataType: "json",
                success: function(data) {
                    $('#food_menu_waste_quantity').val('');
                    if (!data.length) {
                        leaveFoodMenuWaste();
                        swal({
                            title: warning+"!",
                            text: $("#food_menu_no_recipe").val(),
                            confirmButtonText: ok,
                            confirmButtonColor: '#3c8dbc'
                        });
                        return;
                    }
                    let j = 0;
                    let total_loss = 0;

                    $.each(data, function(i, v) {
                        let qty = roundWasteQty(quantity * v.consumption);
                        let los_amount = (qty * v.consumption_unit_cost).toFixed(2);
                        total_loss = total_loss + parseFloat(los_amount);
                        j++;
                        i++;
                        options += '<tr class="rowCount" data-id="' + i +
                            '" id="row_' + i + '">' +

                            '<td style="padding-left: 10px;"><p id="sl_' + i +
                            '">' + j + '</p></td>' +
                            '<input type="hidden" id="ingredient_id_' + i +
                            '" name="ingredient_id[]" value="' + v.ingredient_id +
                            '"/>' +
                            '<td><span style="padding-bottom: 5px;">' + v.name +
                            "(" + v.code + ")" + '</span></td>' +
                            '<td class="ir_w_15"><input readonly  type="text" data-countID="' +
                            i + '" id="waste_amount_' + i +
                            '" name="waste_amount[]" onfocus="this.select();" class="form-control integerchk aligning" placeholder="Waste Amt" value="' +
                            qty + '" /><span class="label_aligning"> ' + v
                                .unit_name +
                            '</span><span id="unit_consumption_ingredient_' + i +
                            '" class="unit_consumption_ingredient ir_display_none">' +
                            v.consumption + '</span></td>' +
                            '<input type="hidden" id="last_purchase_price_' + i +
                            '" name="last_purchase_price[]" value="' + v.consumption_unit_cost + '"/>' +
                            '<td><input type="text" id="loss_amount_' + i +
                            '" name="loss_amount[]" onfocus="this.select();" class="form-control aligning" placeholder="Loss Amt" value="' +
                            los_amount +
                            '" readonly /><span class="label_aligning">' +
                            currency + '</span><span id="unit_price_ingredient_' +
                            i + '" class="unit_price_ingredient ir_display_none">' +
                            v.consumption_unit_cost + '</span></td>' +
                            '<td></td>' +
                            '</tr>';

                    });
                    suffix = data.length;
                    $('#suffix_hidden_field').val(suffix);
                    $('#waste_cart tbody').append(options);
                    $("#total_loss").val(total_loss.toFixed(2));
                }
            });

        }
    });
    //changing the food menu quantity recalculates every ingredient row from its recipe
    $(document).on('input', '#waste_quantity', function() {
        //check wether value is valid or not
        remove_last_two_digit_without_percentage($(this).val(), $(this));

        let given_amount = parseFloat($(this).val());
        if (isNaN(given_amount) || given_amount < 0) {
            given_amount = 0;
        }

        let total_loss = 0;
        $('.rowCount').each(function(i, obj) {
            let row_id = $(this).attr('id').substr(4);
            let unit_price = parseFloat($(this).find('.unit_price_ingredient').html());
            let unit_consumption = parseFloat($(this).find('.unit_consumption_ingredient').html());
            let updated_consumption = roundWasteQty(unit_consumption * given_amount);
            let updated_price = (unit_price * updated_consumption).toFixed(2);
            $('#waste_amount_' + row_id).val(updated_consumption);
            $('#loss_amount_' + row_id).val(updated_price);
            total_loss += parseFloat(updated_price);
        });
        $('#total_loss').val(total_loss.toFixed(2));

    });
    $(document).on('change', '#ingredient_id', function() {
        let ingredient_details = $('#ingredient_id').val();

        if (ingredient_details != '') {
            $('#food_menu_id').prop('disabled', true);

            let ingredient_details_array = ingredient_details.split('|');

            let index = ingredient_id_container.indexOf(ingredient_details_array[0]);

            if (index > -1) {
                swal({
                    title: warning+"!",
                    text: ingredient_already_remain,
                    confirmButtonText: ok,
                    confirmButtonColor: '#3c8dbc'
                });
                $('#ingredient_id').val('').change();
                return false;
            }

            let currency = $("#currency").val();

            suffix++;

            let cart_row = '<tr class="rowCount" data-id="' + suffix + '" id="row_' + suffix + '">' +
                '<td style="padding-left: 10px;"><p id="sl_' + suffix + '">' + suffix + '</p></td>' +
                '<input type="hidden" id="ingredient_id_' + suffix +
                '" name="ingredient_id[]" value="' + ingredient_details_array[0] + '"/>' +
                '<td><span style="padding-bottom: 5px;">' + ingredient_details_array[1] +
                '</span></td>' +
                '<td class="ir_w_15"><input type="text" data-countID="' + suffix +
                '" id="waste_amount_' + suffix +
                '" name="waste_amount[]" onfocus="this.select();" class="form-control integerchk aligning" placeholder="'+wast_amt+'" onkeyup="return calculateAll();"/><span class="label_aligning"> ' +
            ingredient_details_array[2] + '</span></td>' +
            '<input type="hidden" id="last_purchase_price_' + suffix +
            '" name="last_purchase_price[]" value="' + ingredient_details_array[3] + '"/>' +
            '<td><input type="text" id="loss_amount_' + suffix +
            '" name="loss_amount[]" onfocus="this.select();" class="form-control aligning" placeholder="'+loss_amt+'" readonly /><span class="label_aligning">' +
            currency + '</span></td>' +
            '<td><a class="btn btn-danger btn-xs" style="margin-left: 5px; margin-top: 10px;" onclick="return deleter(' +
            suffix + ',' + ingredient_details_array[0] +
            ');" ><i class="fa fa-trash"></i> </a></td>' +
            '</tr>';

            $('#suffix_hidden_field').val(suffix);

            $('#waste_cart tbody').append(cart_row);

            ingredient_id_container.push(ingredient_details_array[0]);

            $('#ingredient_id').val('').change();
            calculateAll();

        }
    });


    // Validate form
    $(document).on('submit', '#waste_form', function() {
        let date = $("#date").val();
        let employee_id = $("#employee_id").val();
        let note = $("#note").val();
        let ingredientCount = $("#waste_cart tbody tr").length;
        let error = false;

        if (employee_id == "") {
            $("#employee_id_err_msg").text(responsible_person_field_required);
            $(".employee_id_err_msg_contnr").show(200);
            error = true;
        }

        if (date == "") {
            $("#date_err_msg").text(date_field_required);
            $(".date_err_msg_contnr").show(200);
            error = true;
        }

        if (ingredientCount < 1) {
            $("#ingredient_id_err_msg").text(at_least_ingredient);
            $(".ingredient_id_err_msg_contnr").show(200)
            error = true;
        }

        if (note.length > 200) {
            $("#note_err_msg").text(note_field_cannot);
            $(".note_err_msg_contnr").show(200);
            error = true;
        }

        if ($("#food_menu_id").val() && !(parseFloat($("#waste_quantity").val()) > 0)) {
            $("#waste_quantity_err_msg").text($("#waste_quantity_invalid").val());
            $(".waste_quantity_err_msg_contnr").show(200);
            error = true;
        }

        //only the cart rows: the popup's quantity box has this class too but no row
        $("#waste_cart .integerchk").each(function() {
            let n = $(this).attr("data-countID");
            let waste_amount = $.trim($("#waste_amount_" + n).val());
            if (waste_amount == '' || isNaN(waste_amount)) {
                $("#waste_amount_" + n).css({
                    "border-color": "red"
                }).show(200).delay(2000, function() {
                    $("#waste_amount_" + n).css({
                        "border-color": "#d2d6de"
                    });
                });
                error = true;
            }
        });

        //returning true here would let the form post anyway
        if (error == true) {
            return false;
        }

    });

})

function calculateAll() {
    let total_loss = 0;
    let i = 1;
    $(".rowCount").each(function() {
        let id = $(this).attr("data-id");
        let waste_amount = $("#waste_amount_" + id).val();
        let temp = "#sl_" + id;
        $(temp).html(i);
        i++;
        if (typeof(waste_amount) !== "undefined" && waste_amount !== null) {

            let last_purchase_price = $("#last_purchase_price_" + id).val();

            if ($.trim(waste_amount) == "" || $.isNumeric(waste_amount) == false) {
                waste_amount = 0;
            }
            if ($.trim(last_purchase_price) == "" || $.isNumeric(last_purchase_price) == false) {
                last_purchase_price = 0;
            }

            let loss_amount = parseFloat($.trim(waste_amount)) * parseFloat($.trim(last_purchase_price));

            $("#loss_amount_" + id).val(loss_amount.toFixed(2));
            total_loss += parseFloat($.trim($("#loss_amount_" + id).val()));

        }
    });

    $("#total_loss").val(total_loss.toFixed(2));

}

function deleter(suffix, ingredient_id) {
    swal({
        title: warning,
        text: are_you_sure,
        confirmButtonColor: '#3c8dbc',
        cancelButtonText: cancel,
        confirmButtonText: ok,
        showCancelButton: true
    }, function() {
        $("#row_" + suffix).remove();
        let ingredient_id_container_new = [];

        for (let i = 0; i < ingredient_id_container.length; i++) {
            if (ingredient_id_container[i] != ingredient_id) {
                ingredient_id_container_new.push(ingredient_id_container[i]);
            }
        }
        ingredient_id_container = ingredient_id_container_new;

        updateRowNo();
        calculateAll();
        if ($("#waste_cart tbody tr").length == 0) {
            $('#food_menu_id').prop('disabled', false);
        }
    });
}

function updateRowNo() {
    let numRows = $("#waste_cart tbody tr").length;
    for (let r = 0; r < numRows; r++) {
        $("#waste_cart tbody tr").eq(r).find("td:first p").text(r + 1);
    }
}
//remove last digits if number is more than 2 digits after decimal
function remove_last_two_digit_without_percentage(value, object_element) {
    if (value.length > 0 && value.indexOf('.') > 0) {
        let percentage = false;
        let number_without_percentage = value;
        if (value.indexOf('%') > 0) {
            percentage = true;
            number_without_percentage = value.toString().substring(0, value.length - 1);
        }
        let number = number_without_percentage.split('.');
        if (number[1].length > 2) {
            let value = parseFloat(Math.floor(number_without_percentage * 100) / 100);
            let add_percentage = (percentage) ? '%' : '';
            if (isNaN(value)) {
                object_element.val('');
            } else {
                object_element.val(value.toString() + add_percentage);
            }

        }
    }
}
