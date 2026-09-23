@if (session('success'))
    <script !src="">
        $.notify(
            "{{ session('success') }}",
            {
                animate: {
                    enter: 'animated bounce',
                    exit: 'animated bounce'
                },
                type: 'success',
                placement: {
                    from: 'top',
                    align: 'center'
                }
            });
    </script>>
@endif

@if (session('error'))
    <script !src="">
        $.notify(
            "{{ session('error') }}",
            {
                animate: {
                    enter: 'animated bounce',
                    exit: 'animated bounce'
                },
                type: 'danger',
                placement: {
                    from: 'top',
                    align: 'center'
                }
            });
    </script>>
@endif
