$(function () {
    "use strict";

    function show_hide_div() {
        let sos_enable_self_order = $("#sos_enable_self_order").val();

        if(sos_enable_self_order=="Yes"){
            $(".show_hide_div").show();
        }else{
            $(".show_hide_div").hide();
        }
    }
    $(document).on('change','#sos_enable_self_order',function(){
        show_hide_div();
    });
    show_hide_div();
});