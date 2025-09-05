/**
 * WooCommerce Category Product Ordering - Admin JS
 *
 * Description: Drag & Drop sorting of goods in the categories of WooCommerce.
 *
 * @author  Aleksandr BespredeL Kireev
 * @license MIT
 * @link    https://bespredel.name
 */

jQuery(function ($) {
    const $list = $("#woo-cat-ordering-list");

    let $toastContainer = $("#woo-cat-toast-container");
    if (!$toastContainer.length) {
        $toastContainer = $('<div id="woo-cat-toast-container"></div>').appendTo("body").css({
            position: "fixed",
            top: "40px",
            right: "20px",
            zIndex: 9999
        });
    }

    function showToast(message, type = "info") {
        const $toast = $('<div class="woo-cat-toast"></div>')
        .addClass(type)
        .text(message)
        .appendTo($toastContainer)
        .hide();

        $toast.slideDown(200);

        setTimeout(() => {
            $toast.slideUp(400, function () {
                $(this).remove();
            });
        }, 2000);
    }

    $list.sortable({
        update: function (event, ui) {
            let order = [];
            $list.find("li").each(function () {
                order.push($(this).data("id"));
            });

            showToast(WooCategoryOrdering.saving_text, "info");

            $.post(WooCategoryOrdering.ajax_url, {
                action: "woo_category_ordering_save",
                nonce: WooCategoryOrdering.nonce,
                order: order,
                term_id: $list.data("term")
            }, function (response) {
                if (response.success) {
                    showToast(WooCategoryOrdering.saved_text, "success");
                } else {
                    showToast("Error saving order", "error");
                }
            });
        }
    });
});
