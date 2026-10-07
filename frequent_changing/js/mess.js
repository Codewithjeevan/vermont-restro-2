/*
 * Mess module (admin pages). Loaded by views/mess/_top.php in the page body,
 * before any page that pulls in the second jQuery for DataTables, so the
 * jQuery captured here is the one that has select2 + datepicker.
 */
(function ($) {
  "use strict";

  var base_url = function () {
    return $("#mess_base_url").val() || "";
  };
  var precision = function () {
    var p = parseInt($("#mess_precision").val(), 10);
    return isNaN(p) ? 2 : p;
  };
  var num = function (v) {
    var n = parseFloat(String(v === undefined || v === null ? "" : v).replace(/,/g, ""));
    return isNaN(n) ? 0 : n;
  };
  var money = function (v) {
    return num(v).toFixed(precision());
  };
  var ymd = function (d) {
    var m = d.getMonth() + 1;
    var day = d.getDate();
    return d.getFullYear() + "-" + (m < 10 ? "0" : "") + m + "-" + (day < 10 ? "0" : "") + day;
  };
  var parseYmd = function (s) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(s || "").trim());
    if (!m) {
      return null;
    }
    return new Date(parseInt(m[1], 10), parseInt(m[2], 10) - 1, parseInt(m[3], 10));
  };
  var addDays = function (d, n) {
    var r = new Date(d.getTime());
    r.setDate(r.getDate() + n);
    return r;
  };
  var dayDiff = function (a, b) {
    return Math.round((b.getTime() - a.getTime()) / 86400000);
  };

  /* confirm before following a link or submitting a form */
  window.messConfirm = function (text, onYes) {
    if (typeof swal === "function") {
      swal(
        {
          title: $("#warning").val() ? $("#warning").val() + "!" : "",
          text: text,
          cancelButtonText: $("#cancel").val() || "Cancel",
          confirmButtonText: $("#ok").val() || "OK",
          confirmButtonColor: "#7367f0",
          showCancelButton: true,
        },
        function () {
          onYes();
        }
      );
    } else if (window.confirm(text)) {
      onYes();
    }
  };

  $(document).on("click", "a.mess-confirm", function (e) {
    e.preventDefault();
    var href = this.href;
    var text = $(this).attr("data-confirm") || ($("#mess_txt_are_you_sure").val() + "?");
    window.messConfirm(text, function () {
      window.location.href = href;
    });
  });

  $(document).on("submit", "form.mess-confirm-form", function (e) {
    var form = this;
    if (form.dataset.confirmed === "1") {
      return true;
    }
    e.preventDefault();
    window.messConfirm($(form).attr("data-confirm") || ($("#mess_txt_are_you_sure").val() + "?"), function () {
      form.dataset.confirmed = "1";
      form.submit();
    });
    return false;
  });

  /* +/- steppers */
  $(document).on("click", ".mess-stepper button[data-step]", function (e) {
    e.preventDefault();
    var $input = $(this).closest(".mess-stepper").find("input");
    var v = parseInt($input.val(), 10) || 0;
    v = Math.max(0, v + parseInt($(this).attr("data-step"), 10));
    $input.val(v).trigger("change");
  });

  /* toggles (pause form etc.) */
  $(document).on("click", "[data-mess-toggle]", function (e) {
    e.preventDefault();
    $($(this).attr("data-mess-toggle")).toggleClass("open");
  });

  /* copy text to the clipboard */
  $(document).on("click", "[data-mess-copy]", function (e) {
    e.preventDefault();
    var text = $($(this).attr("data-mess-copy")).val() || $($(this).attr("data-mess-copy")).text();
    var done = function () {
      var $b = $(e.currentTarget);
      var old = $b.html();
      $b.html($("#mess_txt_copied").val() || "Copied");
      setTimeout(function () {
        $b.html(old);
      }, 1500);
    };
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(done);
    } else {
      var $t = $("<textarea>").val(text).css({ position: "fixed", left: "-9999px" }).appendTo("body");
      $t[0].select();
      try {
        document.execCommand("copy");
        done();
      } catch (err) {}
      $t.remove();
    }
  });

  /* client side table filter: <input data-mess-filter="#table"> */
  $(document).on("input", "input[data-mess-filter]", function () {
    var q = $(this).val().toLowerCase().trim();
    $($(this).attr("data-mess-filter")).find("[data-search]").each(function () {
      var hit = !q || $(this).attr("data-search").toLowerCase().indexOf(q) > -1;
      $(this).toggle(hit);
    });
  });

  /* ============================================================
   * Member form: warn when the phone is already used
   * ============================================================ */
  $(document).on("blur", "#mess_member_form input[name=phone]", function () {
    var phone = $(this).val();
    var $box = $("#mess_phone_warning");
    if (!phone || phone.replace(/\D/g, "").length < 6) {
      $box.hide();
      return;
    }
    $.getJSON(base_url() + "Mess/checkPhoneAjax", { phone: phone, except_id: $("#mess_member_id").val() || 0 }, function (r) {
      if (r && r.used_by && r.used_by.length) {
        $box.find("span").text(r.used_by.join(", "));
        $box.show();
      } else {
        $box.hide();
      }
    });
  });

  /* ============================================================
   * Subscription form
   * ============================================================ */
  var Sub = {
    $f: null,
    agreedTouched: false,
    init: function () {
      this.$f = $("#mess_subscription_form");
      if (!this.$f.length) {
        return;
      }
      var self = this;
      self.agreedTouched = self.$f.find("[name=agreed_price]").val() !== "";
      self.$f.on("change", "#plan_id", function () {
        self.applyPlan();
      });
      self.$f.on("change changeDate", "[name=start_date]", function () {
        self.fillEnd();
      });
      self.$f.on("change input", "#cycle_days", function () {
        self.fillEnd();
      });
      self.$f.on("change changeDate", "[name=end_date]", function () {
        self.syncDays();
      });
      self.$f.on("change input", ".mess-persons", function () {
        self.calc();
      });
      self.$f.on("input", "[name=agreed_price]", function () {
        self.agreedTouched = $(this).val() !== "";
      });
      self.$f.on("click", "#mess_use_suggested", function (e) {
        e.preventDefault();
        self.$f.find("[name=agreed_price]").val(money($("#mess_suggested_value").val()));
        self.agreedTouched = true;
      });
      self.$f.on("change", "#member_id", function () {
        self.loadMember();
      });
      self.$f.on("change", "[name=delivery_mode]", function () {
        $("#mess_address_row").toggle($(this).val() === "delivery");
      });
      $("#mess_address_row").toggle(self.$f.find("[name=delivery_mode]").val() === "delivery");
      if (self.$f.attr("data-new") === "1" && $("#member_id").val()) {
        self.loadMember(true);
      }
      self.syncDays();
      self.calc();
    },
    plan: function () {
      var $o = $("#plan_id option:selected");
      if (!$o.val()) {
        return null;
      }
      return {
        price: num($o.attr("data-price")),
        cycle: parseInt($o.attr("data-cycle"), 10) || 30,
        b: $o.attr("data-b") === "1",
        l: $o.attr("data-l") === "1",
        d: $o.attr("data-d") === "1",
        diet: $o.attr("data-diet"),
      };
    },
    applyPlan: function () {
      var p = this.plan();
      if (!p) {
        this.calc();
        return;
      }
      var set = function (name, on) {
        var $i = $("[name=" + name + "]");
        if (!$i.length) {
          return;
        }
        var v = parseInt($i.val(), 10) || 0;
        if (on && v < 1) {
          $i.val(1);
        }
        if (!on) {
          $i.val(0);
        }
      };
      set("persons_breakfast", p.b);
      set("persons_lunch", p.l);
      set("persons_dinner", p.d);
      if (p.diet) {
        this.$f.find("[name=diet]").val(p.diet).trigger("change.select2");
      }
      $("#cycle_days").val(p.cycle);
      this.fillEnd();
    },
    fillEnd: function () {
      var start = parseYmd(this.$f.find("[name=start_date]").val());
      var days = parseInt($("#cycle_days").val(), 10) || 0;
      if (start && days > 0) {
        this.$f.find("[name=end_date]").val(ymd(addDays(start, days - 1)));
      }
      this.calc();
    },
    syncDays: function () {
      var start = parseYmd(this.$f.find("[name=start_date]").val());
      var end = parseYmd(this.$f.find("[name=end_date]").val());
      if (start && end && end >= start) {
        var paused = parseInt(this.$f.attr("data-paused") || "0", 10);
        $("#cycle_days").val(dayDiff(start, end) + 1 - paused);
      }
      this.calc();
    },
    calc: function () {
      var persons = 0;
      $(".mess-persons").each(function () {
        persons += parseInt($(this).val(), 10) || 0;
      });
      var days = parseInt($("#cycle_days").val(), 10) || 0;
      var p = this.plan();
      var suggested = 0;
      var txt = "";
      if (p) {
        var meals = (p.b ? 1 : 0) + (p.l ? 1 : 0) + (p.d ? 1 : 0);
        var rate = meals && p.cycle ? p.price / (p.cycle * meals) : 0;
        suggested = rate * persons * days;
        txt = money(rate) + " x " + persons + " x " + days + " = ";
      }
      $("#mess_suggested_value").val(suggested);
      $("#mess_suggested_calc").text(txt);
      $("#mess_suggested_text").text(money(suggested));
      if (!this.agreedTouched && p) {
        this.$f.find("[name=agreed_price]").val(money(suggested));
      }
    },
    loadMember: function (keepFields) {
      var self = this;
      var id = $("#member_id").val();
      var $info = $("#mess_member_info");
      if (!id) {
        $info.hide();
        return;
      }
      $.getJSON(base_url() + "Mess/memberInfoAjax", { member_id: id }, function (r) {
        if (!r || !r.status) {
          $info.hide();
          return;
        }
        var bal = num(r.balance);
        $info
          .html(
            (r.phone ? r.phone + " &middot; " : "") +
              (bal > 0
                ? '<span class="mess-due">' + $info.attr("data-due") + " " + r.balance_text + "</span>"
                : bal < 0
                ? '<span class="mess-credit">' + $info.attr("data-credit") + " " + r.balance_text + "</span>"
                : "")
          )
          .show();
        if (self.$f.attr("data-new") !== "1" || keepFields === true && self.$f.attr("data-renew") === "1") {
          return;
        }
        self.$f.find("[name=diet]").val(r.diet).trigger("change.select2");
        self.$f.find("[name=delivery_mode]").val(r.delivery_mode).trigger("change");
        if (!self.$f.find("[name=delivery_address]").val()) {
          self.$f.find("[name=delivery_address]").val(r.address);
        }
        if (r.last_end_date && self.$f.attr("data-renew") !== "1") {
          var last = parseYmd(r.last_end_date);
          var today = parseYmd(self.$f.attr("data-today"));
          if (last && today && last >= today) {
            self.$f.find("[name=start_date]").val(ymd(addDays(last, 1)));
            self.fillEnd();
          }
        }
      });
    },
  };

  /* ============================================================
   * Payment form
   * ============================================================ */
  var Pay = {
    $f: null,
    balance: null,
    init: function () {
      this.$f = $("#mess_payment_form");
      if (!this.$f.length) {
        return;
      }
      var self = this;
      self.$f.on("change", "#member_id", function () {
        self.loadMember();
      });
      self.$f.on("change", "[name=type]", function () {
        self.typeChanged();
      });
      self.$f.on("change", "#subscription_id", function () {
        var due = num($(this).find("option:selected").attr("data-due"));
        // never suggest more than the member owes in total (discounts / advance sit outside the cycle)
        if (self.balance !== null && self.balance > 0 && due > self.balance) {
          due = self.balance;
        }
        if (due > 0 && !self.$f.find("[name=amount]").data("touched")) {
          self.$f.find("[name=amount]").val(money(due));
        }
      });
      self.$f.on("input", "[name=amount]", function () {
        $(this).data("touched", true);
      });
      self.typeChanged();
      if ($("#member_id").val()) {
        self.loadMember();
      }
    },
    typeChanged: function () {
      var t = this.$f.find("[name=type]").val();
      $("#mess_method_row").toggle(t !== "adjustment");
      $("#mess_direction_row").toggle(t === "adjustment");
    },
    loadMember: function () {
      var self = this;
      var id = $("#member_id").val();
      var $sel = $("#subscription_id");
      var pre = String(self.$f.attr("data-subscription") || "");
      if (pre === "0") {
        pre = "";
      }
      var $info = $("#mess_member_info");
      $sel.find("option:not(:first)").remove();
      if (!id) {
        $info.hide();
        return;
      }
      $.getJSON(base_url() + "Mess/memberInfoAjax", { member_id: id }, function (r) {
        if (!r || !r.status) {
          return;
        }
        var bal = num(r.balance);
        self.balance = bal;
        $info
          .html(
            bal > 0
              ? '<span class="mess-due">' + $info.attr("data-due") + " " + r.balance_text + "</span>"
              : bal < 0
              ? '<span class="mess-credit">' + $info.attr("data-credit") + " " + r.balance_text + "</span>"
              : $info.attr("data-zero")
          )
          .show();
        var firstDue = "";
        $.each(r.subscriptions || [], function (i, s) {
          $sel.append($("<option>").val(s.id).text(s.text).attr("data-due", s.due));
          if (num(s.due) > 0) {
            firstDue = String(s.id);
          }
        });
        var pick = pre && $sel.find('option[value="' + pre + '"]').length ? pre : firstDue;
        if (pick) {
          $sel.val(pick);
        }
        $sel.trigger("change");
        if (!pick && bal > 0 && !self.$f.find("[name=amount]").data("touched") && !self.$f.find("[name=amount]").val()) {
          self.$f.find("[name=amount]").val(money(bal));
        }
        pre = "";
        self.$f.attr("data-subscription", "");
      });
    },
  };

  /* ============================================================
   * Meal register: one tap = one AJAX call, only that row redraws
   * ============================================================ */
  var Reg = {
    busy: {},
    init: function () {
      if (!$("#mess_reg_list").length) {
        return;
      }
      var self = this;
      $(document).on("click", ".mess-mark", function (e) {
        e.preventDefault();
        self.send("Mess_daily/mark", { id: $(this).attr("data-id"), action: $(this).attr("data-action") }, $(this).attr("data-id"));
      });
      $(document).on("change", ".mess-partial", function () {
        var v = $(this).val();
        if (v === "") {
          return;
        }
        self.send("Mess_daily/mark", { id: $(this).attr("data-id"), action: "partial", persons: v }, $(this).attr("data-id"));
      });
      $(document).on("click", ".mess-flip", function (e) {
        e.preventDefault();
        self.send("Mess_daily/flipDiet", { id: $(this).attr("data-id") }, $(this).attr("data-id"));
      });
      $("#mess_reg_mode").on("click", "a", function (e) {
        e.preventDefault();
        $("#mess_reg_mode a").removeClass("on");
        $(this).addClass("on");
        self.mode = $(this).attr("data-mode");
        self.applyMode();
      });
    },
    mode: "",
    applyMode: function () {
      var m = this.mode;
      $("#mess_reg_list .mess-reg-row").each(function () {
        var $r = $(this);
        var hit = !m || (m === "pending" ? $r.attr("data-status") === "pending" : $r.attr("data-mode") === m);
        $r.toggleClass("d-none", !hit);
      });
    },
    send: function (url, data, id) {
      var self = this;
      if (self.busy[id]) {
        return;
      }
      self.busy[id] = true;
      var $row = $("#mess_att_" + id);
      $row.css("opacity", 0.55);
      $.ajax({
        url: base_url() + url,
        type: "POST",
        data: data,
        dataType: "json",
        timeout: 20000,
      })
        .done(function (r) {
          if (!r || !r.status) {
            $row.css("opacity", 1);
            if (r && r.msg) {
              window.alert(r.msg);
            }
            return;
          }
          var $new = $(r.html);
          $row.replaceWith($new);
          self.applyMode();
          var q = $("input[data-mess-filter='#mess_reg_list']").val();
          if (q) {
            $("input[data-mess-filter='#mess_reg_list']").trigger("input");
          }
          $.each(r.counts || {}, function (k, v) {
            $("#mess_reg_counts [data-count='" + k + "']").text(v);
          });
        })
        .fail(function () {
          $row.css("opacity", 1);
          window.alert("Network error, try again");
        })
        .always(function () {
          self.busy[id] = false;
        });
    },
  };

  /* ============================================================
   * Daily menu planner: add / remove dish rows, live planned portions
   * ============================================================ */
  var Menu = {
    init: function () {
      var $f = $("#mess_menu_form");
      if (!$f.length) {
        return;
      }
      var self = this;
      $f.on("click", ".mess-add-dish", function (e) {
        e.preventDefault();
        var $cell = $(this).closest(".mess-menu-cell");
        var tpl = $("#mess_dish_template").html();
        var idx = parseInt($cell.attr("data-next"), 10) || 0;
        $cell.attr("data-next", idx + 1);
        var html = tpl.replace(/__SLOT__/g, $cell.attr("data-slot")).replace(/__DIET__/g, $cell.attr("data-diet")).replace(/__I__/g, idx);
        var $row = $(html);
        $cell.find(".mess-dish-rows").append($row);
        self.initRow($row);
      });
      $f.on("click", ".mess-dish-row .x", function (e) {
        e.preventDefault();
        $(this).closest(".mess-dish-row").remove();
      });
      $f.on("change input", ".mess-dish-rows select, .mess-dish-rows input", function () {
        self.calcRow($(this).closest(".mess-dish-row"));
      });
      $f.find(".mess-dish-rows .mess-dish-row").each(function () {
        self.initRow($(this));
      });
    },
    initRow: function ($row) {
      var $sel = $row.find("select.mess-dish-food");
      if ($sel.select2) {
        $sel.select2({ width: "100%", tags: false });
      }
      this.calcRow($row);
    },
    calcRow: function ($row) {
      var $cell = $row.closest(".mess-menu-cell");
      var expected = num($cell.attr("data-expected"));
      var $sel = $row.find("select.mess-dish-food");
      var fid = $sel.val();
      var extras = 0;
      if (fid) {
        extras = num($cell.attr("data-extra-" + fid));
      }
      var portion = num($row.find(".mess-portion").val()) || 0;
      var buffer = num($row.find(".mess-buffer").val()) || 0;
      var total = expected * portion + buffer + extras;
      $row.find(".planned").text(total % 1 === 0 ? total : total.toFixed(2)).attr("title", extras ? "+" + extras + " extra" : "");
      $row.find(".mess-dish-name").toggle(!fid);
      var $opt = $sel.find("option:selected");
      $row.find(".mess-norecipe").toggle(!!fid && $opt.attr("data-recipe") === "0");
    },
  };

  /* ============================================================
   * Extra form: add item rows, price from the food menu, totals
   * ============================================================ */
  var Extra = {
    init: function () {
      var $f = $("#mess_extra_form");
      if (!$f.length) {
        return;
      }
      var self = this;
      $f.on("click", "#mess_add_item", function (e) {
        e.preventDefault();
        var idx = parseInt($f.attr("data-next"), 10) || 1;
        $f.attr("data-next", idx + 1);
        var $row = $($("#mess_item_template").html().replace(/__I__/g, idx));
        $("#mess_items").append($row);
        if ($row.find("select").select2) {
          $row.find("select").select2({ width: "100%" });
        }
      });
      $f.on("click", ".mess-item .x", function (e) {
        e.preventDefault();
        if ($("#mess_items .mess-item").length > 1) {
          $(this).closest(".mess-item").remove();
          self.total();
        }
      });
      $f.on("change", ".mess-item select", function () {
        var $row = $(this).closest(".mess-item");
        var $o = $(this).find("option:selected");
        if ($(this).val()) {
          $row.find(".mess-item-price").val(money($o.attr("data-price")));
        }
        $row.find(".mess-item-name").toggle(!$(this).val());
        self.total();
      });
      $f.on("input change", ".mess-item input", function () {
        self.total();
      });
      $f.on("change", "[name=settle_type]", function () {
        $("#mess_paynow_row").toggle($f.find("[name=settle_type]:checked").val() === "pay_now");
      });
      $f.find("[name=settle_type]:checked").trigger("change");
      $f.find(".mess-item select").each(function () {
        if ($(this).select2) {
          $(this).select2({ width: "100%" });
        }
        $(this).closest(".mess-item").find(".mess-item-name").toggle(!$(this).val());
      });
      self.total();
    },
    total: function () {
      var t = 0;
      $("#mess_items .mess-item").each(function () {
        var q = num($(this).find(".mess-item-qty").val());
        var p = num($(this).find(".mess-item-price").val());
        var line = q * p;
        $(this).find(".mess-item-total").text(money(line));
        t += line;
      });
      $("#mess_extra_total").text(money(t));
    },
  };

  /* ============================================================
   * Day close: leftover follows cooked until typed, live food cost
   * ============================================================ */
  var Close = {
    init: function () {
      var $f = $("#mess_close_form");
      if (!$f.length) {
        return;
      }
      var self = this;
      $f.on("input change", ".mess-cooked", function () {
        var $row = $(this).closest(".mess-close-row");
        var $left = $row.find(".mess-leftover");
        if (!$left.data("touched")) {
          var left = Math.max(0, num($(this).val()) - num($row.attr("data-served")));
          $left.val(Math.round(left * 100) / 100);
        }
        self.total();
      });
      $f.on("input", ".mess-leftover", function () {
        $(this).data("touched", true);
      });
      self.total();
    },
    total: function () {
      var t = 0;
      $("#mess_close_form .mess-close-row").each(function () {
        var cost = num($(this).attr("data-cost"));
        if (!cost) {
          return;
        }
        var line = num($(this).find(".mess-cooked").val()) * cost;
        $(this).find(".mess-rowcost").text(money(line));
        t += line;
      });
      $("#mess_close_total").text(money(t));
    },
  };

  $(function () {
    Sub.init();
    Pay.init();
    Reg.init();
    Menu.init();
    Extra.init();
    Close.init();
  });
})(jQuery);
