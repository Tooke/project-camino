jQuery(document).ready(function ($) {
    $("#tdwm-sortable-roles tbody").sortable({
        update: function () {
            const order = {};
            $("#tdwm-sortable-roles tbody tr").each(function (index) {
                const roleId = $(this).data("role-id");
                order[roleId] = index;
            });

            $.post(tdwm_ajax.ajax_url, {
                action: 'tdwm_update_sort_order',
                order: order
            }, function (response) {
                if (response.success) {
                    alert("Sort order updated.");
                } else {
                    alert("Failed: " + response.data);
                }
            });
        }
    });
});