<base data-base="<?php echo base_url(); ?>">
</base>
<input type="hidden" id="edit_return_id" value="<?php echo $edit_return_id; ?>">
<input type="hidden" id="warning" value="<?php echo lang('alert'); ?>">
<input type="hidden" id="a_error" value="<?php echo lang('error'); ?>">
<input type="hidden" id="ok" value="<?php echo lang('ok'); ?>">
<input type="hidden" id="cancel" value="<?php echo lang('cancel'); ?>">
<input type="hidden" id="view_invoice" value="<?php echo getPOSChecker("123","view_invoice"); ?>">
<input type="hidden" id="change_date" value="<?php echo getPOSChecker("123","change_date"); ?>">
<input type="hidden" id="change_delivery_address" value="<?php echo getPOSChecker("123","change_delivery_address"); ?>">
<input type="hidden" id="menu_not_permit_access" value="<?php echo lang('menu_not_permit_access'); ?>">
<input type="hidden" id="status_changed_successfully" value="<?php echo lang('status_changed_successfully'); ?>">
<section class="main-content-wrapper">

    <?php
if ($this->session->flashdata('exception')) {
    echo '<section class="alert-wrapper"><div class="alert alert-success alert-dismissible fade show"> 
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <div class="alert-body">
    <p><i class="m-right fa fa-check"></i>';
    echo escape_output($this->session->flashdata('exception'));unset($_SESSION['exception']);
    echo '</p></div></div></section>';
}
if ($this->session->flashdata('exception_1')) {

    echo '<section class="alert-wrapper">
    <div class="alert alert-danger alert-dismissible fade show"> 
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <div class="alert-body">
    <p><i class="m-right fa fa-times"></i>';
    echo escape_output($this->session->flashdata('exception_1'));unset($_SESSION['exception_1']);
    echo '</p></div></div></section>';
}
?>
    <section class="content-header">
        <div class="row">
            <div class="col-sm-12 col-md-8">
                <h2 class="top-left-header"><?php echo lang('sale'); ?> </h2>
            </div>
            <?php if(isServiceAccessOnlyLogin('sGmsJaFJE')): ?>
            <div class="col-sm-12 col-md-4">

            </div>
                <?php else:
                $export_daily_sale = $this->session->userdata('export_daily_sale');
                if($export_daily_sale && $export_daily_sale=="enable"): ?>
            <div class="col-md-2">
                <a href="<?php echo base_url() ?>Sale/exportDailySales"  data-access="exportDailySales-123" class="btn_list m-right btn bg-blue-btn delete menu_assign_class"><?php echo lang('exportDailySales'); ?></a>
            </div>
            <div class="col-md-2">
                <a href="<?php echo base_url() ?>Sale/resetDailySales"  data-access="resetDailySales-123" class="btn_list m-right btn bg-blue-btn delete menu_assign_class"><?php echo lang('resetDailySales'); ?></a>
            </div>
            <div class="col-md-2">

            </div>
            <?php else: ?>
            <div class="col-md-offset-4 col-md-2">

            </div>

            <?php endif; endif; ?>
        </div>
    </section>

    <div class="box-wrapper">
        <div class="table-box">
            <!-- /.box-header -->
            <div class="table-responsive">
                <table id="datatable" class="table">
                    <thead>
                        <tr>
                            <th class="ir_w2_txt_center"><?php echo lang('sn'); ?></th>
                            <th class="ir_w_8"><?php echo lang('sale_no'); ?></th>
                            <th class="ir_w_8"><?php echo lang('order_type'); ?></th>
                            <th class="ir_w_12"><?php echo lang('date'); ?>(<?php echo lang('time'); ?>)</th>
                            <th class="ir_w_10"><?php echo lang('customer'); ?> (<?php echo lang('phone'); ?>)</th>
                            <th class="ir_w_8"><?php echo lang('total_payable'); ?></th>
                            <th class="ir_w_10"><?php echo lang('refund_amount'); ?></th>
                            <th class="ir_w_20"><?php echo lang('payment_method'); ?></th>
                            <th class="ir_w_10"><?php echo lang('added_by'); ?></th>
                            <th class="ir_w5_txt_center not-export-col"><?php echo lang('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>

                </table>
            </div>
            <!-- /.box-body -->
        </div>
    </div>

</section>


<!-- Modal -->
<div class="modal fade" id="change_date_modal" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel"><?php echo lang('change_date'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <i data-feather="x"></i>
        </button>
      </div>
      <div class="modal-body">
          <div class="form-group">
            <input type="hidden" name="sale_id_hidden" id="sale_id_hidden">
                <input name="change_date_sale" placeholder="<?php echo lang('date')?>" id="change_date_sale_modal"
                    class="ir_w100_height35x">
          </div>
      </div>
      <div class="modal-footer">
          <button type="button" class="btn bg-blue-btn"
                    id="save_change_date"><?php echo lang('save_changes'); ?></button>
                <button type="button" class="btn bg-red-btn" data-bs-dismiss="modal"
                    id="close_change_date_modal"><?php echo lang('close'); ?></button>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="change_delivery_address_update" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?php echo lang('change_delivery_address'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <input type="hidden" name="sale_id_hidden_d" id="sale_id_hidden_d">
                    <select class="form-control select2" name="status" id="status">
                        <option value="Pending"><?php echo lang('Pending'); ?></option>
                        <option value="Delivered"><?php echo lang('Delivered'); ?></option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn bg-blue-btn"
                        id="save_change_status"><?php echo lang('save_changes'); ?></button>
                <button type="button" class="btn bg-red-btn" data-bs-dismiss="modal"
                        id="close_change_date_modal"><?php echo lang('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="change_delivery_address" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel"><?php echo lang('change_delivery_address'); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
            <i data-feather="x"></i>
        </button>
      </div>
      <div class="modal-body">
          <div class="form-group">
            <input type="hidden" name="sale_id_hidden_d" id="sale_id_hidden_d">
                <select class="form-control select2" name="status" id="status">
                    <option value="Pending"><?php echo lang('Pending'); ?></option>
                    <option value="Delivered"><?php echo lang('Delivered'); ?></option>
                </select>
          </div>
      </div>
      <div class="modal-footer">
          <button type="button" class="btn bg-blue-btn"
                    id="save_change_status"><?php echo lang('save_changes'); ?></button>
                <button type="button" class="btn bg-red-btn" data-bs-dismiss="modal"
                    id="close_change_date_modal"><?php echo lang('close'); ?></button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="refund_modal" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="" id="exampleModalLabel"><?php echo lang('refund_items'); ?> (<?php echo lang('refund'); ?> <?php echo lang('date'); ?>: <span class="refund_date"></span>)</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                        <tr>
                            <th><?php echo lang('food_menu'); ?>(<?php echo lang('code'); ?>)</th>
                            <th><?php echo lang('quantity'); ?></th>
                            <th><?php echo lang('price'); ?></th>
                            <th><?php echo lang('vat'); ?></th>
                            <th><?php echo lang('discount'); ?></th>
                            <th><?php echo lang('refund_qty'); ?></th>
                            <th><?php echo lang('refund_amount'); ?></th>
                        </tr>
                        </thead>
                        <tbody id="sale_refund_cart">

                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn bg-red-btn" data-bs-dismiss="modal"
                ><?php echo lang('close'); ?></button>
            </div>
        </div>
    </div>
</div>


<!-- Email Invoice: send / resend one bill (Email_invoice/send, permission 443 send) -->
<div class="modal fade" id="ei_send_modal" aria-labelledby="ei_send_title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ei_send_title"><?php echo lang('ei_email_invoice'); ?> <span id="ei_sale_no"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    <i data-feather="x"></i>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="ei_sale_id" value="">
                <input type="hidden" id="ei_csrf" value="<?php echo escape_output(ai_csrf_token()); ?>">
                <label for="ei_email"><?php echo lang('ei_customer_email'); ?></label>
                <input type="email" id="ei_email" class="form-control" maxlength="150" placeholder="<?php echo lang('ei_email_placeholder'); ?>">
                <div id="ei_last" class="mt-2" style="font-size:13px;color:#666;"></div>
                <div id="ei_send_result" class="mt-2" style="font-size:14px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn bg-blue-btn" id="ei_send_btn"><?php echo lang('ei_send'); ?></button>
                <button type="button" class="btn bg-red-btn" data-bs-dismiss="modal"><?php echo lang('close'); ?></button>
            </div>
        </div>
    </div>
</div>
<script>
(function ($) {
    var base = $("#base_url_").val();
    var txt = {
        sending: <?php echo json_encode(lang('ei_sending'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        network: <?php echo json_encode(lang('ei_network_error'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        invalid: <?php echo json_encode(lang('ei_invalid_email'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
        last: <?php echo json_encode(lang('ei_last_email'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
    };
    function show(el, text, ok) {
        $(el).text(text || '').css('color', ok === false ? '#991b1b' : (ok ? '#166534' : '#666'));
    }
    $(document).on("click", ".ei_email_invoice", function () {
        var id = $(this).attr("data-id");
        $("#ei_sale_id").val(id);
        $("#ei_email").val("");
        $("#ei_sale_no").text("");
        show("#ei_last", "");
        show("#ei_send_result", "");
        $("#ei_send_btn").prop("disabled", false);
        $("#ei_send_modal").modal("show");
        $.ajax({ url: base + "Email_invoice/saleInfo", data: { sale_id: id }, dataType: "json" })
            .done(function (res) {
                if (!res.ok) { show("#ei_send_result", res.error, false); return; }
                $("#ei_sale_no").text(res.sale_no);
                if (res.email) { $("#ei_email").val(res.email); }
                if (res.last) {
                    show("#ei_last", txt.last + ": " + res.last.to_email + " - " + res.last.status_text + " (" + res.last.at + ")" + (res.last.error && res.last.status !== "sent" ? " - " + res.last.error : ""));
                }
            })
            .fail(function () { show("#ei_send_result", txt.network, false); });
    });
    $(document).on("click", "#ei_send_btn", function () {
        var email = $.trim($("#ei_email").val());
        if (!/^[^\s@,;<>]+@[^\s@,;<>]+\.[^\s@,;<>]+$/.test(email)) {
            show("#ei_send_result", txt.invalid, false);
            return;
        }
        var btn = $(this).prop("disabled", true);
        show("#ei_send_result", txt.sending);
        $.ajax({
            url: base + "Email_invoice/send", method: "POST", dataType: "json",
            data: { sale_id: $("#ei_sale_id").val(), email: email, ai_csrf: $("#ei_csrf").val() }
        }).done(function (res) {
            show("#ei_send_result", res.ok ? res.msg : res.error, !!res.ok);
        }).fail(function (xhr) {
            var res = xhr.responseJSON;
            show("#ei_send_result", res && res.error ? res.error : txt.network, false);
        }).always(function () { btn.prop("disabled", false); });
    });
})(jQuery);
</script>

<!-- <script src="<?php echo base_url(); ?>assets/datatable_custom/jquery-3.3.1.js"></script> -->
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jquery.dataTables.min.js"></script>
<script src="<?php echo base_url(); ?>assets/bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js">
</script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/dataTables.buttons.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.html5.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/buttons.print.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/jszip.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/pdfmake.min.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/js/dataTable/vfs_fonts.js"></script>
<script src="<?php echo base_url(); ?>frequent_changing/newDesign/js/forTable.js"></script>

<script type="text/javascript" src="<?php echo base_url(); ?>assets/POS/js/jquery.cookie.js"></script>
<script type="text/javascript" src="<?php echo base_url(); ?>frequent_changing/js/sale.js"></script>