@if ($errors->any())
    @foreach ($errors->all() as $error)
        <script !src="">
            $.notify(
                "{{$error}}",
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
    @endforeach
@endif
