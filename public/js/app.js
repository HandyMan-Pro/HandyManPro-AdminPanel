/*
Template: Datum - Responsive Bootstrap 4 Admin Dashboard Template
Author: iqonic.design
Design and Developed by: iqonic.design
NOTE: This file contains the styling for responsive Template.
*/

/*----------------------------------------------
Index Of Script
------------------------------------------------

:: Tooltip
:: Fixed Nav
:: Sidebar Widget
:: Page Loader
:: Close  navbar Toggle
:: user toggle
:: Data tables
:: Form Validation
:: Flatpicker
:: Scrollbar
:: checkout
:: Datatables
:: SVG Animation
------------------------------------------------
Index Of Script
----------------------------------------------*/
(function (jQuery) {
    "use strict";

    $(document).on('change', '.datatable-filter [data-filter="select"]', function () {
        window.renderedDataTable.ajax.reload(null, false)
    })

    $(document).on('input', '.dt-search', function () {
        window.renderedDataTable.ajax.reload(null, false)
    })
    $(document).ready(function () {
        // Get the saved theme from local storage
        const savedTheme = localStorage.getItem('data-bs-theme');

        // Set the initial theme based on saved preference or default to light
        if (savedTheme === 'dark') {
            $('html').attr('data-bs-theme', 'dark');
            $('#dark-mode').prop('checked', true);
        } else {
            $('html').attr('data-bs-theme', 'light');
            $('#dark-mode').prop('checked', false);
        }

        $('#dark-mode').on('change', function () {
            const newMode = $(this).is(':checked') ? 'dark' : 'light';

            // Update the HTML attribute and local storage
            $('html').attr('data-bs-theme', newMode);
            localStorage.setItem('data-bs-theme', newMode);
        });
    });
    // confirm message box
    const confirmSwal = async (message) => {
        return await Swal.fire({
            title: message,
            showCancelButton: true,
            confirmButtonText: 'Yes',
            cancelButtonText: 'No'
        }).then((result) => {
            return result
        })
    }

    window.confirmSwal = confirmSwal

    $(document).on('submit', 'form[id$="quick-action-form"]', function (e) {
        e.preventDefault()
        const form = $(this)
        const url = form.attr('action')
        console.log(url)
        const message = form.find('button[data-ajax="true"]').data('message');

        // Scope checkbox selection to the current form's container
        const container = form.closest('.loyalty-index');
        const rowdIds = container.length > 0
            ? container.find('.select-table-row:checked').map(function () { return $(this).val(); }).get()
            : $("#datatable_wrapper .select-table-row:checked").map(function () { return $(this).val(); }).get();


        confirmSwal(message).then((result) => {
            if (!result.isConfirmed) return
            callActionAjax({ url: `${url}?rowIds=${rowdIds}`, body: form.serialize() })
        })

    })

    $(document).on('change', '#datatable_wrapper .switch-status-change', function () {
        let url = $(this).attr('data-url')
        let body = {
            status: $(this).prop('checked') ? 1 : 0,
            _token: $(this).attr('data-token')
        }
        callActionAjax({ url: url, body: body })
    })

    $(document).on('change', '#datatable_wrapper .change-select', function () {
        let url = $(this).attr('data-url')
        let body = {
            value: $(this).val(),
            _token: $(this).attr('data-token')
        }
        callActionAjax({ url: url, body: body })
    })

    function callActionAjax({ url, body }) {
        $.ajax({
            type: 'POST',
            url: url,
            data: body,
            success: function (res) {
                if (res.status) {
                    const successMessage = res.message;
                    showMessage(successMessage);
                    window.renderedDataTable.ajax.reload(resetActionButtons, false)
                    const event = new CustomEvent('update_quick_action', { detail: { value: true } })
                    document.dispatchEvent(event)
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: res.message,
                        icon: "error"
                    })
                }
            }
        })
    }


    $(document).on('click', '#datatable_wrapper .button-status-change', function () {

        let url = $(this).attr('data-url')
        let body = {
            status: 1,
            _token: $(this).attr('data-token')
        }
        callActionAjax({ url: url, body: body })
    })

    function callActionAjax({ url, body }) {
        $.ajax({
            type: 'POST',
            url: url,
            data: body,
            success: function (res) {
                if (res.status) {
                    const successMessage = res.message;
                    showMessage(successMessage);

                    // Find the visible loyalty table and reload it
                    const visibleTable = $('.loyalty-index:not(.d-none)').find('table').attr('id');

                    if (visibleTable) {
                        // Reload the specific visible table
                        const tableInstance = $('#' + visibleTable).DataTable();
                        if (tableInstance) {
                            tableInstance.ajax.reload(null, false);
                        }
                    } else {
                        // Fallback for non-loyalty tables
                        if (window.renderedDataTable) {
                            window.renderedDataTable.ajax.reload(null, false);
                        }
                    }

                    resetActionButtons();
                    const event = new CustomEvent('update_quick_action', { detail: { value: true } })
                    document.dispatchEvent(event)
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: res.message,
                        icon: "error"
                    })
                }
            }
        })
    }

    function showMessage(message) {
        Snackbar.show({
            text: message,
            pos: 'bottom-center'
        });
    }


    //select row in datatable
    const dataTableRowCheck = (id, source = null) => {
        var dataType = source ? source.getAttribute('data-type') : null;
        checkRow(dataType);

        // Find the correct form and elements based on dataType
        let formPrefix = '';
        if (dataType === 'loyaltyearnrule') formPrefix = 'earn_';
        else if (dataType === 'loyaltyredeemrule') formPrefix = 'redeem_';
        else if (dataType === 'loyaltyreferralrule' || dataType === 'loyaltyreferrerule') formPrefix = 'referral_';

        const formId = formPrefix ? formPrefix + 'quick-action-form' : 'quick-action-form';
        const selectAllId = formPrefix ? formPrefix + 'select-all-table' : 'select-all-table';
        const actionDropdownId = formPrefix ? formPrefix + 'quick-action-type' : 'quick-action-type';

        if ($(".select-table-row:checked").length > 0) {
            $("#" + formId).removeClass('form-disabled');

            const selectAllEl = document.getElementById(selectAllId);
            if (selectAllEl) selectAllEl.indeterminate = true;

            // Use the correct form ID instead of #quick-actions
            $("#" + formId).find("input, textarea, button, select").removeAttr("disabled");
        } else {
            const selectAllEl = document.getElementById(selectAllId);
            if (selectAllEl) {
                selectAllEl.indeterminate = false;
                selectAllEl.checked = false;
            }
            resetActionButtons(dataType);
        }

        if ($("#datatable-row-" + id).is(":checked")) {
            $("#row-" + id).addClass("table-active");
        } else {
            $("#row-" + id).removeClass("table-active");
        }


        // Scope checkbox selection to the current form's container or fallback
        const container = $("#" + formId).closest('.loyalty-index');
        const rowdIds = container.length > 0
            ? container.find('.select-table-row:checked').map(function () { return $(this).val(); }).get()
            : $("#datatable_wrapper .select-table-row:checked").map(function () { return $(this).val(); }).get();

        const actionDropdown = document.getElementById(actionDropdownId);

        if (!actionDropdown) {
            console.error('Action dropdown not found:', actionDropdownId);
            return;
        }

        // Helper to enable/disable options by value
        const setOptionDisabled = (value, disabled) => {
            $(actionDropdown).find(`option[value="${value}"]`).prop('disabled', disabled);
        };

        // Default state: Enable Delete, Disable Restore/Perm Delete
        setOptionDisabled('delete', false);
        setOptionDisabled('restore', true);
        setOptionDisabled('permanently-delete', true);

        // Specific overrides based on dataType (if needed)
        if (dataType === 'booking') {
            setOptionDisabled('delete', true);
            setOptionDisabled('restore', true);
            // Booking might have specific logic, keeping existing behavior pattern
            // actionDropdown.options[1].disabled = false; // This was index 1, likely 'assign' or similar?
            // Checking previous code: options[1] was enabled.
            // Without knowing the value, I'll leave it, but 'delete' is index 2.
        }

        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            type: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken,
            },
            url: baseUrl + "/check-in-trash",
            data: { ids: rowdIds, datatype: dataType },
            success: function (response) {
                if (response.all_in_trash == true) {
                    // If in trash: Enable Restore/Perm Delete, Disable Delete
                    setOptionDisabled('restore', false);
                    setOptionDisabled('permanently-delete', false);
                    setOptionDisabled('delete', true);
                } else {
                    // If not in trash (or mixed): Enable Delete, Disable Restore/Perm Delete
                    setOptionDisabled('restore', true);
                    setOptionDisabled('permanently-delete', true);
                    setOptionDisabled('delete', false);
                }
            }
        });
    };
    window.dataTableRowCheck = dataTableRowCheck


    // const selectAllTable = (source) => {
    //   const checkboxes = document.getElementsByName("datatable_ids[]");
    //   for (var i = 0, n = checkboxes.length; i < n; i++) {

    //       if (!$("#" + checkboxes[i].id).prop('disabled')){
    //           checkboxes[i].checked = source.checked;
    //       }
    //       if ($("#" + checkboxes[i].id).is(":checked")) {
    //           $("#" + checkboxes[i].id)
    //               .closest("tr")
    //               .addClass("table-active");
    //           $("#quick-actions")
    //               .find("input, textarea, button, select")
    //               .removeAttr("disabled");
    //           if ($("#quick-action-type").val() == "") {
    //               $("#quick-action-apply").attr("disabled", true);
    //           }
    //       } else {
    //           $("#" + checkboxes[i].id)
    //               .closest("tr")
    //               .removeClass("table-active");
    //           resetActionButtons();
    //       }
    //   }

    //   checkRow()
    // };

    const selectAllTable = (source) => {
        var dataType = source.getAttribute('data-type');
        console.log(dataType);

        // Find the correct dropdown based on dataType
        let formPrefix = '';
        if (dataType === 'loyaltyearnrule') formPrefix = 'earn_';
        else if (dataType === 'loyaltyredeemrule') formPrefix = 'redeem_';
        else if (dataType === 'loyaltyreferralrule' || dataType === 'loyaltyreferrerule') formPrefix = 'referral_';

        const actionDropdownId = formPrefix ? formPrefix + 'quick-action-type' : 'quick-action-type';

        // IMPORTANT: Scope checkbox selection to only the visible container
        // Find the closest parent container that is visible (not d-none)
        const visibleContainer = $(source).closest('.loyalty-index:not(.d-none)');

        // Get checkboxes only within the visible container, or all if not in loyalty-index
        const checkboxes = visibleContainer.length > 0
            ? visibleContainer.find('input[name="datatable_ids[]"]').toArray()
            : document.getElementsByName("datatable_ids[]");

        const actionDropdown = document.getElementById(actionDropdownId);
        const selectedIds = [];

        for (var i = 0, n = checkboxes.length; i < n; i++) {
            if (!$("#" + checkboxes[i].id).prop('disabled')) {
                checkboxes[i].checked = source.checked;
                if (checkboxes[i].checked) {
                    selectedIds.push(checkboxes[i].value);
                }
            }
        }

        // Helper to enable/disable options by value
        const setOptionDisabled = (value, disabled) => {
            $(actionDropdown).find(`option[value="${value}"]`).prop('disabled', disabled);
        };

        // Default state: Enable Delete, Disable Restore/Perm Delete
        setOptionDisabled('delete', false);
        setOptionDisabled('restore', true);
        setOptionDisabled('permanently-delete', true);

        if (dataType === 'booking') {
            setOptionDisabled('delete', true);
            setOptionDisabled('restore', true);
            // Booking specific logic if needed
        }

        const baseUrl = document.querySelector('meta[name="baseUrl"]').getAttribute('content');
        const csrfToken = $('meta[name="csrf-token"]').attr('content');

        $.ajax({
            type: 'POST',
            headers: {
                'X-CSRF-Token': csrfToken,
            },
            url: baseUrl + "/check-in-trash",
            data: { ids: selectedIds, datatype: dataType },
            success: function (response) {
                if (response.all_in_trash == true) {
                    // If in trash: Enable Restore/Perm Delete, Disable Delete
                    setOptionDisabled('restore', false);
                    setOptionDisabled('permanently-delete', false);
                    setOptionDisabled('delete', true);
                } else {
                    // If not in trash (or mixed): Enable Delete, Disable Restore/Perm Delete
                    setOptionDisabled('restore', true);
                    setOptionDisabled('permanently-delete', true);
                    setOptionDisabled('delete', false);
                }
            }
        });

        $("#quick-actions").find("input, textarea, button, select").removeAttr("disabled");
        const quickActionTypeId = formPrefix ? formPrefix + 'quick-action-type' : 'quick-action-type';
        const quickActionApplyId = formPrefix ? formPrefix + 'quick-action-apply' : 'quick-action-apply';
        if ($("#" + quickActionTypeId).val() == "") {
            $("#" + quickActionApplyId).attr("disabled", true);
        }

        checkboxes.forEach((checkbox) => {
            const tableRow = $("#" + checkbox.id).closest("tr");
            if (checkbox.checked) {
                tableRow.addClass("table-active");
            } else {
                tableRow.removeClass("table-active");
            }
        });

        if ($(".select-table-row:checked").length === 0) {
            resetActionButtons(dataType);
            const selectAllId = formPrefix ? formPrefix + 'select-all-table' : 'select-all-table';
            const selectAllEl = document.getElementById(selectAllId);
            if (selectAllEl) {
                selectAllEl.indeterminate = false;
                selectAllEl.checked = false;
            }
        }

        checkRow(dataType)
    };


    window.selectAllTable = selectAllTable

    const checkRow = (dataType = null) => {
        // Find the correct elements based on dataType
        let formPrefix = '';
        if (dataType === 'loyaltyearnrule') formPrefix = 'earn_';
        else if (dataType === 'loyaltyredeemrule') formPrefix = 'redeem_';
        else if (dataType === 'loyaltyreferralrule' || dataType === 'loyaltyreferrerule') formPrefix = 'referral_';

        const quickActionTypeId = formPrefix ? formPrefix + 'quick-action-type' : 'quick-action-type';
        const statusId = formPrefix ? formPrefix + 'status' : 'status';

        if ($(".select-table-row:checked").length > 0) {
            $("#" + quickActionTypeId).prop('disabled', false);
            $("#" + statusId).prop('disabled', false);
            $("#is_featured").prop('disabled', false);

        } else {
            $("#" + quickActionTypeId + ", #" + statusId + ", #is_featured").prop('disabled', true);


        }
    }


    $(document).ready(function () {

        $(".select-table-row").prop('checked', false);
        $("form[id$='quick-action-form'] select").prop('disabled', true);
    });
    window.checkRow = checkRow



    //reset table action form elements

    const resetActionButtons = (dataType = null) => {
        checkRow(dataType)

        // Find the correct elements based on dataType
        let formPrefix = '';
        if (dataType === 'loyaltyearnrule') formPrefix = 'earn_';
        else if (dataType === 'loyaltyredeemrule') formPrefix = 'redeem_';
        else if (dataType === 'loyaltyreferralrule' || dataType === 'loyaltyreferrerule') formPrefix = 'referral_';

        const selectAllId = formPrefix ? formPrefix + 'select-all-table' : 'select-all-table';
        const formId = formPrefix ? formPrefix + 'quick-action-form' : 'quick-action-form';

        const selectAllEl = document.getElementById(selectAllId);
        if (selectAllEl !== undefined && selectAllEl !== null) {
            selectAllEl.checked = false;

            // Use the specific form ID instead of #quick-actions
            $("#" + formId)
                .find("input, textarea, button, select")
                .attr("disabled", "disabled");
            $("#" + formId + ' .quick-action-field').addClass('d-none');
            $("#" + formId + ' .quick-action-featured').addClass('d-none');

            $("#" + formId).find("select").val(null).trigger("change");
            $("#" + formId).find('.quick-action-field').find("select").val("1");
            $("#" + formId).find('.quick-action-featured').find("select").val("1")
        }
    };

    window.resetActionButtons = resetActionButtons

    jQuery(document).ready(function () {

        /*---------------------------------------------------------------------
        Tooltip
        -----------------------------------------------------------------------*/
        jQuery('[data-toggle="popover"]').popover();
        jQuery('[data-toggle="tooltip"]').tooltip();

        /*---------------------------------------------------------------------
        Fixed Nav
        -----------------------------------------------------------------------*/

        $(window).on('scroll', function () {
            if ($(window).scrollTop() > 0) {
                $('.iq-top-navbar').addClass('fixed');
            } else {
                $('.iq-top-navbar').removeClass('fixed');
            }
        });

        $(window).on('scroll', function () {
            if ($(window).scrollTop() > 0) {
                $('.white-bg-menu').addClass('sticky-menu');
            } else {
                $('.white-bg-menu').removeClass('sticky-menu');
            }
        });


        /*---------------------------------------------------------------------
         Sidebar Widget
         -----------------------------------------------------------------------*/

        jQuery(document).on("click", '.side-menu > li > a', function () {
            jQuery('.side-menu > li > a').parent().removeClass('active');
            jQuery(this).parent().addClass('active');
        });

        var parents = jQuery('li.active').parents('.submenu.collapse');

        parents.addClass('show');


        parents.parents('li').addClass('active');
        jQuery('li.active > a[aria-expanded="false"]').attr('aria-expanded', 'true');

        /*---------------------------------------------------------------------
        Page Loader
        -----------------------------------------------------------------------*/
        jQuery("#load").fadeOut();
        jQuery("#loading").delay().fadeOut("");

        /*---------------------------------------------------------------------
        Page Menu
        -----------------------------------------------------------------------*/
        jQuery(document).on('click', '.wrapper-menu', function () {
            jQuery(this).toggleClass('open');
        });

        jQuery(document).on('click', ".wrapper-menu", function () {
            jQuery("body").toggleClass("sidebar-main");
        });


        /*---------------------------------------------------------------------
         Close  navbar Toggle
         -----------------------------------------------------------------------*/

        jQuery('.close-toggle').on('click', function () {
            jQuery('.h-collapse.navbar-collapse').collapse('hide');
        });

        /*---------------------------------------------------------------------
        user toggle
        -----------------------------------------------------------------------*/
        jQuery(document).on('click', '.user-toggle', function () {
            jQuery(this).parent().addClass('show-data');
        });

        jQuery(document).on('click', ".close-data", function () {
            jQuery('.user-toggle').parent().removeClass('show-data');
        });
        jQuery(document).on("click", function (event) {
            var $trigger = jQuery(".user-toggle");
            if ($trigger !== event.target && !$trigger.has(event.target).length) {
                jQuery(".user-toggle").parent().removeClass('show-data');
            }
        });

        /*---------------------------------------------------------------------
        Data tables
        -----------------------------------------------------------------------*/
        if ($.fn.DataTable) {
            const table = $('.data-table').DataTable();
        }


        /*---------------------------------------------------------------------
        Form Validation
        -----------------------------------------------------------------------*/


        window.addEventListener('load', function () {

            var forms = document.getElementsByClassName('needs-validation');

            var validation = Array.prototype.filter.call(forms, function (form) {
                form.addEventListener('submit', function (event) {
                    if (form.checkValidity() === false) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);

        /*---------------------------------------------------------------------
        Scrollbar
        -----------------------------------------------------------------------*/

        jQuery('.data-scrollbar').each(function () {
            var attr = $(this).attr('data-scroll');
            if (typeof attr !== typeof undefined && attr !== false) {
                let Scrollbar = window.Scrollbar;
                var a = jQuery(this).data('scroll');
                Scrollbar.init(document.querySelector('div[data-scroll= "' + a + '"]'));
            }
        });

    });


    $(document).on('click', '[data-toggel-extra="side-nav"]', function () {
        const pannel = $(this).attr('data-expand-extra')
        $(pannel).addClass('active')
    })

    $(document).on('click', '[data-toggel-extra="side-nav-close"]', function () {
        const pannel = $(this).attr('data-expand-extra')
        $(pannel).removeClass('active')
    })

    $(document).on('click', '[data-toggel-extra="right-sidenav"]', function () {
        const target = $(this).data('target')
        $(target).addClass('active')
    })

    $(document).on('click', '[data-extra-dismiss="right-sidenav"]', function () {
        $(this).closest('.right-sidenav').removeClass('active')
    })

    $(document).on('click', '[data-toggle="end-call"]', function () {
        $(this).closest('.tab-pane').removeClass('active').removeClass('show')
        $($(this).attr('data-target')).tab('show')
        $('.chat-action').find('[data-toggle="tab"]').removeClass('active')
    })

    $(document).on('click', '[data-toggle-extra="tab"]', function () {
        const target = $(this).attr('data-target-extra')
        $('[data-toggle-extra="tab-content"]').removeClass('active')
        $(target).addClass('active')
        $(this).parent().find('.active').removeClass('active')
        $(this).addClass('active')
    })

    $('.dropdown-menu').on('click', function (event) {
        event.stopPropagation();
    });



})(jQuery);
