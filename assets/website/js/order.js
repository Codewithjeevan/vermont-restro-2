(function($) {
    'use strict';

    let base_url = $('#base_url').val();
    let precision = $('#precision').val();
    let currency = $('#currency').val();
    let ir_precision = 2;

    $(document).on('click', '.single_order', function(e){
        e.preventDefault();
        let food_menu_id = $(this).attr('data_single_order_id');
        let exist_check_food_menu_id;
        let exist_check = 'No';
        $('.cart-sidebar-item').each(function(){
            exist_check_food_menu_id = $(this).attr('data-order-cart-id');
            if(exist_check_food_menu_id == food_menu_id){
                exist_check = "Yes";
            }
        });
        $(".flaticon-shopping-bag").click();
        singleItemOrder(food_menu_id, exist_check);

    });

    function checkTaxApply(tax){
          let return_status = true;
        return return_status;
    }

    function get_total_vat() {
        let tax_object = {};
        let tax_name = [];
        $('.cart-sidebar-item').each(function(){
          let food_menu_id = $(this).attr('data-order-cart-id');
          let item_total_price = parseFloat($(this).find(".subtotal_cal").attr("data-inline_total")).toFixed(ir_precision);
          let menu_details = search_by_menu_id(food_menu_id, window.items);
          let tax_information = JSON.parse(menu_details[0].tax_information);
          if (tax_information.length > 0) {
              for(let k in tax_information){ 
                  if(tax_name.includes(tax_information[k].tax_field_name) && checkTaxApply(tax_information[k].tax_field_name)){
                      let previous_value = tax_object["" + tax_information[k].tax_field_name];
                      let current_value  = 0;
                      let tax_type = Number($("#tax_type").val());
                      if(tax_type==1){
                          current_value = parseFloat((parseFloat(parseFloat(tax_information[k].tax_field_percentage)*parseFloat(item_total_price))/parseFloat(100)));
                      }else{
                          current_value = (parseFloat(item_total_price) - (parseFloat(item_total_price)/(1+(tax_information[k].tax_field_percentage/100)))).toFixed(ir_precision);
                      }
    
                      tax_object["" + tax_information[k].tax_field_name] = (parseFloat(previous_value)+ Number(current_value)).toFixed(ir_precision);
                  }else{
                      if(checkTaxApply(tax_information[k].tax_field_name)){
                          tax_name.push(tax_information[k].tax_field_name);
                          let current_value  = 0;
                          let tax_type = Number($("#tax_type").val());
    
                          if(tax_type==1){
                              current_value = parseFloat((parseFloat(parseFloat(tax_information[k].tax_field_percentage)*parseFloat(item_total_price))/parseFloat(100)));
                          }else{
                              current_value = (parseFloat(item_total_price) - parseFloat(item_total_price)/(1+(tax_information[k].tax_field_percentage/100))).toFixed(ir_precision);
                          }
                          tax_object["" + tax_information[k].tax_field_name] = Number(current_value).toFixed(ir_precision);
                      }
    
                  }
              }
          }
         
          $(this).find('.cart-sidebar-item-meta span').each(function(){
            let modifier_id = $(this).attr('data-id');
            let item_id_custom = food_menu_id;
            let item_total_price = $(this).attr('data-total_price');
            let modifier_details = search_by_modifer_id(modifier_id, window.only_modifiers);
           if(modifier_details[0].tax_information){
            let tax_information = JSON.parse(modifier_details[0].tax_information);
            if (tax_information.length > 0) {
                for(let k in tax_information){
                    if(tax_name.includes(tax_information[k].tax_field_name)  && checkTaxApply(tax_information[k].tax_field_name)){
                        let previous_value = tax_object["" + tax_information[k].tax_field_name];
                        let current_value  = 0;
                        let tax_type = Number($("#tax_type").val());
                        if(tax_type==1){
                            current_value = parseFloat((parseFloat(parseFloat(tax_information[k].tax_field_percentage)*parseFloat(item_total_price))/parseFloat(100)));
                        }else{
                            current_value = (parseFloat(item_total_price) - (parseFloat(item_total_price)/(1+(tax_information[k].tax_field_percentage/100)))).toFixed(ir_precision);
                        }
                        tax_object["" + tax_information[k].tax_field_name] = (parseFloat(previous_value)+ Number(current_value)).toFixed(ir_precision);
                    }else{
                        if(checkTaxApply(tax_information[k].tax_field_name)){
                        tax_name.push(tax_information[k].tax_field_name);
                        let current_value  = 0;
                        let tax_type = Number($("#tax_type").val());
      
                        if(tax_type==1){
                            current_value = parseFloat((parseFloat(parseFloat(tax_information[k].tax_field_percentage)*parseFloat(item_total_price))/parseFloat(100)));
                        }else{
                            current_value = (parseFloat(item_total_price) - parseFloat(item_total_price)/(1+(tax_information[k].tax_field_percentage/100))).toFixed(ir_precision);
                        }
                        tax_object["" + tax_information[k].tax_field_name] = (Number(current_value)).toFixed(ir_precision);
                      }
                    }
                }
            }
           }
          
          });

        });
 
        let collect_tax = $("#collect_tax").val();
        let vat_amount = collect_tax == "Yes" ? tax_object : null;
        
        let html_modal = "";
        $.each(vat_amount, function (key, value) {
          let row_id = 1;
          let key_value = key;
          html_modal +=
            "<tr class='tax_field' data-tax_field_id='" +
            row_id +
            "'  data-tax_field_type='" +
            key_value +
            "'  data-tax_field_amount='" +
            value +
            "'>\n" +
            "                            <td>" +
            key_value +
            "</td>\n" +
            "                            <td>" +
            value +
            "</td>\n" +
            "                        </tr>";
        });
        $("#tax_row_show").html(html_modal);
      }


    function setCheckOutCartItem(){
        let checkOutHtml = '';
        $('.cart-sidebar-item').each(function(){
            let food_menu_id = $(this).attr('data-order-cart-id');
            let name = $(this).attr('data-name');
            let price = Number($(this).attr('data-price'));
            let qty = Number($(this).attr('data-qty'));
            let modifier_html = '';
            let modifier_sum = 0;

            $(this).find('.cart-sidebar-item-meta span').each(function(){
                let modifier_id = $(this).attr('data-id');
                let total_price = $(this).attr('data-total_price');
                    modifier_sum+= Number(total_price);
                let modifier_name = $(this).attr('data-name');
                modifier_html += `<span data-id="`+modifier_id+`" data-total_price="`+total_price+`" data-name="`+modifier_name+`" class="d-block modifier_div">${modifier_name} (${(Number(total_price)).toFixed(precision)})</span>`;
            });
            
            checkOutHtml += `
            <tr>
                <td data-title="Product">
                    <div class="cart-product-wrapper">
                        <div class="cart-product-body">
                            <h6> <a class="row_cart" data-id="`+food_menu_id+`" href="${base_url+'Frontend/menuItemDetails/'+food_menu_id}">${name}</a> </h6>
                            <div class="checkout_modifier">
                            ${modifier_html}
                            </div>
                        </div>
                    </div>
                </td>
                <td data-title="Quantity">x ${qty}</td>
                <td data-title="Total"><strong data-inline_total="`+((Number(qty) * Number(price)))+`" class="checkout_single_subtotal checkout_single_subtotal_${food_menu_id}">${ parseFloat(Number(modifier_sum) + (Number(qty) * Number(price))).toFixed(precision)}</strong> </td>
            </tr>`;
        });

        localStorage['checkout_cart_html'] = checkOutHtml;
        get_total_vat();
    }
    setTimeout(function(){
        get_total_vat();
    }, 1500);
    function singleItemOrder(food_order_id, exist_check){
        let quantity = $(`.item_details_qty_${food_order_id}`).val();
        $.ajax({
            type: "POST",
            url: base_url+"Frontend/singleItemOrder",
            data: {
                food_id:food_order_id
            },
            dataType: "json",
            success: function (response) {
                let html_content = '';
                if(response.status == 'success'){

                    let isChecked;
                    let modifier_id = 0;
                    let modifier_name = '';
                    let modifier_price = 0;
                    let modifier_html = '';
                    let modifier_sum = 0;
                    $('.modifier_checkbox').each(function() {
                        isChecked = $(this).is(':checked');
                        if(isChecked){
                            modifier_id = $(this).closest('.customize-variation-item').data('id');
                            modifier_name = $(this).closest('.customize-variation-item').data('name');
                            modifier_price = $(this).closest('.customize-variation-item').data('price');
                            modifier_price = Number(quantity) * Number(modifier_price);
                            modifier_html += `<span data-id="`+modifier_id+`" data-total_price="`+modifier_price+`" data-name="`+modifier_name+`" class="d-block modifier_div">${modifier_name} (${(modifier_price).toFixed(precision)})</span>`;
                            modifier_sum += Number(modifier_price);
                        }
                    });

                 
                    if(exist_check == 'Yes'){
                        $(`.checkout_single_subtotal_${response.data.id}`).text(`${ parseFloat(Number(modifier_sum) + (Number(quantity) * Number(response.data.sale_price))).toFixed(precision)}`);
                    }

                    if(exist_check == 'Yes'){
                        $(`.food-qty-price-${response.data.id}`).text(`${quantity} X ${response.data.sale_price}`);
                        $(`.cart-sidebar-price-${response.data.id}`).text(`${ parseFloat(Number(modifier_sum) + (Number(quantity) * Number(response.data.sale_price))).toFixed(precision)}`);
                        $('.cart-sidebar-item-meta').html('').html(modifier_html);
                    }else{
                        html_content += `
                        <div class="cart-sidebar-item" data-name="`+response.data.name+`" data-price="`+response.data.sale_price+`" data-qty="`+Number(quantity)+`"  data-order-cart-id="${food_order_id}">
                            <div class="media">
                                <a href="${base_url+'Frontend'+response.data.id+'/'+response.data.category_id}"><img src="${base_url+'images/'+response.data.photo}" alt="${response.data.name}"></a>
                                <div class="media-body">
                                    <h5> <a href="${base_url+'Frontend'+response.data.id+'/'+response.data.category_id}" title="${response.data.name}">${response.data.name}</a> </h5>
                                    <span class="food-qty-price-${response.data.id}">${Number(quantity)} x ${parseFloat(response.data.sale_price).toFixed(precision)}</span>
                                </div>
                            </div>
                            <div class="cart-sidebar-item-meta">
                                ${modifier_html}
                            </div>
                            <div class="cart-sidebar-price">
                                <span>${currency}</span>
                                <span data-inline_total="`+((Number(quantity) * Number(response.data.sale_price)))+`" class="subtotal_cal cart-sidebar-price-${response.data.id}">${ parseFloat(Number(modifier_sum) + (Number(quantity) * Number(response.data.sale_price))).toFixed(precision)}</span>
                            </div>
                            <div class="close-btn single-item-remove">
                                <span></span>
                                <span></span>
                            </div>
                        </div>`;
                        $('#order_html_render').append(html_content);
                    }
                    setTimeout(function(){
                        storageCartDataInLocal()
                        setCheckOutCartItem();
                        subtotalCal()
                        cartItemCalculate()
                    }, 1000);
                }
            }
        });

    }
    
    $(document).on('click', '.single-item-remove', function(){
        $(this).parent().remove();
        subtotalCal();
        cartItemCalculate();
        storageCartDataInLocal();
        checkoutStorageCartDataInLocal();
        setCheckOutCartItem();
    });
  

    function subtotalCal(){
        let subtotal = 0;
        $('.subtotal_cal').each(function(){
            subtotal += parseFloat($(this).text());
        });
        $('.cart-total').text(`${currency} ${(subtotal).toFixed(precision)}`);
    }


    function cartItemCalculate(){
        let cart_length = $('.cart-sidebar-item').length;
        $('.cart-item-count').text(cart_length);
    }



    function storageCartDataInLocal(){
        localStorage['cart_html'] = $("#order_html_render").html();
        setCheckOutCartItem();
    }
    
    let local_cart_data = localStorage['cart_html'];
    if(local_cart_data){
        $("#order_html_render").html(local_cart_data);
        subtotalCal();
        cartItemCalculate();
    }

    let checkout_cart_html = localStorage['checkout_cart_html'];
    if(checkout_cart_html){
        $("#checkout_table tbody").html(checkout_cart_html);
        subtotalCal();
        cartItemCalculate();
    }



    function checkoutCalculation(){
        let subtotal = 0;
        let grandTotal = 0;
        $('.checkout_single_subtotal').each(function(){
            subtotal = parseFloat($(this).text());
            grandTotal += subtotal;
        });
        $('.checkout_grand_total').text((parseFloat(grandTotal)).toFixed(precision));
    }
    setTimeout(function(){
        checkoutCalculation();
    }, 100);

    function isEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
      }
      function getPadTwo (str) {
        str = str.toString();
        return (str.length < 3 ? getPadTwo("0" + str, 3) : str);
    }
      function generateSaleNo() {
        //for date and time
        let today = new Date();
        let dd = today.getDate();
        let mm = today.getMonth() + 1; //January is 0!
        let yyyy = today.getFullYear();
        let twoDigitYear = yyyy. toString(). substr(-2);
        if (dd < 10) {
            dd = "0" + dd;
        }
        if (mm < 10) {
            mm = "0" + mm;
        }
        let time_a = new Date();
        let t_h = time_a.getHours();
        let t_m = time_a.getMinutes();
        let t_s = time_a.getSeconds();

        if (t_h < 10) {
            t_h = "0" + t_h;
        }
        if (t_m < 10) {
            t_m = "0" + t_m;
        }
        if (t_s < 10) {
            t_s = "0" + t_s;
        }
        let hidden_customer_name = $("#hidden_customer_name").val();
        let invoice_counter_value_tmp = Number(localStorage['invoice_counter_value'])?Number(localStorage['invoice_counter_value']):0;
        let invoice_counter_value = invoice_counter_value_tmp+1;
        localStorage['invoice_counter_value'] = invoice_counter_value;
        let sale_no = hidden_customer_name+twoDigitYear+mm+dd+"-"+getPadTwo(invoice_counter_value);
        return sale_no;
    }
    function getDateTime() {
        //for date and time
        let today1 = new Date();
        let dd1 = today1.getDate();
        let mm1 = today1.getMonth() + 1; //January is 0!
        let yyyy = today1.getFullYear();
        if (dd1 < 10) {
            dd1 = "0" + dd1;
        }
        if (mm1 < 10) {
            mm1 = "0" + mm1;
        }
        let time_a = new Date().toLocaleTimeString();
        let today_date = yyyy + "-" + mm1 + "-" + dd1;
        let date_time = today_date + " " + time_a;
        return [date_time,time_a];
    }
    function getRandomCode(length) {
        let result           = '';
        //this is random character pattern
        let characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        let charactersLength = characters.length;
        for ( let i = 0; i < length; i++ ) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }
    function search_by_menu_id(menu_id,myArray){
        let foundResult=new Array();
        for (let i=0; i < myArray.length; i++) {
            if (Number(myArray[i].item_id) ===  Number(menu_id)) {
                foundResult.push(myArray[i]);
            }
        }
        return foundResult.sort();
    }
    function search_by_modifer_id(menu_modifier_id,myArray){
        let foundResult=new Array();
        for (let i=0; i < myArray.length; i++) {
            if (Number(myArray[i].menu_modifier_id) ===  Number(menu_modifier_id)) {
                foundResult.push(myArray[i]);
            }
        }
        return foundResult.sort();
    }
    
   

    $(document).on('click', '.pay_now', function(){
         $(this).addClass("no_access");
        
         let fname = $("#fname").val();
         let phone = $("#phone").val();
         let email = $("#email").val();
         let delivery_area = $("#delivery_area").val();
         var outlet_id = $('#delivery_area').find(":selected").attr("data-outlet_id");
          
         let status = true;

         if(fname==""){
            status = false;
            $("#fname").css("border","1px solid red");
            $("#fname").focus();
         }else if(phone==""){
            status = false;
            $("#phone").css("border","1px solid red");
            $("#phone").focus();
         }else if(delivery_area==""){
            status = false;
            $("#delivery_area").css("border","1px solid red");
            $("#delivery_area").focus();
         }else if(!isEmail(email)){
            status = false;
            $("#email").css("border","1px solid red");
            $("#email").focus();
         }
         if(status==true){

            let sale_no_new = 0;
            let random_code = '';
            sale_no_new = generateSaleNo();
            random_code = getRandomCode(15);

            let order_status = 1;
            let rounding_amount_hidden = 0;
            let customer_current_due = 0;
            let token_number = '';
            let hidden_given_amount = 0;
            let hidden_change_amount = 0;
            let counter_id = 0;
            let customer_id = $("#hidden_customer_id").val();
            let open_invoice_date_hidden = $("#open_invoice_date_hidden").val();
            let total_items_in_cart = $(".cart-item-count").text();
            let total_items_in_cart_qty = 0;

            $('.cart-sidebar-item').each(function(){
                let this_qty = Number($(this).attr('data-qty'));
                total_items_in_cart_qty+=this_qty;
            });
            let total_amount = $(".checkout_grand_total").text();
            let total_vat = 0;
            let outlet_id_indexdb = outlet_id;
            let order_info = "{";
            order_info += '"sale_no":"' + sale_no_new + '",';
            order_info += '"outlet_id":"' + outlet_id_indexdb + '",';
            order_info += '"waiter_app_status":"",';
            order_info += '"hidden_given_amount":"' + hidden_given_amount + '",';
            order_info += '"hidden_change_amount":"' + hidden_change_amount + '",';
            order_info += '"counter_id":"' + counter_id + '",';
            order_info += '"random_code":"' + random_code + '",';
            order_info += '"token_number":"' + token_number + '",';
            order_info += '"customer_id":"' + customer_id + '",';
            order_info += '"customer_address":"' + delivery_area+ '",';
            order_info += '"customer_gst_number":"",';
            order_info += '"status":"Pending",';
            order_info += '"user_name":"",';
            order_info += '"user_id":"",';
            order_info += '"customer_name":"' + fname + '",';
            order_info += '"delivery_partner_id":"",';
            order_info += '"self_order_table_id":"",';
            order_info += '"self_order_table_person":"",';
            order_info += '"rounding_amount_hidden":"' + rounding_amount_hidden + '",';
            order_info += '"previous_due_tmp":"' + customer_current_due + '",';
            order_info += '"waiter_id":"",';
            order_info += '"waiter_name":"",';
            order_info += '"open_invoice_date_hidden":"' + open_invoice_date_hidden + '",';
            order_info += '"total_items_in_cart":"' + total_items_in_cart + '",';
            order_info += '"total_items_in_cart_qty":"' + total_items_in_cart_qty + '",';
            order_info += '"sub_total":"' + total_amount + '",';
            order_info +='"sale_date":"' + open_invoice_date_hidden + '",';
            order_info +='"date_time":"' + getDateTime()[0] + '",';
            order_info +='"order_time":"' + getDateTime()[1] + '",';
            order_info += '"charge_type":"",';
            order_info += '"total_vat":"' + total_vat + '",';
            order_info += '"total_payable":"' + total_amount + '",';
            order_info +=
                '"total_item_discount_amount":"0",';
            order_info +=
                '"sub_total_with_discount":"",';
            order_info +=
                '"sub_total_discount_amount":"0",';
            order_info +=
                '"total_discount_amount":"0",';
            order_info += '"delivery_charge":"0",';
            order_info += '"tips_amount":"0",';
            order_info += '"delivery_charge_actual_charge":"0",';
            order_info += '"tips_amount_actual_charge":"0",';
            order_info +=
                '"sub_total_discount_value":"0",';
            order_info +=
                '"sub_total_discount_type":"",';
            order_info += '"order_type":"3",';
            order_info += '"order_status":"' + order_status + '",';

        
            let sale_vat_objects = [];
            $("#tax_row_show .tax_field").each(function (i, obj) {
                let tax_field_id = $(this).attr("data-tax_field_id");
                let tax_field_type = $(this).attr("data-tax_field_type");
                let tax_field_amount = $(this).attr("data-tax_field_amount");
                sale_vat_objects.push({
                    tax_field_id: tax_field_id,
                    tax_field_type: tax_field_type,
                    tax_field_amount: parseFloat(tax_field_amount).toFixed(ir_precision),
                });
            });


            order_info +='"sale_vat_objects":' + JSON.stringify(sale_vat_objects) + ",";

            let hidden_table_name = "";
            let hidden_table_id = "";
            let hidden_table_capacity = 1;

            let total_person = 0;

            let orders_table = "";
                orders_table += '"orders_table":';
                orders_table += "[";
                let x = 1;

                
                let orders_table_text = '';
                total_person = hidden_table_capacity;
                orders_table_text = hidden_table_name;
                orders_table +=
                    '{"table_id":"' + hidden_table_id + '", "persons":"' + hidden_table_capacity + '"}';
 

            let items_info = "";

            items_info += '"items":';
            items_info += "[";

            if ($(".cart-sidebar-item").length > 0) {
                let k = 1;
                $('.cart-sidebar-item').each(function(){

                    let item_id = $(this).attr('data-order-cart-id');
                    let menu_details = search_by_menu_id(item_id, window.items);
                    let item_name = $(this).attr('data-name');
                    let qty = Number($(this).attr('data-qty'));
                   
                    let item_vat = [];
                    let tax_information_tmp = JSON.parse(menu_details[0].tax_information);
 
                    if ((tax_information_tmp).length > 0) {
                        for(let k in tax_information_tmp){
                            item_vat.push({
                                tax_field_id: 1,
                                tax_field_type: tax_information_tmp[k].tax_field_name,
                                tax_field_amount: parseFloat(tax_information_tmp[k].tax_field_percentage).toFixed(ir_precision),
                            });

                        }
                    }
                  
 
                    let item_discount = 0;
                    let discount_type = "fixed";
                    
                    let item_previous_id = 0;
                    let item_cooking_done_time = "";
                    let item_cooking_start_time = "";
                    let item_cooking_status = "";
                    let item_type = "";
                    let item_price_without_discount = 0;
                    let item_unit_price = menu_details[0].price;
                    let item_quantity = qty;
                    let is_kot_print = "";
                    let tmp_qty = qty;
                    let p_qty = qty;
                    let item_price_with_discount = menu_details[0].price;
                    let item_discount_amount = 0;
                     
                    order_info +='"sale_vat_objects":' + JSON.stringify(sale_vat_objects) + ",";


                    items_info +=
                        '{"food_menu_id":"' +
                        item_id +
                        '", "is_print":"' + 1 +
                        '", "is_kot_print":"' + is_kot_print +
                        '", "menu_name":"' + item_name +
                        '", "kitchen_id":"", "kitchen_name":"", "is_free":"0", "rounding_amount_hidden":"0", "item_vat":' + JSON.stringify(item_vat) + ",";
                    items_info +=
                        '"menu_discount_value":"' +
                        item_discount +
                        '","discount_type":"' +
                        discount_type +
                        '","menu_price_without_discount":"' +
                        item_price_without_discount +
                        '",';
                    items_info +=
                        '"menu_unit_price":"' +
                        item_unit_price +
                        '","qty":"' +
                        item_quantity +
                        '","tmp_qty":"' +
                        tmp_qty +
                        '","p_qty":"' +
                        p_qty +
                        '",';
                    items_info +=
                        '"item_previous_id":"' +
                        item_previous_id +
                        '","item_cooking_done_time":"' +
                        item_cooking_done_time +
                        '",';
                    items_info +=
                        '"item_cooking_start_time":"' +
                        item_cooking_start_time +
                        '","item_cooking_status":"' +
                        item_cooking_status +
                        '","item_type":"' +
                        item_type +
                        '",';
                    items_info +=
                        '"menu_price_with_discount":"' +
                        item_price_with_discount +
                        '","item_discount_amount":"' +
                        item_discount_amount +
                        '"';
 
                    let modifiers_id = "";
                    let modifiers_name = "";
                    let modifiers_price = "";

                    let iii = 0;
                    let modifier_vat = "";
                    let total_row = ($(this).find('.cart-sidebar-item-meta span').length);

                    $(this).find('.cart-sidebar-item-meta span').each(function(){
                        let modifier_id = $(this).attr('data-id');
                        let total_price = $(this).attr('data-total_price');
                        let modifier_name = $(this).attr('data-name');
                        let modifier_details = search_by_modifer_id(modifier_id, window.only_modifiers);
                        
                        let item_vat_m = [];
                        let tax_information_tmp1 = JSON.parse(modifier_details[0].tax_information);
                            if ((tax_information_tmp1).length > 0) {
                                for(let k in tax_information_tmp1){
                                    item_vat_m.push({
                                        tax_field_id: 1,
                                        tax_field_type: tax_information_tmp1[k].tax_field_name,
                                        tax_field_amount: parseFloat(tax_information_tmp1[k].tax_field_percentage).toFixed(ir_precision),
                                    });

                                }
                            }
                           
                        if (iii == total_row) {
                            modifiers_id += modifier_id+ ",";
                            modifiers_name += modifier_name+ ",";
                            modifiers_price += total_price+ ",";
                            modifier_vat += item_vat_m + "|||";
                        } else {
                            modifiers_id += modifier_id;
                            modifiers_name += modifier_name;
                            modifiers_price += total_price;
                            modifier_vat += item_vat_m;
                        }
                        iii++;
                    });


                     modifier_vat = "";
                    items_info +=
                    ',"modifiers_id":"' +
                    modifiers_id  +
                    '", "modifiers_name":"' + modifiers_name+'", "modifiers_price":"' +
                    modifiers_price +
                    '", "modifier_vat":' +
                    JSON.stringify(modifier_vat);

                    items_info += ',"item_note":""';
                    items_info += ',"menu_combo_items":""';
                    items_info +=
                    k == $(".cart-sidebar-item").length ? "}" : "},";
                    k++;
                });
            }
            items_info += "]";
            order_info += items_info + "}";
      
            $.ajax({
                url: base_url + "Payment/add_kitchen_sale_by_ajax",
                method: "POST",
                dataType:"json",
                data: {
                    order: order_info,
                    is_self_order: "Yes",
                    close_order: 0,
                },
                success: function (data) {
                   if(data.status == true){
                    localStorage['cart_html'] = '';
                    localStorage['checkout_cart_html'] = '';
                    window.location.replace(data.payment_link_direct_url);
                   }else{
                        alert("Someting is wrong on online payment, please try again later!");
                   }
                },
                error: function () {
    
                },
            });


         }


    });
    
  
  })(jQuery);
  