<script>
    "use strict";
    // Class definition
    var KTKBootstrapTouchspin = function() {

        // Private functions
        var demos = function() {
            // minimum setup
            $('#kt_touchspin_1, #kt_touchspin_2_1').TouchSpin({
                buttondown_class: 'btn btn-secondary',
                buttonup_class: 'btn btn-secondary',

                min: 0,
                max: 100,
                step: 0.1,
                decimals: 2,
                boostat: 5,
                maxboostedstep: 10,
            });

            // with prefix
            $('#kt_touchspin_2, #kt_touchspin_2_2').TouchSpin({
                buttondown_class: 'btn btn-secondary',
                buttonup_class: 'btn btn-secondary',

                min: -1000000000,
                max: 1000000000,
                stepinterval: 50,
                maxboostedstep: 10000000,
                prefix: '$'
            });

            // vertical button alignment:
            $('#kt_touchspin_3, #kt_touchspin_2_3,#stock').TouchSpin({
                buttondown_class: 'btn btn-secondary',
                buttonup_class: 'btn btn-secondary',

                min: -1000000000,
                max: 1000000000,
                stepinterval: 50,
                maxboostedstep: 10000000,
                // postfix: '$'
            });

            // vertical buttons with custom icons:
            $('#kt_touchspin_4, #kt_touchspin_2_4').TouchSpin({
                buttondown_class: 'btn btn-secondary',
                buttonup_class: 'btn btn-secondary',
                verticalbuttons: true,
                verticalup: '<i class="ki ki-plus"></i>',
                verticaldown: '<i class="ki ki-minus"></i>'
            });

            // vertical buttons with custom icons:
            $('#kt_touchspin_5, #kt_touchspin_2_5').TouchSpin({
                buttondown_class: 'btn btn-secondary',
                buttonup_class: 'btn btn-secondary',
                verticalbuttons: true,
                verticalup: '<i class="ki ki-arrow-up"></i>',
                verticaldown: '<i class="ki ki-arrow-down"></i>'
            });
        }

        return {
            // public functions
            init: function() {
                demos();
            }
        };
    }();

    jQuery(document).ready(function() {
        KTKBootstrapTouchspin.init();
    });
    $(function (){

        $('body').on('click','.kt_sweetalert_demo_9',function (e) {
            e.preventDefault();
            const that = $(this);
            Swal.fire({
                title: "{{__('site.global.are_you_sure')}}",
                text: '{{__('site.global.delete_this_row')}}',
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "{{__('site.global.yes')}}",
                cancelButtonText: "{{__('site.global.no')}}",
                reverseButtons: true
            }).then(function(result) {
                if (result.value) {
                    Swal.fire(
                        "Deleted!",
                        "Your record has been deleted.",
                        "success"
                    )
                    that.closest('form').submit();
                } else if (result.dismiss === "cancel") {
                    Swal.fire(
                        "Cancelled",
                        "Your imaginary data is safe :)",
                        "error"
                    )
                }
            });
        });
    })
</script>
